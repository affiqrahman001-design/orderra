import type { FaqItem } from "../contracts/faq";

export interface FaqAdapter {
  listFaqs(): Promise<FaqItem[]>;
}

export interface FaqService {
  listFaqs(): Promise<FaqItem[]>;
}

export function createFaqService(adapter: FaqAdapter): FaqService {
  return {
    listFaqs: () => adapter.listFaqs(),
  };
}
