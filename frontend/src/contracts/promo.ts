export interface Promo {
  code: string;
  title: string;
  description: string;
  discountType: "percentage" | "fixed";
  value: number;
  minimumSubtotal?: number;
  badge?: string;
}

export interface PromoApplication {
  code: string;
  description: string;
  amount: number;
}

export interface PromoValidationResult {
  valid: boolean;
  message: string;
  appliedPromo?: PromoApplication;
}
