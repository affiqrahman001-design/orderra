import { formatCurrency } from "../../../lib/currency";
import type { PromoAdapter } from "../../promoService";
import type { PromoApplication, PromoValidationResult } from "../../../contracts/promo";
import { promos } from "../../../data/promos";

function createAppliedPromoAmount(
  discountType: "percentage" | "fixed",
  value: number,
  subtotal: number,
): number {
  if (discountType === "percentage") {
    return Math.round((subtotal * value) / 100);
  }

  return value;
}

export const promoLocalAdapter: PromoAdapter = {
  async listPromos() {
    return promos;
  },
  async validateCode(code, subtotal) {
    const cleanCode = code.trim().toUpperCase();

    if (!cleanCode) {
      return {
        valid: false,
        message: "Enter a promo code before applying.",
      };
    }

    const promo = promos.find((entry) => entry.code === cleanCode);

    if (!promo) {
      return {
        valid: false,
        message: "That code is not active in this build.",
      };
    }

    if (promo.minimumSubtotal && subtotal < promo.minimumSubtotal) {
      return {
        valid: false,
        message: `This code starts from ${formatCurrency(promo.minimumSubtotal)}.`,
      };
    }

    const amount = createAppliedPromoAmount(promo.discountType, promo.value, subtotal);

    const appliedPromo: PromoApplication = {
      code: promo.code,
      description: promo.title,
      amount,
    };

    const result: PromoValidationResult = {
      valid: true,
      message: `${promo.code} applied successfully.`,
      appliedPromo,
    };

    return result;
  },
};
