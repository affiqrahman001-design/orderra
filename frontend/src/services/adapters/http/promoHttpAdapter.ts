import type { PromoAdapter } from "../../promoService";
import type { Promo, PromoValidationResult } from "../../../contracts/promo";
import { apiRequest } from "../../../lib/api/client";

type BackendEnvelope<T> = { data: T; message?: string };

type BackendPromo = {
  id?: string;
  code: string;
  title?: string;
  description?: string;
  discount_type?: "percentage" | "fixed";
  discount_value?: number;
  active?: boolean;
};

function mapPromo(promo: BackendPromo): Promo {
  return {
    code: promo.code,
    title: promo.title ?? promo.code,
    description: promo.description ?? "ORDERra demo promotion.",
    discountType: promo.discount_type ?? "fixed",
    value: promo.discount_value ?? 0,
  };
}

export const promoHttpAdapter: PromoAdapter = {
  async listPromos() {
    const response = await apiRequest<BackendEnvelope<BackendPromo[]>>("/promos");
    return response.data.map(mapPromo);
  },

  async validateCode(code, subtotal): Promise<PromoValidationResult> {
    const response = await apiRequest<BackendEnvelope<PromoValidationResult>>("/promos/validate", {
      method: "POST",
      body: JSON.stringify({ code, subtotal }),
    });
    return response.data;
  },
};
