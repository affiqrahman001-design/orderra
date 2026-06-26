/** Common statuses for filtering; backend accepts any configured order status string. */

export const ORDER_STATUS_FILTER_OPTIONS = [
  { value: "", label: "Any status" },
  { value: "placed", label: "Placed" },
  { value: "confirmed", label: "Confirmed" },
  { value: "preparing", label: "Preparing" },
  { value: "ready", label: "Ready" },
  { value: "awaiting_rider", label: "Awaiting rider" },
  { value: "delivered", label: "Delivered" },
  { value: "completed", label: "Completed" },
  { value: "cancelled", label: "Cancelled" },
];

export const FULFILLMENT_FILTER_OPTIONS = [
  { value: "", label: "Any mode" },
  { value: "delivery", label: "Delivery" },
  { value: "pickup", label: "Pickup" },
  { value: "dine_in", label: "Dine in" },
];

export function humanizeAdminStatus(slug: string): string {
  return slug.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
}
