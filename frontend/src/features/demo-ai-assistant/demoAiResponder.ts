import {
  DEMO_AI_DEFAULT_QUICK_REPLIES,
  DEMO_AI_HELP_CENTER_LINK,
  DEMO_AI_KNOWLEDGE_BASE,
  DEMO_AI_PRIVACY_POLICY_LINK,
  DEMO_AI_SUPPORT_FALLBACK,
} from './demoAiKnowledge';
import type {
  DemoAiCatalogContext,
  DemoAiKnowledgeEntry,
  DemoAiLink,
  DemoAiReply,
} from './demoAiTypes';

const MINIMUM_MATCH_SCORE = 2;

function normalize(input: string): string {
  return input
    .normalize('NFKD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9\s/-]/g, ' ')
    .replace(/\s+/g, ' ');
}

const DEMO_AI_MAX_QUICK_REPLIES = 4;
const DEMO_AI_MAX_REPLY_LINKS = 2;

const DEMO_AI_IGNORED_MATCH_TOKENS = new Set([
  'a',
  'about',
  'an',
  'and',
  'are',
  'can',
  'could',
  'do',
  'does',
  'for',
  'from',
  'how',
  'into',
  'is',
  'me',
  'my',
  'of',
  'on',
  'or',
  'please',
  'should',
  'the',
  'this',
  'to',
  'what',
  'when',
  'where',
  'why',
  'will',
  'with',
  'you',
  'your',
]);

function normalizeQuickReplies(quickReplies?: string[]): string[] {
  const source = quickReplies?.length ? quickReplies : DEMO_AI_DEFAULT_QUICK_REPLIES;
  const seen = new Set<string>();

  return source
    .map((reply) => reply.trim())
    .filter((reply) => {
      if (!reply) return false;

      const key = reply.toLowerCase();
      if (seen.has(key)) return false;

      seen.add(key);
      return true;
    })
    .slice(0, DEMO_AI_MAX_QUICK_REPLIES);
}

function normalizeReplyLinks(links?: DemoAiLink[]): DemoAiLink[] | undefined {
  if (!links?.length) return undefined;

  const seen = new Set<string>();
  const safeLinks = links.filter((link) => {
    const label = link.label.trim();
    const path = link.path.trim();

    if (!label || !path.startsWith('/')) return false;

    const key = `${label.toLowerCase()}::${path}`;
    if (seen.has(key)) return false;

    seen.add(key);
    return true;
  });

  return safeLinks.slice(0, DEMO_AI_MAX_REPLY_LINKS);
}

function tokenize(input: string): string[] {
  return normalize(input)
    .split(' ')
    .filter((token) => token.length >= 3 && !DEMO_AI_IGNORED_MATCH_TOKENS.has(token));
}

function listNamesByCategory(categoryId: string, context: DemoAiCatalogContext): string {
  return context.products
    .filter((product) => product.categoryId === categoryId)
    .map((product) => product.name)
    .slice(0, 6)
    .join(', ');
}

function scoreEntry(query: string, queryTokens: string[], entry: DemoAiKnowledgeEntry): number {
  const searchable = normalize([entry.question, entry.topic, ...entry.keywords].join(' '));

  return entry.keywords.reduce((score, keyword) => {
    const normalizedKeyword = normalize(keyword);
    if (!normalizedKeyword) return score;

    if (query === normalizedKeyword) return score + 8;
    if (query.includes(normalizedKeyword)) return score + (normalizedKeyword.includes(' ') ? 5 : 3);

    const keywordTokens = tokenize(normalizedKeyword);
    const tokenMatches = keywordTokens.filter((token) => queryTokens.includes(token)).length;
    const searchableMatches = queryTokens.filter((token) => searchable.includes(token)).length;

    return score + tokenMatches * 2 + Math.min(searchableMatches, 2);
  }, 0);
}

function findBestEntry(query: string): DemoAiKnowledgeEntry | null {
  const normalizedQuery = normalize(query);
  const queryTokens = tokenize(normalizedQuery);

  if (!normalizedQuery || queryTokens.length === 0) return null;

  const [bestMatch] = DEMO_AI_KNOWLEDGE_BASE.map((entry) => ({
    entry,
    score: scoreEntry(normalizedQuery, queryTokens, entry),
  })).sort((a, b) => b.score - a.score);

  return bestMatch && bestMatch.score >= MINIMUM_MATCH_SCORE ? bestMatch.entry : null;
}

function buildKnowledgeReply(entry: DemoAiKnowledgeEntry): DemoAiReply {
  return {
    text: entry.answer,
    quickReplies: normalizeQuickReplies(entry.quickReplies),
    links: normalizeReplyLinks(entry.links),
    matchedEntryId: entry.id,
    topic: entry.topic,
  };
}

function buildMenuRecommendationReply(context: DemoAiCatalogContext): DemoAiReply {
  const burgers = context.products.filter((product) => product.categoryId === 'burgers');
  const bestBurger =
    burgers.find((product) => product.slug === 'smokehouse-prime-burger') ?? burgers[0];

  return {
    text: bestBurger
      ? `For a rich demo pick, try ${bestBurger.name}. It pairs nicely with fries or a combo, and the order still stays safely inside the demo.`
      : 'The burger menu is available in the Burgers category. Any order you create stays safely inside the demo.',
    quickReplies: normalizeQuickReplies(['Payment demo', 'Delivery tracking', 'Dine-in QR']),
    topic: 'menu_customization',
  };
}

function buildDrinksReply(context: DemoAiCatalogContext): DemoAiReply {
  const drinksList = listNamesByCategory('drinks', context);

  return {
    text: drinksList
      ? `You can try these demo drinks: ${drinksList}. Add one before checkout if you want to test a full order.`
      : 'Drinks are available in the Drinks category when included in the demo menu.',
    quickReplies: normalizeQuickReplies(['Best burger', 'Checkout help', 'Pickup order']),
    topic: 'menu_customization',
  };
}

function buildFallbackReply(): DemoAiReply {
  return {
    text: DEMO_AI_SUPPORT_FALLBACK,
    quickReplies: normalizeQuickReplies([...DEMO_AI_DEFAULT_QUICK_REPLIES]),
    links: normalizeReplyLinks([DEMO_AI_HELP_CENTER_LINK]),
  };
}

function isHumanSupportRequest(query: string): boolean {
  return [
    'human agent',
    'real person',
    'talk to staff',
    'talk to support',
    'talk to agent',
    'live agent',
    'live chat',
    'contact support',
    'customer service',
    'support ticket',
    'create ticket',
    'request demo help',
    'need more help',
  ].some((phrase) => query.includes(phrase));
}

function buildHumanSupportBoundaryReply(): DemoAiReply {
  return {
    text: 'This is a demo support agent, so it does not contact a real human agent or create a real support ticket. You can still use the Help Center for detailed demo guidance.',
    quickReplies: normalizeQuickReplies(['Help Center', 'Privacy Policy', 'Refund demo']),
    links: normalizeReplyLinks([DEMO_AI_HELP_CENTER_LINK, DEMO_AI_PRIVACY_POLICY_LINK]),
    supportCard: {
      title: 'Need more help?',
      text: 'This demo does not connect to a real support team, but the Help Center explains the full ORDERra support flow.',
    },
    topic: 'support_demo',
  };
}

export function buildDemoAiReply(userInput: string, context: DemoAiCatalogContext): DemoAiReply {
  const query = normalize(userInput);

  if (isHumanSupportRequest(query)) {
    return buildHumanSupportBoundaryReply();
  }

  if (
    query.includes('best burger') ||
    query.includes('burger recommendation') ||
    query.includes('recommend burger')
  ) {
    return buildMenuRecommendationReply(context);
  }

  if (query.includes('see drinks') || query.includes('drink') || query.includes('cola')) {
    return buildDrinksReply(context);
  }

  const matchedEntry = findBestEntry(query);
  if (matchedEntry) {
    return buildKnowledgeReply(matchedEntry);
  }

  return buildFallbackReply();
}
