import {
  useEffect,
  useMemo,
  useRef,
  useState,
  type FormEvent,
  type PointerEvent as ReactPointerEvent,
} from 'react';
import './DemoAiAssistant.css';
import {
  DEMO_AI_AGENT_NAME,
  DEMO_AI_DEFAULT_QUICK_REPLIES,
  DEMO_AI_WELCOME,
} from './demoAiKnowledge';
import { buildDemoAiReply } from './demoAiResponder';
import { getDefaultDemoAiPosition, readDemoAiState, writeDemoAiState } from './demoAiStorage';
import type {
  DemoAiCatalogContext,
  DemoAiLink,
  DemoAiMessage,
  DemoAiPosition,
  DemoAiSupportCard,
  DemoAiWidgetMode,
} from './demoAiTypes';

type DemoAiAssistantProps = DemoAiCatalogContext & {
  navigate: (path: string) => void;
};
type DemoAiDockSide = 'left' | 'right';
type DemoAiClosedVariant = 'floating' | 'edge-left' | 'edge-right';
type DemoAiDragMode = 'panel' | 'dock';

const HEADER_DRAG_SELECTOR = '[data-demo-ai-drag-handle]';
const INTERACTIVE_SELECTOR = 'button, input, textarea, select, a';

const MOBILE_BREAKPOINT = 820;
const VIEWPORT_MARGIN = 10;
const PANEL_DEFAULT_WIDTH = 360;
const PANEL_DEFAULT_HEIGHT = 500;

const FLOATING_BUBBLE_SIZE = 58;
const FLOATING_HOVER_WIDTH = 124;
const EDGE_DOCK_WIDTH = 90;
const EDGE_DOCK_HEIGHT = 58;
const EDGE_HIDDEN_WIDTH = 34;
const EDGE_VISIBLE_WIDTH = 56;
const EDGE_SNAP_DISTANCE = 92;

const DESKTOP_SAFE_BOTTOM = 22;
const MOBILE_SAFE_BOTTOM = 16;
const CLOSED_SAFE_BOTTOM = 112;
const MIN_TOP = 84;
const DRAG_THRESHOLD = 7;

interface ActiveDragState {
  mode: DemoAiDragMode;
  pointerId: number;
  startClient: DemoAiPosition;
  offset: DemoAiPosition;
  hasMoved: boolean;
}

function isBrowser(): boolean {
  return typeof window !== 'undefined';
}

function isMobileViewport(): boolean {
  return isBrowser() && window.innerWidth <= MOBILE_BREAKPOINT;
}

function getElementSize(
  element: HTMLElement | null,
  fallbackWidth: number,
  fallbackHeight: number,
): { width: number; height: number } {
  return {
    width: element?.offsetWidth || fallbackWidth,
    height: element?.offsetHeight || fallbackHeight,
  };
}

function getDockSide(position: DemoAiPosition, width = EDGE_DOCK_WIDTH): DemoAiDockSide {
  if (!isBrowser()) return 'right';
  return position.x + width / 2 < window.innerWidth / 2 ? 'left' : 'right';
}

function getClosedVariant(position: DemoAiPosition): DemoAiClosedVariant {
  if (!isBrowser()) return 'edge-right';

  const side = getDockSide(position, EDGE_DOCK_WIDTH);

  if (isMobileViewport()) {
    return side === 'left' ? 'edge-left' : 'edge-right';
  }

  if (position.x <= 0) return 'edge-left';
  if (position.x + EDGE_DOCK_WIDTH >= window.innerWidth - 2) return 'edge-right';

  return 'floating';
}

function clampY(y: number, height: number, bottomSafe = CLOSED_SAFE_BOTTOM): number {
  if (!isBrowser()) return y;

  const maxY = Math.max(MIN_TOP, window.innerHeight - height - bottomSafe);
  return Math.min(Math.max(y, MIN_TOP), maxY);
}

function clampFloatingClosedPosition(position: DemoAiPosition): DemoAiPosition {
  if (!isBrowser()) return position;

  const maxX = Math.max(
    VIEWPORT_MARGIN,
    window.innerWidth - FLOATING_HOVER_WIDTH - VIEWPORT_MARGIN,
  );

  return {
    x: Math.min(Math.max(position.x, VIEWPORT_MARGIN), maxX),
    y: clampY(position.y, FLOATING_BUBBLE_SIZE, CLOSED_SAFE_BOTTOM),
  };
}

function clampDraggingClosedPosition(position: DemoAiPosition): DemoAiPosition {
  if (!isBrowser()) return position;

  return {
    x: Math.min(Math.max(position.x, -EDGE_HIDDEN_WIDTH), window.innerWidth - EDGE_VISIBLE_WIDTH),
    y: clampY(position.y, EDGE_DOCK_HEIGHT, CLOSED_SAFE_BOTTOM),
  };
}

function clampOpenPosition(
  position: DemoAiPosition,
  width = PANEL_DEFAULT_WIDTH,
  height = PANEL_DEFAULT_HEIGHT,
): DemoAiPosition {
  if (!isBrowser()) return position;

  const bottomSafe = isMobileViewport() ? MOBILE_SAFE_BOTTOM : DESKTOP_SAFE_BOTTOM;
  const maxX = Math.max(VIEWPORT_MARGIN, window.innerWidth - width - VIEWPORT_MARGIN);
  const maxY = Math.max(VIEWPORT_MARGIN, window.innerHeight - height - bottomSafe);

  return {
    x: Math.min(Math.max(position.x, VIEWPORT_MARGIN), maxX),
    y: Math.min(Math.max(position.y, VIEWPORT_MARGIN), maxY),
  };
}

function snapToEdge(
  position: DemoAiPosition,
  width = EDGE_DOCK_WIDTH,
  height = EDGE_DOCK_HEIGHT,
): DemoAiPosition {
  if (!isBrowser()) return position;

  const side = getDockSide(position, width);
  const x = side === 'left' ? -EDGE_HIDDEN_WIDTH : window.innerWidth - width + EDGE_HIDDEN_WIDTH;

  return {
    x,
    y: clampY(position.y, height, CLOSED_SAFE_BOTTOM),
  };
}

function shouldSnapToEdge(position: DemoAiPosition, width = EDGE_DOCK_WIDTH): boolean {
  if (!isBrowser()) return true;
  if (isMobileViewport()) return true;

  return (
    position.x <= EDGE_SNAP_DISTANCE || position.x + width >= window.innerWidth - EDGE_SNAP_DISTANCE
  );
}

function getPanelPositionFromClosed(
  position: DemoAiPosition,
  variant: DemoAiClosedVariant,
): DemoAiPosition {
  if (!isBrowser()) return position;

  if (isMobileViewport()) return position;

  if (variant === 'edge-left') {
    return clampOpenPosition({ x: VIEWPORT_MARGIN, y: position.y - 80 });
  }

  if (variant === 'edge-right') {
    return clampOpenPosition({
      x: window.innerWidth - PANEL_DEFAULT_WIDTH - VIEWPORT_MARGIN,
      y: position.y - 80,
    });
  }

  return clampOpenPosition({
    x: position.x,
    y: position.y - 86,
  });
}

function createMessage(
  role: DemoAiMessage['role'],
  text: string,
  links?: DemoAiLink[],
  supportCard?: DemoAiSupportCard,
): DemoAiMessage {
  return {
    id: `${role}-${Date.now()}-${Math.random().toString(16).slice(2)}`,
    role,
    text,
    links,
    supportCard,
    createdAt: Date.now(),
  };
}

const INITIAL_MESSAGE = createMessage('assistant', DEMO_AI_WELCOME);

export function DemoAiAssistant({ products, categories, navigate }: DemoAiAssistantProps) {
  const persisted = useMemo(() => readDemoAiState(), []);
  const initialMode = persisted?.mode ?? 'collapsed';
  const initialPosition = persisted?.position ?? getDefaultDemoAiPosition();

  const [mode, setMode] = useState<DemoAiWidgetMode>(initialMode);
  const [messages, setMessages] = useState<DemoAiMessage[]>(
    persisted?.messages.length ? persisted.messages : [INITIAL_MESSAGE],
  );
  const [draft, setDraft] = useState('');
  const [position, setPosition] = useState<DemoAiPosition>(() => {
    if (initialMode === 'open') return clampOpenPosition(initialPosition);
    return snapToEdge(initialPosition);
  });
  const [quickReplies, setQuickReplies] = useState<string[]>([...DEMO_AI_DEFAULT_QUICK_REPLIES]);
  const [isDragging, setIsDragging] = useState(false);

  const widgetRef = useRef<HTMLDivElement | null>(null);
  const messagesEndRef = useRef<HTMLDivElement | null>(null);
  const activeDragRef = useRef<ActiveDragState | null>(null);
  const lastClosedPositionRef = useRef<DemoAiPosition>(
    initialMode === 'open' ? snapToEdge(initialPosition) : position,
  );

  const context: DemoAiCatalogContext = useMemo(
    () => ({ products, categories }),
    [products, categories],
  );

  const isCollapsed = mode === 'collapsed';
  const closedVariant = isCollapsed ? getClosedVariant(position) : 'floating';
  const isFloatingClosed = isCollapsed && closedVariant === 'floating';
  const isEdgeLeft = isCollapsed && closedVariant === 'edge-left';
  const isEdgeRight = isCollapsed && closedVariant === 'edge-right';

  useEffect(() => {
    const onResize = () => {
      const element = widgetRef.current;

      setPosition((current) => {
        if (mode === 'open') {
          if (isMobileViewport()) return current;

          const { width, height } = getElementSize(
            element,
            PANEL_DEFAULT_WIDTH,
            PANEL_DEFAULT_HEIGHT,
          );
          return clampOpenPosition(current, width, height);
        }

        if (isMobileViewport()) return snapToEdge(current);

        const variant = getClosedVariant(current);
        if (variant === 'floating') return clampFloatingClosedPosition(current);

        return snapToEdge(current);
      });
    };

    window.addEventListener('resize', onResize);
    return () => window.removeEventListener('resize', onResize);
  }, [mode]);

  useEffect(() => {
    writeDemoAiState({ mode, position, messages });
  }, [mode, position, messages]);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ block: 'end' });
  }, [messages, mode]);

  const sendMessage = (text: string) => {
    const cleaned = text.trim();
    if (!cleaned) return;

    const userMessage = createMessage('user', cleaned);
    const reply = buildDemoAiReply(cleaned, context);
    const assistantMessage = createMessage('assistant', reply.text, reply.links, reply.supportCard);

    setMessages((current) => [...current, userMessage, assistantMessage].slice(-24));
    setQuickReplies(reply.quickReplies ?? [...DEMO_AI_DEFAULT_QUICK_REPLIES]);
    setDraft('');
  };

  const openFromClosed = () => {
    const variant = getClosedVariant(position);
    lastClosedPositionRef.current = position;
    setPosition(getPanelPositionFromClosed(position, variant));
    setMode('open');
  };

  const closeToDock = () => {
    if (isMobileViewport()) {
      setPosition(snapToEdge(lastClosedPositionRef.current));
      setMode('collapsed');
      return;
    }

    setPosition(() => {
      const previousClosed = lastClosedPositionRef.current;
      const variant = getClosedVariant(previousClosed);

      if (variant === 'floating') return clampFloatingClosedPosition(previousClosed);
      return snapToEdge(previousClosed);
    });

    setMode('collapsed');
  };

  const onSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    sendMessage(draft);
  };

  const onPointerDown = (event: ReactPointerEvent<HTMLDivElement>) => {
    const target = event.target as HTMLElement;
    const isInteractiveControl = Boolean(target.closest(INTERACTIVE_SELECTOR));

    if (mode === 'open') {
      if (isMobileViewport()) return;
      if (!target.closest(HEADER_DRAG_SELECTOR) || isInteractiveControl) return;
    }

    const element = widgetRef.current;
    if (!element) return;

    activeDragRef.current = {
      mode: mode === 'open' ? 'panel' : 'dock',
      pointerId: event.pointerId,
      startClient: { x: event.clientX, y: event.clientY },
      offset: { x: event.clientX - position.x, y: event.clientY - position.y },
      hasMoved: false,
    };

    event.currentTarget.setPointerCapture(event.pointerId);
  };

  const onPointerMove = (event: ReactPointerEvent<HTMLDivElement>) => {
    const activeDrag = activeDragRef.current;
    const element = widgetRef.current;
    if (!activeDrag || !element || activeDrag.pointerId !== event.pointerId) return;

    const moveX = event.clientX - activeDrag.startClient.x;
    const moveY = event.clientY - activeDrag.startClient.y;
    const distance = Math.hypot(moveX, moveY);

    if (!activeDrag.hasMoved && distance < DRAG_THRESHOLD) return;

    activeDrag.hasMoved = true;
    setIsDragging(true);

    const next = {
      x: event.clientX - activeDrag.offset.x,
      y: event.clientY - activeDrag.offset.y,
    };

    if (activeDrag.mode === 'panel') {
      setPosition(clampOpenPosition(next, element.offsetWidth, element.offsetHeight));
      return;
    }

    setPosition(clampDraggingClosedPosition(next));
  };

  const stopDragging = (event: ReactPointerEvent<HTMLDivElement>, openOnTap = true) => {
    const activeDrag = activeDragRef.current;
    const element = widgetRef.current;
    if (!activeDrag || activeDrag.pointerId !== event.pointerId) return;

    if (event.currentTarget.hasPointerCapture(event.pointerId)) {
      event.currentTarget.releasePointerCapture(event.pointerId);
    }

    activeDragRef.current = null;
    setIsDragging(false);

    if (activeDrag.mode === 'dock') {
      if (!activeDrag.hasMoved) {
        if (openOnTap) openFromClosed();
        return;
      }

      const { width, height } = getElementSize(element, EDGE_DOCK_WIDTH, EDGE_DOCK_HEIGHT);

      setPosition((current) => {
        if (shouldSnapToEdge(current, width)) {
          const snapped = snapToEdge(current, width, height);
          lastClosedPositionRef.current = snapped;
          return snapped;
        }

        const floating = clampFloatingClosedPosition(current);
        lastClosedPositionRef.current = floating;
        return floating;
      });
    }
  };

  return (
    <div
      ref={widgetRef}
      className={`demo-ai-widget ${mode === 'open' ? 'is-open' : 'is-closed'} ${
        isMobileViewport() && mode === 'open' ? 'is-mobile-open' : ''
      } ${isFloatingClosed ? 'is-floating' : ''} ${isEdgeLeft ? 'is-edge-left' : ''} ${
        isEdgeRight ? 'is-edge-right' : ''
      } ${isDragging ? 'is-dragging' : ''}`}
      style={{ left: `${position.x}px`, top: `${position.y}px` }}
      onPointerDown={onPointerDown}
      onPointerMove={onPointerMove}
      onPointerUp={(event) => stopDragging(event)}
      onPointerCancel={(event) => stopDragging(event, false)}
      role={isCollapsed ? 'button' : 'complementary'}
      tabIndex={isCollapsed ? 0 : undefined}
      onKeyDown={(event) => {
        if (isCollapsed && (event.key === 'Enter' || event.key === ' ')) {
          event.preventDefault();
          openFromClosed();
        }
      }}
      aria-label={isCollapsed ? 'Open ORDERra Support Agent' : 'ORDERra Support Agent'}
    >
      {isCollapsed ? (
        <div className="demo-ai-widget__closed-content" aria-hidden="true">
          <span className="demo-ai-widget__closed-dot">AI</span>
          <span className="demo-ai-widget__closed-label">Support</span>
        </div>
      ) : (
        <>
          <div className="demo-ai-widget__header" data-demo-ai-drag-handle>
            <div>
              <p>{DEMO_AI_AGENT_NAME}</p>
              <small>Friendly demo help</small>
            </div>
            <button
              type="button"
              className="demo-ai-widget__close"
              data-demo-ai-control
              aria-label="Close support agent"
              onClick={closeToDock}
            >
              ×
            </button>
          </div>

          <div className="demo-ai-widget__messages" aria-live="polite">
            {messages.map((message) => (
              <div
                key={message.id}
                className={`demo-ai-widget__message-group${
                  message.role === 'user' ? ' is-user' : ' is-assistant'
                }`}
              >
                <p
                  className={`demo-ai-widget__message${
                    message.role === 'user' ? ' is-user' : ' is-assistant'
                  }`}
                >
                  {message.text}
                </p>

                {message.role === 'assistant' && message.supportCard ? (
                  <div className="demo-ai-widget__support-card">
                    <strong>{message.supportCard.title}</strong>
                    <span>{message.supportCard.text}</span>
                  </div>
                ) : null}

                {message.role === 'assistant' && message.links?.length ? (
                  <div className="demo-ai-widget__message-links">
                    {message.links.map((link, index) => (
                      <button
                        type="button"
                        key={`${message.id}-${link.path}-${index}`}
                        onClick={() => navigate(link.path)}
                      >
                        {link.label}
                      </button>
                    ))}
                  </div>
                ) : null}
              </div>
            ))}
            <div ref={messagesEndRef} />
          </div>

          <div className="demo-ai-widget__quick-replies">
            {quickReplies.map((reply) => (
              <button type="button" key={reply} onClick={() => sendMessage(reply)}>
                {reply}
              </button>
            ))}
          </div>

          <form className="demo-ai-widget__composer" onSubmit={onSubmit}>
            <label htmlFor="demo-ai-input" className="sr-only">
              Ask ORDERra Support Agent
            </label>
            <input
              id="demo-ai-input"
              name="demoAiMessage"
              type="text"
              autoComplete="off"
              enterKeyHint="send"
              value={draft}
              onChange={(event) => setDraft(event.target.value)}
              placeholder="Ask about ordering, payment, or support"
            />
            <button type="submit" aria-label="Send message to support agent">
              Send
            </button>
          </form>
        </>
      )}
    </div>
  );
}
