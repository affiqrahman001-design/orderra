import type { FaqAdapter } from "../../faqService";
import type { FaqItem } from "../../../contracts/faq";
import { faqs } from "../../../data/faqs";

export const faqHttpAdapter: FaqAdapter = {
  async listFaqs(): Promise<FaqItem[]> {
    // Backend v1 does not expose public FAQ yet. Keep this local fallback config-driven.
    return faqs;
  },
};
