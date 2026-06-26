import type { Category } from '../../contracts/category';
import type { Product } from '../../contracts/product';

export type DemoAiRole = 'user' | 'assistant';

export type DemoAiWidgetMode = 'open' | 'collapsed';

export type DemoAiTopicKey =
  | 'getting_started'
  | 'menu_customization'
  | 'cart_checkout'
  | 'dine_in_qr'
  | 'pickup'
  | 'delivery_tracking'
  | 'payment_simulation'
  | 'refunds_issues'
  | 'account_privacy'
  | 'support_demo';

export interface DemoAiLink {
  label: string;
  path: string;
}

export interface DemoAiSupportCard {
  title: string;
  text: string;
}

export interface DemoAiMessage {
  id: string;
  role: DemoAiRole;
  text: string;
  createdAt: number;
  links?: DemoAiLink[];
  supportCard?: DemoAiSupportCard;
}

export interface DemoAiPosition {
  x: number;
  y: number;
}

export interface DemoAiPersistedState {
  mode: DemoAiWidgetMode;
  position: DemoAiPosition;
  messages: DemoAiMessage[];
}

export interface DemoAiCatalogContext {
  products: Product[];
  categories: Category[];
}

export interface DemoAiKnowledgeEntry {
  id: string;
  topic: DemoAiTopicKey;
  question: string;
  answer: string;
  keywords: string[];
  quickReplies?: string[];
  links?: DemoAiLink[];
  helpCenterCategory?: DemoAiTopicKey;
}

export interface DemoAiReply {
  text: string;
  quickReplies?: string[];
  links?: DemoAiLink[];
  supportCard?: DemoAiSupportCard;
  matchedEntryId?: string;
  topic?: DemoAiTopicKey;
}
