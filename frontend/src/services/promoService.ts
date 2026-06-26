import type { Promo, PromoValidationResult } from "../contracts/promo";

export interface PromoAdapter {
  listPromos(): Promise<Promo[]>;
  validateCode(code: string, subtotal: number): Promise<PromoValidationResult>;
}

export interface PromoService {
  listPromos(): Promise<Promo[]>;
  validateCode(code: string, subtotal: number): Promise<PromoValidationResult>;
}

export function createPromoService(adapter: PromoAdapter): PromoService {
  return {
    listPromos: () => adapter.listPromos(),
    validateCode: (code, subtotal) => adapter.validateCode(code, subtotal),
  };
}
