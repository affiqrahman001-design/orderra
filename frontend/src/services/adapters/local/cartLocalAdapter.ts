import type { CartAdapter, CartView } from "../../cartService";
import type { CartLine, FulfillmentMethod } from "../../../contracts/order";
import { getTotals } from "../../../lib/pricing";

let lines: CartLine[] = [];
let fulfillment: FulfillmentMethod = "delivery";
let promoCode: string | null = null;

function snapshot(): CartView {
  return {
    cartToken: null,
    fulfillment,
    lines,
    totals: getTotals(lines, fulfillment),
    promoCode,
  };
}

export const cartLocalAdapter: CartAdapter = {
  async getCart() {
    return snapshot();
  },

  async addLine() {
    return snapshot();
  },

  async updateLine(lineId, quantity) {
    lines = lines.map((line) => (line.id === lineId ? { ...line, quantity } : line));
    return snapshot();
  },

  async removeLine(lineId) {
    lines = lines.filter((line) => line.id !== lineId);
    return snapshot();
  },

  async updateFulfillment(input) {
    fulfillment = input.fulfillmentType;
    return snapshot();
  },

  async updatePromo(nextPromoCode) {
    promoCode = nextPromoCode;
    return snapshot();
  },

  async quote(input) {
    if (input.fulfillmentType) fulfillment = input.fulfillmentType;
    promoCode = input.promoCode ?? promoCode;
    return snapshot();
  },
};
