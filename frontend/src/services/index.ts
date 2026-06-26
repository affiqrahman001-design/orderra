import { createCartService } from "./cartService";
import { createCatalogService } from "./catalogService";
import { createOperationsService } from "./operationsService";
import { createPaymentService } from "./paymentService";
import { createPromoService } from "./promoService";
import { createOrderService } from "./orderService";
import { createFaqService } from "./faqService";

import { cartLocalAdapter } from "./adapters/local/cartLocalAdapter";
import { catalogLocalAdapter } from "./adapters/local/catalogLocalAdapter";
import { operationsLocalAdapter } from "./adapters/local/operationsLocalAdapter";
import { paymentLocalAdapter } from "./adapters/local/paymentLocalAdapter";
import { promoLocalAdapter } from "./adapters/local/promoLocalAdapter";
import { orderLocalAdapter } from "./adapters/local/orderLocalAdapter";
import { faqLocalAdapter } from "./adapters/local/faqLocalAdapter";

import { cartHttpAdapter } from "./adapters/http/cartHttpAdapter";
import { catalogHttpAdapter } from "./adapters/http/catalogHttpAdapter";
import { operationsHttpAdapter } from "./adapters/http/operationsHttpAdapter";
import { paymentHttpAdapter } from "./adapters/http/paymentHttpAdapter";
import { promoHttpAdapter } from "./adapters/http/promoHttpAdapter";
import { orderHttpAdapter } from "./adapters/http/orderHttpAdapter";
import { faqHttpAdapter } from "./adapters/http/faqHttpAdapter";
import { appConfig } from "../lib/config/env";

export const isApiMode = appConfig.dataMode === "api";

const adapters = isApiMode
  ? {
      cart: cartHttpAdapter,
      catalog: catalogHttpAdapter,
      operations: operationsHttpAdapter,
      payment: paymentHttpAdapter,
      promo: promoHttpAdapter,
      order: orderHttpAdapter,
      faq: faqHttpAdapter,
    }
  : {
      cart: cartLocalAdapter,
      catalog: catalogLocalAdapter,
      operations: operationsLocalAdapter,
      payment: paymentLocalAdapter,
      promo: promoLocalAdapter,
      order: orderLocalAdapter,
      faq: faqLocalAdapter,
    };

export const cartService = createCartService(adapters.cart);
export const catalogService = createCatalogService(adapters.catalog);
export const operationsService = createOperationsService(adapters.operations);
export const paymentService = createPaymentService(adapters.payment);
export const promoService = createPromoService(adapters.promo);
export const orderService = createOrderService(adapters.order);
export const faqService = createFaqService(adapters.faq);
