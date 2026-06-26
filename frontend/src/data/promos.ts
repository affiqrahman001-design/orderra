import type { Promo } from "../contracts/promo";

export const promos: Promo[] = [
  {
    code: "WELCOME10",
    title: "A clean start for first orders",
    description: "10% off the first basket with no minimum spend.",
    discountType: "percentage",
    value: 10,
    badge: "New guest",
  },
  {
    code: "DINNER8",
    title: "A small evening nudge",
    description: "$8 off orders above $80.",
    discountType: "fixed",
    value: 8,
    minimumSubtotal: 80,
    badge: "After 5 PM",
  },
  {
    code: "TABLE15",
    title: "Built for fuller tables",
    description: "15% off orders above $120.",
    discountType: "percentage",
    value: 15,
    minimumSubtotal: 120,
    badge: "Group order",
  },
];
