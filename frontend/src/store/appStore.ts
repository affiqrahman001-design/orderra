import { create } from 'zustand';
import type { Category } from '../contracts/category';
import type { FaqItem } from '../contracts/faq';
import type { SupportCompensationType, WebhookSimulationEvent } from '../contracts/operations';
import type {
  CartLine,
  CheckoutForm,
  OrderSummary,
  SelectedOption,
  TableQrSession,
  TotalsSnapshot,
} from '../contracts/order';
import type { PaymentSimulationResult } from '../contracts/payment';
import type { Product } from '../contracts/product';
import type { Promo, PromoApplication } from '../contracts/promo';
import { getDefaultRefundDecision } from '../lib/operations';
import type { CheckoutErrors } from '../lib/ordering';
import {
  createDefaultSplitParticipants,
  ensureSplitParticipants,
  markBillRequested,
  markPayAtTableReady,
  markWaiterCalled,
  validateCheckoutForm,
} from '../lib/ordering';
import { canCreateOrderFromPaymentState } from '../lib/payment';
import { getCartItemCount, getSubtotal, getTotals } from '../lib/pricing';
import {
  cartService,
  catalogService,
  faqService,
  isApiMode,
  operationsService,
  orderService,
  paymentService,
  promoService,
} from '../services';
import {
  clearStoredCartToken,
  getStoredCartToken,
  shouldResetCartTokenAfterApiError,
} from '../services/adapters/http/cartHttpAdapter';
import {
  type JoinTableQrSummary,
  attachCartToGuestQrSession,
  buildJoinTableQrSummary,
  fetchGuestQrSessionByCode,
  mapGuestQrSessionDto,
} from '../services/dineInGuestService';

const cartPersistenceKey = 'orderra_frontend_cart_v2';

type View = 'catalog' | 'checkout' | 'confirmation';

type PersistedCartState = {
  cartLines: CartLine[];
  appliedPromo: PromoApplication | null;
  promoInput: string;
  checkout: CheckoutForm;
  activeTableSession: TableQrSession | null;
};

function createDefaultCheckout(): CheckoutForm {
  return {
    name: '',
    email: '',
    phone: '',
    fulfillment: 'delivery',
    address: '',
    city: '',
    postalCode: '',
    tableReference: '',
    paymentMethod: 'card',
    paymentSimulationOutcome: 'success',
    deliveryWindow: 'As soon as possible',
    contactless: false,
    splitBillMode: 'none',
    splitParticipantCount: 1,
    splitParticipants: createDefaultSplitParticipants(),
    primaryPayerName: '',
  };
}

function readPersistedCart(): PersistedCartState | null {
  if (typeof window === 'undefined') return null;
  try {
    const raw = window.localStorage.getItem(cartPersistenceKey);
    if (!raw) return null;
    return JSON.parse(raw) as PersistedCartState;
  } catch {
    return null;
  }
}

function writePersistedCart(
  state: Pick<
    PersistedCartState,
    'cartLines' | 'appliedPromo' | 'promoInput' | 'checkout' | 'activeTableSession'
  >,
): void {
  if (typeof window === 'undefined') return;
  window.localStorage.setItem(cartPersistenceKey, JSON.stringify(state));
}

function removePersistedCart(): void {
  if (typeof window === 'undefined') return;
  window.localStorage.removeItem(cartPersistenceKey);
}

function createLineId(productId: string): string {
  if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) return crypto.randomUUID();
  return `${productId}-${Date.now()}`;
}

function createLocalCartLine(
  product: Product,
  selections: SelectedOption[],
  note = '',
  quantity = 1,
): CartLine {
  const unitPrice =
    product.price + selections.reduce((sum, selection) => sum + selection.priceDelta, 0);
  return {
    id: createLineId(product.id),
    productId: product.id,
    name: product.name,
    image: product.image,
    quantity,
    basePrice: product.price,
    unitPrice,
    flow: product.flow,
    selections,
    note: note.trim() || undefined,
  };
}

function buildCustomerContext(checkout: CheckoutForm): Record<string, unknown> {
  return {
    name: checkout.name,
    email: checkout.email,
    phone: checkout.phone,
  };
}

function buildFulfillmentContext(checkout: CheckoutForm): Record<string, unknown> {
  if (checkout.fulfillment === 'delivery') {
    return {
      address_line1: checkout.address,
      city: checkout.city,
      state: 'NY',
      postal_code: checkout.postalCode,
      country_code: 'US',
      delivery_window: checkout.deliveryWindow,
      contactless: checkout.contactless,
      zone_code: 'zone-a',
      distance_meters: 3200,
    };
  }

  if (checkout.fulfillment === 'dine_in') {
    return {
      table_label: checkout.tableReference,
      table_reference: checkout.tableReference,
      party_size: Math.max(1, checkout.splitParticipantCount || 1),
      split_bill_mode: checkout.splitBillMode,
      split_participant_count: checkout.splitParticipantCount,
      split_participants: checkout.splitParticipants,
    };
  }

  return {
    pickup_window: checkout.deliveryWindow,
    branch_code: 'MAIN',
  };
}

const persisted = readPersistedCart();

interface AppState {
  initialized: boolean;
  loading: boolean;
  catalogError: string | null;
  categories: Category[];
  products: Product[];
  promos: Promo[];
  faqs: FaqItem[];
  searchQuery: string;
  selectedCategoryId: string;
  activeProduct: Product | null;
  cartOpen: boolean;
  cartLines: CartLine[];
  cartError: string | null;
  serverTotals: TotalsSnapshot | null;
  appliedPromo: PromoApplication | null;
  promoInput: string;
  promoMessage: string | null;
  checkout: CheckoutForm;
  checkoutErrors: CheckoutErrors;
  currentView: View;
  latestOrder: OrderSummary | null;
  orderSubmitting: boolean;
  orderError: string | null;
  latestPayment: PaymentSimulationResult | null;
  activeTableSession: TableQrSession | null;
  initialize: () => Promise<void>;
  setSearchQuery: (query: string) => void;
  setSelectedCategoryId: (categoryId: string) => void;
  setActiveProduct: (product: Product | null) => void;
  setCartOpen: (open: boolean) => void;
  addProductToCart: (
    product: Product,
    selections: SelectedOption[],
    note?: string,
    quantity?: number,
  ) => Promise<void>;
  updateCartLineQuantity: (lineId: string, quantity: number) => Promise<void>;
  removeCartLine: (lineId: string) => Promise<void>;
  setPromoInput: (value: string) => void;
  applyPromo: () => Promise<void>;
  clearPromo: () => Promise<void>;
  setCheckoutField: <K extends keyof CheckoutForm>(field: K, value: CheckoutForm[K]) => void;
  setSplitParticipantCount: (count: number) => void;
  setSplitParticipantName: (participantId: string, name: string) => void;
  toggleSplitItemAssignment: (participantId: string, lineId: string) => void;
  validateCheckout: () => boolean;
  goToCheckout: () => void;
  goToCatalog: () => void;
  refreshPricingQuote: () => Promise<TotalsSnapshot>;
  placeOrder: () => Promise<void>;
  callWaiter: () => void;
  requestBill: () => void;
  setPayAtTableReady: () => void;
  simulateRefund: (type: SupportCompensationType) => Promise<void>;
  simulateWebhook: (type: WebhookSimulationEvent['type']) => Promise<void>;
  advanceRiderSimulation: () => Promise<void>;
  resetOrderFlow: () => void;
  joinTableFromQrCode: (sessionCode: string) => Promise<JoinTableQrSummary>;
}

function persistCartState(state: AppState): void {
  writePersistedCart({
    cartLines: state.cartLines,
    appliedPromo: state.appliedPromo,
    promoInput: state.promoInput,
    checkout: state.checkout,
    activeTableSession: state.activeTableSession,
  });
}

export const useAppStore = create<AppState>((set, get) => ({
  initialized: false,
  loading: false,
  catalogError: null,
  categories: [],
  products: [],
  promos: [],
  faqs: [],
  searchQuery: '',
  selectedCategoryId: 'all',
  activeProduct: null,
  cartOpen: false,
  cartLines: persisted?.cartLines ?? [],
  cartError: null,
  serverTotals: null,
  appliedPromo: persisted?.appliedPromo ?? null,
  promoInput: persisted?.promoInput ?? '',
  promoMessage: null,
  checkout: persisted?.checkout ?? createDefaultCheckout(),
  checkoutErrors: {},
  currentView: 'catalog',
  latestOrder: null,
  orderSubmitting: false,
  orderError: null,
  latestPayment: null,
  activeTableSession: persisted?.activeTableSession ?? null,

  async initialize() {
    if (get().initialized) return;
    set({ loading: true, catalogError: null });

    try {
      const [categories, products, promos, faqs] = await Promise.all([
        catalogService.listCategories(),
        catalogService.listProducts(),
        promoService.listPromos(),
        faqService.listFaqs(),
      ]);

      let cartLines = get().cartLines;
      let serverTotals = get().serverTotals;
      let cartError: string | null = null;

      if (isApiMode) {
        try {
          const cart = await cartService.getCart();
          cartLines = cart.lines;
          serverTotals = cart.totals;
        } catch (error) {
          if (shouldResetCartTokenAfterApiError(error)) {
            clearStoredCartToken();
            removePersistedCart();
            try {
              const cart = await cartService.getCart();
              cartLines = cart.lines;
              serverTotals = cart.totals;
              cartError = null;
            } catch (retryErr) {
              cartError =
                retryErr instanceof Error ? retryErr.message : 'Cart could not be loaded from API.';
            }
          } else {
            cartError =
              error instanceof Error ? error.message : 'Cart could not be loaded from API.';
          }
        }
      }

      set({
        initialized: true,
        loading: false,
        categories,
        products,
        promos,
        faqs,
        cartLines,
        serverTotals,
        cartError,
      });
    } catch (error) {
      set({
        loading: false,
        catalogError:
          error instanceof Error ? error.message : 'Something interrupted the catalog load.',
      });
    }
  },

  setSearchQuery(query) {
    set({ searchQuery: query });
  },
  setSelectedCategoryId(categoryId) {
    set({ selectedCategoryId: categoryId });
  },
  setActiveProduct(product) {
    set({ activeProduct: product });
  },
  setCartOpen(open) {
    set({ cartOpen: open });
  },

  async addProductToCart(product, selections, note = '', quantity = 1) {
    if (isApiMode) {
      set({ cartError: null });
      try {
        const cart = await cartService.addLine({
          menuItemId: product.id,
          quantity,
          note,
          selectedModifiers: selections,
        });
        set({
          cartLines: cart.lines,
          serverTotals: cart.totals,
          activeProduct: null,
          cartOpen: true,
          appliedPromo: null,
          promoMessage: null,
        });
        persistCartState(get());
        return;
      } catch (error) {
        set({
          cartError: error instanceof Error ? error.message : 'Item could not be added to cart.',
        });
      }
    }

    const line = createLocalCartLine(product, selections, note, quantity);
    set((state) => ({
      cartLines: [...state.cartLines, line],
      activeProduct: null,
      cartOpen: true,
      appliedPromo: null,
      serverTotals: null,
      promoMessage: state.appliedPromo
        ? 'Basket updated. Reapply your promo to refresh the total.'
        : null,
    }));
    persistCartState(get());
  },

  async updateCartLineQuantity(lineId, quantity) {
    if (quantity <= 0) {
      await get().removeCartLine(lineId);
      return;
    }

    if (isApiMode) {
      try {
        const cart = await cartService.updateLine(lineId, quantity);
        set({
          cartLines: cart.lines,
          serverTotals: cart.totals,
          appliedPromo: null,
          promoMessage: null,
          cartError: null,
        });
        persistCartState(get());
        return;
      } catch (error) {
        set({
          cartError: error instanceof Error ? error.message : 'Quantity could not be updated.',
        });
        return;
      }
    }

    set((state) => ({
      cartLines: state.cartLines.map((line) => (line.id === lineId ? { ...line, quantity } : line)),
      appliedPromo: null,
      serverTotals: null,
      promoMessage: state.appliedPromo
        ? 'Basket updated. Reapply your promo to refresh the total.'
        : null,
    }));
    persistCartState(get());
  },

  async removeCartLine(lineId) {
    if (isApiMode) {
      try {
        const cart = await cartService.removeLine(lineId);
        set({
          cartLines: cart.lines,
          serverTotals: cart.totals,
          appliedPromo: null,
          promoMessage: null,
          cartError: null,
        });
        persistCartState(get());
        return;
      } catch (error) {
        set({ cartError: error instanceof Error ? error.message : 'Item could not be removed.' });
        return;
      }
    }

    set((state) => ({
      cartLines: state.cartLines.filter((line) => line.id !== lineId),
      appliedPromo: null,
      serverTotals: null,
      promoMessage: state.appliedPromo
        ? 'Basket updated. Reapply your promo to refresh the total.'
        : null,
    }));
    persistCartState(get());
  },

  setPromoInput(value) {
    set({ promoInput: value, promoMessage: null });
  },

  async applyPromo() {
    const subtotal = getSubtotal(get().cartLines);
    const code = get().promoInput.trim();
    if (subtotal <= 0) {
      set({ appliedPromo: null, promoMessage: 'Add at least one item before applying a code.' });
      return;
    }

    try {
      const result = await promoService.validateCode(code, subtotal);
      let serverTotals = get().serverTotals;
      if (isApiMode && result.valid) {
        const cart = await cartService.updatePromo(code);
        serverTotals = cart.totals;
      }
      set({
        appliedPromo: result.valid ? (result.appliedPromo ?? null) : null,
        promoMessage: result.message,
        serverTotals,
      });
      persistCartState(get());
    } catch (error) {
      set({
        appliedPromo: null,
        promoMessage:
          error instanceof Error ? error.message : 'Promo validation could not be completed.',
      });
    }
  },

  async clearPromo() {
    try {
      if (isApiMode) {
        const cart = await cartService.updatePromo(null);
        set({ serverTotals: cart.totals });
      }
      set({ appliedPromo: null, promoInput: '', promoMessage: null });
      persistCartState(get());
    } catch (error) {
      set({ promoMessage: error instanceof Error ? error.message : 'Promo could not be removed.' });
    }
  },

  setCheckoutField(field, value) {
    set((state) => {
      const nextCheckout: CheckoutForm = { ...state.checkout, [field]: value };

      if (field === 'fulfillment') {
        if (value !== 'delivery') {
          nextCheckout.address = '';
          nextCheckout.city = '';
          nextCheckout.postalCode = '';
          nextCheckout.contactless = false;
        }
        if (value !== 'dine_in') {
          nextCheckout.tableReference = '';
          nextCheckout.splitBillMode = 'none';
          nextCheckout.splitParticipantCount = 1;
          nextCheckout.splitParticipants = ensureSplitParticipants(
            1,
            nextCheckout.splitParticipants,
            nextCheckout.primaryPayerName,
          );
        } else if (state.activeTableSession) {
          nextCheckout.tableReference = state.activeTableSession.tableReference;
        }
      }

      if (field === 'name' || field === 'primaryPayerName') {
        nextCheckout.splitParticipants = ensureSplitParticipants(
          nextCheckout.splitParticipantCount,
          nextCheckout.splitParticipants,
          field === 'primaryPayerName' ? String(value) : nextCheckout.primaryPayerName,
        );
      }

      if (field === 'splitBillMode') {
        const nextSplitBillMode = value as CheckoutForm['splitBillMode'];

        if (nextSplitBillMode === 'none') {
          nextCheckout.splitParticipantCount = 1;
          nextCheckout.splitParticipants = ensureSplitParticipants(
            1,
            nextCheckout.splitParticipants,
            nextCheckout.primaryPayerName,
          );
        } else {
          const nextParticipantCount = Math.max(2, nextCheckout.splitParticipantCount || 2);

          nextCheckout.splitParticipantCount = nextParticipantCount;
          nextCheckout.splitParticipants = ensureSplitParticipants(
            nextParticipantCount,
            nextCheckout.splitParticipants,
            nextCheckout.primaryPayerName || nextCheckout.name,
          );
        }
      }

      const nextErrors = { ...state.checkoutErrors };
      if (field in nextErrors) delete nextErrors[field as keyof CheckoutErrors];
      if (field === 'fulfillment') {
        delete nextErrors.address;
        delete nextErrors.city;
        delete nextErrors.postalCode;
        delete nextErrors.tableReference;
      }

      return {
        checkout: nextCheckout,
        checkoutErrors: nextErrors,
        orderError: null,
        latestPayment: null,
        serverTotals: null,
      };
    });
    persistCartState(get());
  },

  setSplitParticipantCount(count) {
    set((state) => ({
      checkout: {
        ...state.checkout,
        splitParticipantCount: Math.max(1, count),
        splitParticipants: ensureSplitParticipants(
          count,
          state.checkout.splitParticipants,
          state.checkout.primaryPayerName,
        ),
      },
    }));
    persistCartState(get());
  },

  setSplitParticipantName(participantId, name) {
    set((state) => ({
      checkout: {
        ...state.checkout,
        splitParticipants: state.checkout.splitParticipants.map((participant) =>
          participant.id === participantId ? { ...participant, name } : participant,
        ),
      },
    }));
    persistCartState(get());
  },

  toggleSplitItemAssignment(participantId, lineId) {
    set((state) => ({
      checkout: {
        ...state.checkout,
        splitParticipants: state.checkout.splitParticipants.map((participant) => {
          if (participant.id !== participantId)
            return {
              ...participant,
              itemLineIds: participant.itemLineIds.filter(
                (assignedLineId) => assignedLineId !== lineId,
              ),
            };
          const alreadyAssigned = participant.itemLineIds.includes(lineId);
          return {
            ...participant,
            itemLineIds: alreadyAssigned
              ? participant.itemLineIds.filter((assignedLineId) => assignedLineId !== lineId)
              : [...participant.itemLineIds, lineId],
          };
        }),
      },
    }));
    persistCartState(get());
  },

  validateCheckout() {
    const checkoutErrors = validateCheckoutForm(get().checkout);
    set({ checkoutErrors });
    return Object.keys(checkoutErrors).length === 0;
  },

  goToCheckout() {
    if (getCartItemCount(get().cartLines) === 0) return;
    set({
      currentView: 'checkout',
      cartOpen: false,
      activeProduct: null,
      orderError: null,
      latestPayment: null,
    });
  },

  goToCatalog() {
    set({ currentView: 'catalog', orderError: null, latestPayment: null });
  },

  async refreshPricingQuote() {
    const state = get();
    if (!isApiMode)
      return getTotals(state.cartLines, state.checkout.fulfillment, state.appliedPromo);

    const cart = await cartService.quote({
      fulfillmentType: state.checkout.fulfillment,
      customerContext: buildCustomerContext(state.checkout),
      fulfillmentContext: buildFulfillmentContext(state.checkout),
      promoCode: state.appliedPromo?.code ?? null,
    });
    set({ cartLines: cart.lines, serverTotals: cart.totals, cartError: null });
    persistCartState(get());
    return cart.totals;
  },

  async placeOrder() {
    if (get().orderSubmitting) return;
    if (!get().validateCheckout()) {
      set({ orderError: 'Complete the required fulfillment details before placing the order.' });
      return;
    }

    set({ orderSubmitting: true, orderError: null, latestPayment: null });

    try {
      const state = get();
      let totals = await get().refreshPricingQuote();

      if (isApiMode) {
        const cart = await cartService.updateFulfillment({
          fulfillmentType: state.checkout.fulfillment,
          customerContext: buildCustomerContext(state.checkout),
          fulfillmentContext: buildFulfillmentContext(state.checkout),
        });
        totals = cart.totals;
        set({ cartLines: cart.lines, serverTotals: cart.totals });
      }

      const payment = await paymentService.simulatePayment({
        method: state.checkout.paymentMethod,
        amount: totals.total,
        currency: 'USD',
        reference: `CHK-${Date.now()}`,
        simulationOutcome: state.checkout.paymentSimulationOutcome,
      });

      if (!canCreateOrderFromPaymentState(payment.state)) {
        set({ orderSubmitting: false, latestPayment: payment, orderError: payment.message });
        return;
      }

      const nextState = get();
      const order = await orderService.createOrder({
        cartLines: nextState.cartLines,
        checkout: nextState.checkout,
        promoCode: nextState.appliedPromo?.code,
        totals,
        payment,
        existingTableSessionId:
          nextState.checkout.fulfillment === 'dine_in'
            ? nextState.activeTableSession?.sessionId
            : undefined,
      });

      set({
        latestOrder: { ...order, payment, paymentState: payment.state },
        currentView: 'confirmation',
        cartLines: [],
        serverTotals: null,
        appliedPromo: null,
        promoInput: '',
        promoMessage: null,
        checkout: createDefaultCheckout(),
        checkoutErrors: {},
        cartOpen: false,
        orderSubmitting: false,
        orderError: null,
        latestPayment: payment,
        activeTableSession: order.tableSession ?? nextState.activeTableSession,
      });
      removePersistedCart();
      clearStoredCartToken();
    } catch (error) {
      if (isApiMode && shouldResetCartTokenAfterApiError(error)) {
        clearStoredCartToken();
        removePersistedCart();
        set({
          orderSubmitting: false,
          cartLines: [],
          serverTotals: null,
          appliedPromo: null,
          promoInput: '',
          promoMessage: null,
          currentView: 'catalog',
          checkout: createDefaultCheckout(),
          checkoutErrors: {},
          cartOpen: false,
          orderError:
            'This checkout session is no longer active. Your items were cleared—add them again to order.',
        });
        return;
      }
      set({
        orderSubmitting: false,
        orderError:
          error instanceof Error
            ? error.message
            : 'The order could not be placed in this demo flow.',
      });
    }
  },

  callWaiter() {
    set((state) => {
      if (!state.activeTableSession) return {};
      const nextSession = markWaiterCalled(state.activeTableSession);
      return {
        activeTableSession: nextSession,
        latestOrder: state.latestOrder
          ? { ...state.latestOrder, tableSession: nextSession }
          : state.latestOrder,
      };
    });
    persistCartState(get());
  },

  requestBill() {
    set((state) => {
      if (!state.activeTableSession) return {};
      const nextSession = markBillRequested(state.activeTableSession);
      return {
        activeTableSession: nextSession,
        latestOrder: state.latestOrder
          ? { ...state.latestOrder, status: 'bill_requested', tableSession: nextSession }
          : state.latestOrder,
      };
    });
    persistCartState(get());
  },

  setPayAtTableReady() {
    set((state) => {
      if (!state.activeTableSession) return {};
      const nextSession = markPayAtTableReady(state.activeTableSession);
      return {
        activeTableSession: nextSession,
        latestOrder: state.latestOrder
          ? { ...state.latestOrder, status: 'paid_at_table', tableSession: nextSession }
          : state.latestOrder,
      };
    });
    persistCartState(get());
  },

  async simulateRefund(type) {
    const order = get().latestOrder;
    if (!order) return;
    set({ orderError: null });
    try {
      const updatedOrder = await operationsService.simulateRefund(
        order,
        getDefaultRefundDecision(type, order),
      );
      set({ latestOrder: updatedOrder });
    } catch (error) {
      set({
        orderError:
          error instanceof Error
            ? error.message
            : 'Refund simulation could not be completed in this demo flow.',
      });
    }
  },

  async simulateWebhook(type) {
    const order = get().latestOrder;
    if (!order) return;
    try {
      const updatedOrder = await operationsService.simulateWebhook(
        order,
        type,
        `${type} triggered from the frontend demo simulation layer.`,
      );
      set({ latestOrder: updatedOrder });
    } catch (error) {
      set({
        orderError:
          error instanceof Error
            ? error.message
            : 'Webhook simulation could not be completed in this demo flow.',
      });
    }
  },

  async advanceRiderSimulation() {
    const order = get().latestOrder;
    if (!order || order.fulfillment !== 'delivery') return;
    try {
      const updatedOrder = await operationsService.simulateRiderProgress(order);
      set({ latestOrder: updatedOrder });
    } catch (error) {
      set({
        orderError:
          error instanceof Error
            ? error.message
            : 'Rider simulation could not be completed in this demo flow.',
      });
    }
  },

  resetOrderFlow() {
    set({
      currentView: 'catalog',
      latestOrder: null,
      activeProduct: null,
      cartOpen: false,
      orderSubmitting: false,
      orderError: null,
      latestPayment: null,
    });
  },

  async joinTableFromQrCode(sessionCode: string): Promise<JoinTableQrSummary> {
    const dto = await fetchGuestQrSessionByCode(sessionCode);
    const mapped = mapGuestQrSessionDto(dto);

    set((state) => ({
      activeTableSession: mapped,
      checkout: {
        ...state.checkout,
        fulfillment: 'dine_in',
        tableReference: mapped.tableReference,
      },
      orderError: null,
    }));
    persistCartState(get());

    if (isApiMode) {
      const token = getStoredCartToken();
      if (token && get().cartLines.length > 0) {
        try {
          await attachCartToGuestQrSession(dto.id, token);
          const cart = await cartService.getCart();
          set({ cartLines: cart.lines, serverTotals: cart.totals, cartError: null });
          persistCartState(get());
        } catch {
          set({
            cartError:
              'Table session resolved, but items could not be linked. Return to checkout and retry.',
          });
        }
      }
    }

    return buildJoinTableQrSummary(dto, mapped);
  },
}));
