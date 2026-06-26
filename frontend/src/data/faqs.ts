import type { FaqItem } from "../contracts/faq";

export const faqs: FaqItem[] = [
  {
    id: "faq-delivery-window",
    question: "How long does a standard order usually take?",
    answer:
      "Most orders land within 25 to 35 minutes, depending on item mix and delivery distance. The estimate shown at confirmation already accounts for current kitchen pacing.",
  },
  {
    id: "faq-allergy",
    question: "Can I leave an allergy or ingredient note?",
    answer:
      "Yes. Use the kitchen note field inside customisable items, then add any broader reminder during checkout if needed.",
  },
  {
    id: "faq-pickup",
    question: "Do you support pickup as well as delivery?",
    answer:
      "Yes. Pickup can be selected during checkout, and the form trims back so only the details that matter stay visible.",
  },
  {
    id: "faq-promo",
    question: "Where should promo codes be entered?",
    answer:
      "Apply the code from the cart drawer before moving to checkout. The discount is reflected immediately in the order summary.",
  },
  {
    id: "faq-payment",
    question: "What payment methods are available at checkout?",
    answer:
      "Card and cash are both available in the current demo flow. Card keeps delivery handoff simpler, while cash remains useful for pickup. Additional methods can be added later through the backend-ready payment contract.",
  },
  {
    id: "faq-change-order",
    question: "Can I adjust the order after it is placed?",
    answer:
      "Small changes are easiest before the kitchen begins. Once a dish is already being prepared, the safer path is usually to contact support directly.",
  },
];
