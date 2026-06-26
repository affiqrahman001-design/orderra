import type { FaqAdapter } from "../../faqService";
import { faqs } from "../../../data/faqs";

export const faqLocalAdapter: FaqAdapter = {
  async listFaqs() {
    return faqs;
  },
};
