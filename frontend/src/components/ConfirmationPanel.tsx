import type { OrderSummary } from '../contracts/order';
import { formatCurrency } from '../lib/currency';
import { formatFulfillmentLabel, formatOrderStatusLabel } from '../lib/ordering';
import { getPaymentMethodLabel, getPaymentStateLabel } from '../lib/payment';
import { MenuThumbnail } from './MenuThumbnail';

interface ConfirmationPanelProps {
  order: OrderSummary;
  onBackToMenu: () => void;
  onCallWaiter: () => void;
  onRequestBill: () => void;
  onPayAtTableReady: () => void;
  onAdvanceRiderSimulation: () => void;
  onSimulateRefund: (
    type: 'missing_item' | 'wrong_item' | 'late_delivery' | 'partial_refund' | 'store_credit',
  ) => void;
  onSimulateWebhook: (
    type:
      | 'payment.updated'
      | 'order.confirmed'
      | 'rider.assigned'
      | 'rider.location_updated'
      | 'order.delivered'
      | 'refund.updated',
  ) => void;
}

function getConfirmationHeading(fulfillment: OrderSummary['fulfillment']): string {
  if (fulfillment === 'delivery') {
    return 'Your delivery order is safely in.';
  }

  if (fulfillment === 'pickup') {
    return 'Your pickup order is queued with the kitchen.';
  }

  return 'Your table order has been sent to the kitchen.';
}

function getConfirmationReferenceLabel(fulfillment: OrderSummary['fulfillment']): string {
  if (fulfillment === 'delivery') {
    return 'delivery reference';
  }

  if (fulfillment === 'pickup') {
    return 'pickup reference';
  }

  return 'table order reference';
}

function getConfirmationEstimateCopy(order: OrderSummary): string {
  if (order.fulfillment === 'delivery') {
    return `The kitchen estimate is around ${order.estimatedReadyInMinutes} minutes before rider handoff begins.`;
  }

  if (order.fulfillment === 'pickup') {
    return `We estimate around ${order.estimatedReadyInMinutes} minutes before your order is ready at the counter.`;
  }

  const tableReference = order.tableSession?.tableReference ?? 'your table';
  return `We sent it to the kitchen for ${tableReference}, with an estimate of around ${order.estimatedReadyInMinutes} minutes.`;
}

function getConfirmationNextStep(
  order: OrderSummary,
  nextStatuses: OrderSummary['statusFlow'],
): string {
  if (order.fulfillment === 'delivery') {
    return order.riderSimulation
      ? 'Follow the rider simulation below once the kitchen releases the order.'
      : 'The kitchen will prepare the order first, then the delivery simulation can assign a rider.';
  }

  if (order.fulfillment === 'pickup') {
    return 'Head to the pickup counter once the order reaches ready for pickup.';
  }

  if (order.fulfillment === 'dine_in') {
    return order.canAddMoreItems
      ? 'You can add more items from the menu under the same table session, call a waiter, or request the bill from the demo controls.'
      : 'Your table order is active. Use the table service controls when you need help or the bill.';
  }

  return nextStatuses[0]
    ? `Watch for ${formatOrderStatusLabel(nextStatuses[0]).toLowerCase()} in the demo timeline.`
    : 'No additional status steps are available yet.';
}

function getConfirmationFollowUpCopy(fulfillment: OrderSummary['fulfillment']): string {
  if (fulfillment === 'delivery') {
    return 'A delivery confirmation would normally follow by email or SMS with tracking updates and the same order reference.';
  }

  if (fulfillment === 'pickup') {
    return 'A pickup confirmation would normally follow by email or SMS with the same order reference and ready-time update.';
  }

  return 'For dine-in, the table session keeps the order reference available so guests can add items or request the bill later.';
}

export function ConfirmationPanel({
  order,
  onBackToMenu,
  onCallWaiter,
  onRequestBill,
  onPayAtTableReady,
  onAdvanceRiderSimulation,
  onSimulateRefund,
  onSimulateWebhook,
}: ConfirmationPanelProps) {
  const currentStatusIndex = order.statusFlow.indexOf(order.status);
  const nextStatuses =
    currentStatusIndex >= 0
      ? order.statusFlow.slice(currentStatusIndex + 1, currentStatusIndex + 4)
      : [];
  const isDineIn = order.fulfillment === 'dine_in';
  const isDelivery = order.fulfillment === 'delivery';
  const confirmationHeading = getConfirmationHeading(order.fulfillment);
  const confirmationReferenceLabel = getConfirmationReferenceLabel(order.fulfillment);
  const confirmationEstimateCopy = getConfirmationEstimateCopy(order);
  const confirmationNextStep = getConfirmationNextStep(order, nextStatuses);
  const confirmationFollowUpCopy = getConfirmationFollowUpCopy(order.fulfillment);

  return (
    <section className="confirmation-panel">
      <div className="confirmation-panel__card">
        <div className="confirmation-panel__mark" aria-hidden="true">
          ✓
        </div>
        <p className="eyebrow">Order placed</p>
        <h2>{confirmationHeading}</h2>
        <p className="confirmation-panel__copy">
          {order.customerName}, your {confirmationReferenceLabel} is{' '}
          <strong>#{order.publicCode}</strong> (order id <strong>{order.orderId}</strong>).{' '}
          {confirmationEstimateCopy}
        </p>

        <div className="inline-notice confirmation-panel__callout">
          <p>
            <strong>Payment status:</strong>{' '}
            {order.payment ? (
              <>
                {getPaymentMethodLabel(order.payment.method)} ·{' '}
                {getPaymentStateLabel(order.payment.state)}
              </>
            ) : (
              'No payment payload is attached in this local view.'
            )}
          </p>
          <p>
            Demo note: This checkout was completed in safe demo mode. No real payment was charged.
          </p>
          <p>
            <strong>Next step:</strong> {confirmationNextStep}
          </p>
        </div>

        <div className="confirmation-panel__stats">
          <div>
            <span>Status</span>
            <strong>{formatOrderStatusLabel(order.status)}</strong>
          </div>
          <div>
            <span>Fulfillment</span>
            <strong>{formatFulfillmentLabel(order.fulfillment)}</strong>
          </div>
          <div>
            <span>Total</span>
            <strong>{formatCurrency(order.totals.total)}</strong>
          </div>
        </div>

        <div className="confirmation-panel__items">
          {order.items.map((item) => (
            <div key={item.id} className="confirmation-panel__item">
              <MenuThumbnail src={item.image} alt="" className="confirmation-panel__item-thumb" />
              <span className="confirmation-panel__item-main">
                {item.quantity} × {item.name}
              </span>
              <strong>{formatCurrency(item.unitPrice * item.quantity)}</strong>
            </div>
          ))}
        </div>

        <div className="inline-notice">
          <p>{confirmationFollowUpCopy}</p>
        </div>

        <div className="summary-table">
          <div className="summary-table__total">
            <span>Current stage</span>
            <strong>{formatOrderStatusLabel(order.status)}</strong>
          </div>
          {order.payment ? (
            <div>
              <span>Payment</span>
              <strong>
                {getPaymentMethodLabel(order.payment.method)} ·{' '}
                {getPaymentStateLabel(order.payment.state)}
              </strong>
            </div>
          ) : null}
          {nextStatuses.length > 0 ? (
            <>
              <div>
                <span>Next</span>
                <strong>{formatOrderStatusLabel(nextStatuses[0])}</strong>
              </div>
              {nextStatuses.slice(1, 3).map((status) => (
                <div key={status}>
                  <span>Then</span>
                  <strong>{formatOrderStatusLabel(status)}</strong>
                </div>
              ))}
            </>
          ) : (
            <div>
              <span>Next</span>
              <strong>No additional status steps yet</strong>
            </div>
          )}
        </div>

        {order.tableSession ? (
          <div className="summary-table">
            <div className="summary-table__total">
              <span>Table session</span>
              <strong>{order.tableSession.tableReference}</strong>
            </div>
            <div>
              <span>QR session</span>
              <strong>{order.tableSession.qrSessionCode}</strong>
            </div>
            <div>
              <span>Session status</span>
              <strong>{order.tableSession.status}</strong>
            </div>
            <div>
              <span>Add more items</span>
              <strong>
                {order.canAddMoreItems ? 'Available on this table session' : 'Not active'}
              </strong>
            </div>
          </div>
        ) : null}

        {order.splitShares && order.splitShares.length > 0 ? (
          <div className="summary-table">
            <div className="summary-table__total">
              <span>Bill split</span>
              <strong>{order.splitBill?.mode ?? 'none'}</strong>
            </div>
            {order.splitShares.map((share) => (
              <div key={share.participantId}>
                <span>{share.participantName}</span>
                <strong>{formatCurrency(share.amount)}</strong>
              </div>
            ))}
          </div>
        ) : null}

        {order.riderSimulation ? (
          <div className="summary-table">
            <div className="summary-table__total">
              <span>Rider</span>
              <strong>{order.riderSimulation.riderName ?? 'Pending assignment'}</strong>
            </div>
            <div>
              <span>Current rider stage</span>
              <strong>{formatOrderStatusLabel(order.riderSimulation.currentStatus)}</strong>
            </div>
            <div>
              <span>ETA</span>
              <strong>{order.riderSimulation.etaMinutes} min</strong>
            </div>
          </div>
        ) : null}

        {order.refunds && order.refunds.length > 0 ? (
          <div className="summary-table">
            <div className="summary-table__total">
              <span>Refund activity</span>
              <strong>{order.refunds[order.refunds.length - 1]?.state}</strong>
            </div>
            {order.refunds.map((refund, index) => (
              <div key={`${refund.type}-${index}`}>
                <span>{refund.type}</span>
                <strong>{formatCurrency(refund.amount)}</strong>
              </div>
            ))}
          </div>
        ) : null}

        {order.webhookEvents && order.webhookEvents.length > 0 ? (
          <div className="summary-table">
            <div className="summary-table__total">
              <span>Webhook events</span>
              <strong>{order.webhookEvents.length} simulated</strong>
            </div>
            {order.webhookEvents.slice(-3).map((event) => (
              <div key={event.id}>
                <span>{event.type}</span>
                <strong>{event.payloadSummary}</strong>
              </div>
            ))}
          </div>
        ) : null}

        <details className="confirmation-panel__demo-controls">
          <summary className="confirmation-panel__demo-summary">
            <span>
              <strong>Demo controls</strong>
              <small>
                Open safe simulation tools for refund, webhook, rider, and table actions.
              </small>
            </span>
          </summary>

          <div className="confirmation-panel__action-board">
            <div className="confirmation-panel__action-group">
              <div className="confirmation-panel__action-header">
                <strong>Order support</strong>
                <span>Demo resolution tools</span>
              </div>
              <div className="confirmation-panel__action-grid">
                <button
                  type="button"
                  className="button button--secondary"
                  onClick={() => onSimulateRefund('missing_item')}
                >
                  Missing item
                </button>
                <button
                  type="button"
                  className="button button--secondary"
                  onClick={() => onSimulateRefund('wrong_item')}
                >
                  Wrong item
                </button>
                <button
                  type="button"
                  className="button button--secondary"
                  onClick={() => onSimulateRefund('late_delivery')}
                >
                  Late delivery
                </button>
              </div>
            </div>

            <div className="confirmation-panel__action-group">
              <div className="confirmation-panel__action-header">
                <strong>Demo operations</strong>
                <span>Safe backend simulations</span>
              </div>
              <div className="confirmation-panel__action-grid">
                <button
                  type="button"
                  className="button button--secondary"
                  onClick={() => onSimulateRefund('partial_refund')}
                >
                  Partial refund
                </button>
                <button
                  type="button"
                  className="button button--secondary"
                  onClick={() => onSimulateRefund('store_credit')}
                >
                  Store credit
                </button>
                <button
                  type="button"
                  className="button button--secondary"
                  onClick={() => onSimulateWebhook('payment.updated')}
                >
                  Sync payment status
                </button>
              </div>
            </div>

            {isDelivery ? (
              <div className="confirmation-panel__action-group">
                <div className="confirmation-panel__action-header">
                  <strong>Delivery simulation</strong>
                  <span>Rider and tracking tools</span>
                </div>
                <div className="confirmation-panel__action-grid">
                  <button
                    type="button"
                    className="button button--secondary"
                    onClick={onAdvanceRiderSimulation}
                  >
                    Advance rider
                  </button>
                  <button
                    type="button"
                    className="button button--secondary"
                    onClick={() => onSimulateWebhook('rider.location_updated')}
                  >
                    Simulate rider webhook
                  </button>
                </div>
              </div>
            ) : null}

            {isDineIn ? (
              <div className="confirmation-panel__action-group">
                <div className="confirmation-panel__action-header">
                  <strong>Table service</strong>
                  <span>Dine-in service actions</span>
                </div>
                <div className="confirmation-panel__action-grid">
                  <button type="button" className="button button--secondary" onClick={onCallWaiter}>
                    Call waiter
                  </button>
                  <button
                    type="button"
                    className="button button--secondary"
                    onClick={onRequestBill}
                  >
                    Request bill
                  </button>
                  <button
                    type="button"
                    className="button button--secondary"
                    onClick={onPayAtTableReady}
                  >
                    Table payment ready
                  </button>
                </div>
              </div>
            ) : null}
          </div>
        </details>
        <button
          type="button"
          className="button button--primary confirmation-panel__back-button"
          onClick={onBackToMenu}
        >
          Back to menu
        </button>
      </div>
    </section>
  );
}
