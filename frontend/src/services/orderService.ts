import type { CreateOrderInput, OrderSummary } from "../contracts/order";

export interface OrderAdapter {
  createOrder(input: CreateOrderInput): Promise<OrderSummary>;
  getOrderById(orderId: string): Promise<OrderSummary | null>;
}

export interface OrderService {
  createOrder(input: CreateOrderInput): Promise<OrderSummary>;
  getOrderById(orderId: string): Promise<OrderSummary | null>;
}

export function createOrderService(adapter: OrderAdapter): OrderService {
  return {
    createOrder: (input) => adapter.createOrder(input),
    getOrderById: (orderId) => adapter.getOrderById(orderId),
  };
}
