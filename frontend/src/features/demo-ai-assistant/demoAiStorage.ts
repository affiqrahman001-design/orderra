import type {
  DemoAiLink,
  DemoAiMessage,
  DemoAiPersistedState,
  DemoAiPosition,
  DemoAiSupportCard,
  DemoAiWidgetMode,
} from './demoAiTypes';

const STORAGE_KEY = 'orderra_demo_ai_widget_v1';
const MAX_STORED_MESSAGES = 24;
const ALLOWED_INTERNAL_LINK_PATHS = new Set([
  '/help-center',
  '/help',
  '/privacy-policy',
  '/privacy',
]);

const DESKTOP_DEFAULT_POSITION: DemoAiPosition = { x: 0, y: 260 };
const MOBILE_DEFAULT_POSITION: DemoAiPosition = { x: 0, y: 280 };
const MOBILE_BREAKPOINT = 820;
const DOCK_VISIBLE_WIDTH = 56;
const DOCK_SAFE_BOTTOM = 116;

function getViewportDefaultPosition(): DemoAiPosition {
  if (typeof window === 'undefined') return DESKTOP_DEFAULT_POSITION;

  const isMobile = window.innerWidth <= MOBILE_BREAKPOINT;
  const y = Math.max(
    96,
    Math.min(
      window.innerHeight - DOCK_SAFE_BOTTOM,
      isMobile ? MOBILE_DEFAULT_POSITION.y : DESKTOP_DEFAULT_POSITION.y,
    ),
  );

  return {
    x: Math.max(12, window.innerWidth - DOCK_VISIBLE_WIDTH),
    y,
  };
}

function sanitizeMode(input: unknown): DemoAiWidgetMode {
  if (input === 'open') return 'open';
  if (input === 'collapsed' || input === 'hidden') return 'collapsed';
  return 'collapsed';
}

function sanitizeSupportCard(input: unknown): DemoAiSupportCard | undefined {
  if (!input || typeof input !== 'object') return undefined;

  const card = input as Partial<DemoAiSupportCard>;
  if (typeof card.title !== 'string' || typeof card.text !== 'string') return undefined;

  const title = card.title.trim().slice(0, 48);
  const text = card.text.trim().slice(0, 160);

  if (!title || !text) return undefined;

  return { title, text };
}

function sanitizeLinks(input: unknown): DemoAiLink[] | undefined {
  if (!Array.isArray(input)) return undefined;

  const links = input
    .filter((entry) => entry && typeof entry === 'object')
    .map((entry) => entry as DemoAiLink)
    .filter(
      (entry) =>
        typeof entry.label === 'string' &&
        typeof entry.path === 'string' &&
        entry.path.startsWith('/') &&
        ALLOWED_INTERNAL_LINK_PATHS.has(entry.path),
    )
    .map((entry) => ({
      label: entry.label.slice(0, 40),
      path: entry.path,
    }));

  return links.length ? links.slice(0, 3) : undefined;
}

function sanitizeMessages(input: unknown): DemoAiMessage[] {
  if (!Array.isArray(input)) return [];
  return input
    .filter((entry) => entry && typeof entry === 'object')
    .map((entry) => entry as DemoAiMessage)
    .filter(
      (entry) =>
        typeof entry.text === 'string' && (entry.role === 'user' || entry.role === 'assistant'),
    )
    .map((entry) => ({
      id: typeof entry.id === 'string' ? entry.id : `${entry.role}-${Date.now()}`,
      role: entry.role,
      text: entry.text,
      createdAt: typeof entry.createdAt === 'number' ? entry.createdAt : Date.now(),
      links: entry.role === 'assistant' ? sanitizeLinks(entry.links) : undefined,
      supportCard: entry.role === 'assistant' ? sanitizeSupportCard(entry.supportCard) : undefined,
    }))
    .slice(-MAX_STORED_MESSAGES);
}

function sanitizePosition(input: unknown): DemoAiPosition {
  if (
    input &&
    typeof input === 'object' &&
    typeof (input as DemoAiPosition).x === 'number' &&
    typeof (input as DemoAiPosition).y === 'number' &&
    Number.isFinite((input as DemoAiPosition).x) &&
    Number.isFinite((input as DemoAiPosition).y)
  ) {
    return input as DemoAiPosition;
  }

  return getViewportDefaultPosition();
}

export function readDemoAiState(): DemoAiPersistedState | null {
  if (typeof window === 'undefined') return null;
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw) as Partial<DemoAiPersistedState>;
    return {
      mode: sanitizeMode(parsed.mode),
      position: sanitizePosition(parsed.position),
      messages: sanitizeMessages(parsed.messages),
    };
  } catch {
    return null;
  }
}

export function writeDemoAiState(state: DemoAiPersistedState): void {
  if (typeof window === 'undefined') return;
  window.localStorage.setItem(
    STORAGE_KEY,
    JSON.stringify({
      ...state,
      mode: sanitizeMode(state.mode),
      messages: sanitizeMessages(state.messages).slice(-MAX_STORED_MESSAGES),
    }),
  );
}

export function getDefaultDemoAiPosition(): DemoAiPosition {
  return getViewportDefaultPosition();
}
