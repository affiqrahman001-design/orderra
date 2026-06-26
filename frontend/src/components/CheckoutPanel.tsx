import { useEffect } from 'react';
import type { CartLine, CheckoutForm, TotalsSnapshot } from '../contracts/order';
import type { PaymentSimulationResult } from '../contracts/payment';
import { formatCurrency } from '../lib/currency';
import type { CheckoutErrors } from '../lib/ordering';
import { getFulfillmentSummaryLabel } from '../lib/ordering';
import { getPaymentMethodLabel, getPaymentStateLabel } from '../lib/payment';
import { MenuThumbnail } from './MenuThumbnail';

const supportedPaymentMethods: CheckoutForm['paymentMethod'][] = [
  'card',
  'cash',
  'apple_pay',
  'google_pay',
  'ach',
  'paypal',
];

function getCartLineTotal(line: CartLine): number {
  return line.unitPrice * line.quantity;
}

function getSplitParticipantName(
  checkout: CheckoutForm,
  participantIndex: number,
  fallback: string,
): string {
  const participant = checkout.splitParticipants[participantIndex];
  return participant?.name.trim() || fallback;
}

interface CheckoutPanelProps {
  checkout: CheckoutForm;
  checkoutErrors: CheckoutErrors;
  totals: TotalsSnapshot;
  cartLines: CartLine[];
  orderError: string | null;
  orderSubmitting: boolean;
  paymentResult: PaymentSimulationResult | null;
  activeTableSessionId?: string | null;
  onChange: <K extends keyof CheckoutForm>(field: K, value: CheckoutForm[K]) => void;
  onSplitParticipantCountChange: (count: number) => void;
  onSplitParticipantNameChange: (participantId: string, name: string) => void;
  onToggleSplitItemAssignment: (participantId: string, lineId: string) => void;
  onBack: () => void;
  onSubmit: () => void;
}

export function CheckoutPanel({
  checkout,
  checkoutErrors,
  totals,
  cartLines,
  orderError,
  orderSubmitting,
  paymentResult,
  activeTableSessionId,
  onChange,
  onSplitParticipantCountChange,
  onSplitParticipantNameChange,
  onToggleSplitItemAssignment,
  onBack,
  onSubmit,
}: CheckoutPanelProps) {
  const isDelivery = checkout.fulfillment === 'delivery';
  const isPickup = checkout.fulfillment === 'pickup';
  const isDineIn = checkout.fulfillment === 'dine_in';
  const fulfillmentSummaryLabel = getFulfillmentSummaryLabel(checkout.fulfillment);
  const primarySplitParticipantName = getSplitParticipantName(
    checkout,
    0,
    checkout.primaryPayerName.trim() || checkout.name.trim() || 'Guest 1',
  );
  const byItemAssignedLineIds = new Set(
    checkout.splitParticipants.flatMap((participant) => participant.itemLineIds),
  );
  const byItemAssignedSubtotal = cartLines.reduce(
    (sum, line) => sum + (byItemAssignedLineIds.has(line.id) ? getCartLineTotal(line) : 0),
    0,
  );
  const byItemUnassignedLineCount = cartLines.filter(
    (line) => !byItemAssignedLineIds.has(line.id),
  ).length;
  const splitParticipantCount = Math.max(1, checkout.splitParticipants.length);
  const equalBaseShare = Math.floor(totals.total / splitParticipantCount);
  const equalRemainder = totals.total - equalBaseShare * splitParticipantCount;
  const splitPreviewRows =
    checkout.splitBillMode === 'none'
      ? []
      : checkout.splitParticipants.map((participant, index) => {
          if (checkout.splitBillMode === 'equal') {
            return {
              id: participant.id,
              name: participant.name.trim() || `Guest ${index + 1}`,
              amount: equalBaseShare + (index === 0 ? equalRemainder : 0),
            };
          }

          const assignedItemTotal = participant.itemLineIds.reduce((sum, lineId) => {
            const line = cartLines.find((entry) => entry.id === lineId);
            return sum + (line ? getCartLineTotal(line) : 0);
          }, 0);
          const sharedRemainder = Math.max(0, totals.total - byItemAssignedSubtotal);

          return {
            id: participant.id,
            name: participant.name.trim() || `Guest ${index + 1}`,
            amount: assignedItemTotal + (index === 0 ? sharedRemainder : 0),
          };
        });
  const isPendingPaymentNotice =
    paymentResult?.state === 'pending' && orderError === paymentResult.message;
  const orderErrorNoticeClassName = isPendingPaymentNotice
    ? 'inline-notice'
    : 'inline-notice inline-notice--error';
  useEffect(() => {
    const firstError = document.querySelector<HTMLElement>('.checkout-layout .field-error');
    if (!firstError) return;

    const field = firstError.closest<HTMLElement>('.field');
    field?.scrollIntoView({ behavior: 'smooth', block: 'center' });

    const input = field?.querySelector<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>(
      'input, select, textarea',
    );
    window.setTimeout(() => input?.focus({ preventScroll: true }), 250);
  }, [checkoutErrors]);
  return (
    <section className="checkout-layout">
      <div className="checkout-layout__main">
        <div className="section-heading">
          <p className="eyebrow">Checkout</p>
          <h2>Order details and payment</h2>
          <p>
            Add the essentials, choose delivery, pickup, or dine in, then review the total before
            placing the order.
          </p>
        </div>

        <div className="form-card">
          <div className="toggle-pills">
            <button
              type="button"
              className={isDelivery ? 'toggle-pill is-active' : 'toggle-pill'}
              onClick={() => onChange('fulfillment', 'delivery')}
            >
              Delivery
            </button>
            <button
              type="button"
              className={isPickup ? 'toggle-pill is-active' : 'toggle-pill'}
              onClick={() => onChange('fulfillment', 'pickup')}
            >
              Pickup
            </button>
            <button
              type="button"
              className={isDineIn ? 'toggle-pill is-active' : 'toggle-pill'}
              onClick={() => onChange('fulfillment', 'dine_in')}
            >
              Dine in
            </button>
          </div>

          <div className="form-grid">
            <label className="field">
              <span>Full name</span>
              <input
                value={checkout.name}
                onChange={(event) => onChange('name', event.target.value)}
                placeholder="Maya Brooks"
              />
              {checkoutErrors.name ? (
                <small className="field-error">{checkoutErrors.name}</small>
              ) : null}
            </label>

            <label className="field">
              <span>Email</span>
              <input
                type="email"
                value={checkout.email}
                onChange={(event) => onChange('email', event.target.value)}
                placeholder="maya@example.com"
              />
              {checkoutErrors.email ? (
                <small className="field-error">{checkoutErrors.email}</small>
              ) : null}
            </label>

            <label className="field">
              <span>Phone</span>
              <input
                value={checkout.phone}
                onChange={(event) => onChange('phone', event.target.value)}
                placeholder="+1 (555) 014-8821"
              />
              {checkoutErrors.phone ? (
                <small className="field-error">{checkoutErrors.phone}</small>
              ) : null}
            </label>

            <label className="field">
              <span>Preferred window</span>
              <select
                value={checkout.deliveryWindow}
                onChange={(event) => onChange('deliveryWindow', event.target.value)}
              >
                <option>As soon as possible</option>
                <option>Within 30 minutes</option>
                <option>Within 45 minutes</option>
                <option>After 7:00 PM</option>
              </select>
            </label>

            {isDelivery ? (
              <>
                <label className="field field--full">
                  <span>Address</span>
                  <input
                    value={checkout.address}
                    onChange={(event) => onChange('address', event.target.value)}
                    placeholder="245 Hudson Street"
                  />
                  {checkoutErrors.address ? (
                    <small className="field-error">{checkoutErrors.address}</small>
                  ) : null}
                </label>

                <label className="field">
                  <span>City</span>
                  <input
                    value={checkout.city}
                    onChange={(event) => onChange('city', event.target.value)}
                    placeholder="New York"
                  />
                  {checkoutErrors.city ? (
                    <small className="field-error">{checkoutErrors.city}</small>
                  ) : null}
                </label>

                <label className="field">
                  <span>Postal code</span>
                  <input
                    value={checkout.postalCode}
                    onChange={(event) => onChange('postalCode', event.target.value)}
                    placeholder="10014"
                  />
                  {checkoutErrors.postalCode ? (
                    <small className="field-error">{checkoutErrors.postalCode}</small>
                  ) : null}
                </label>
              </>
            ) : null}

            {isDineIn ? (
              <label className="field field--full">
                <span>Table or seat reference</span>
                <input
                  value={checkout.tableReference}
                  onChange={(event) => onChange('tableReference', event.target.value)}
                  placeholder="Patio Table 4"
                />
                {checkoutErrors.tableReference ? (
                  <small className="field-error">{checkoutErrors.tableReference}</small>
                ) : null}
              </label>
            ) : null}
          </div>
        </div>

        <div className="form-card">
          <div className="section-heading section-heading--compact">
            <h3>Payment method</h3>
            <p>
              Choose how this order should be handled. Live payment capture is disabled for this
              portfolio demo.
            </p>
          </div>

          <div className="payment-list">
            {supportedPaymentMethods.map((method) => (
              <button
                key={method}
                type="button"
                className={
                  checkout.paymentMethod === method ? 'payment-option is-active' : 'payment-option'
                }
                onClick={() => onChange('paymentMethod', method)}
              >
                <strong>{getPaymentMethodLabel(method)}</strong>
                <span>
                  {method === 'cash'
                    ? 'Pay at pickup, table, or handoff. No card charge is created.'
                    : method === 'ach'
                      ? 'Bank payment is simulated for reference. No real transfer is started.'
                      : 'Payment authorization is simulated securely. No live provider charge runs.'}
                </span>
              </button>
            ))}
          </div>

          <label className="field">
            <span>Payment test result</span>
            <select
              value={checkout.paymentSimulationOutcome}
              onChange={(event) =>
                onChange(
                  'paymentSimulationOutcome',
                  event.target.value as CheckoutForm['paymentSimulationOutcome'],
                )
              }
            >
              <option value="success">Approve payment</option>
              <option value="failed">Decline payment</option>
              <option value="pending">Keep payment pending</option>
            </select>
          </label>

          <label className="checkbox-row">
            <input
              type="checkbox"
              checked={checkout.contactless}
              disabled={!isDelivery}
              onChange={(event) => onChange('contactless', event.target.checked)}
            />
            <span>
              {isDelivery
                ? 'Leave at the door if no one answers immediately.'
                : isPickup
                  ? 'Prepare for a quick handoff when I arrive.'
                  : 'Keep the handoff minimal if timing shifts slightly.'}
            </span>
          </label>

          {paymentResult ? (
            <div
              className={
                paymentResult.state === 'failed' || paymentResult.state === 'cancelled'
                  ? 'inline-notice inline-notice--error'
                  : 'inline-notice'
              }
            >
              <p>
                {getPaymentMethodLabel(paymentResult.method)}:{' '}
                {getPaymentStateLabel(paymentResult.state)}. {paymentResult.message}
              </p>
            </div>
          ) : null}
        </div>

        {isDineIn ? (
          <div className="form-card">
            <div className="section-heading section-heading--compact">
              <h3>Table session and split bill</h3>
              <p>
                Keep the dine-in order attached to the same table session and choose how the bill
                should be organized.
              </p>
            </div>

            <label className="field field--full">
              <span>Primary payer</span>
              <input
                value={checkout.primaryPayerName}
                onChange={(event) => onChange('primaryPayerName', event.target.value)}
                placeholder="Maya Brooks"
              />
            </label>

            <div className="toggle-pills">
              <button
                type="button"
                className={
                  checkout.splitBillMode === 'none' ? 'toggle-pill is-active' : 'toggle-pill'
                }
                onClick={() => onChange('splitBillMode', 'none')}
              >
                One bill
              </button>
              <button
                type="button"
                className={
                  checkout.splitBillMode === 'equal' ? 'toggle-pill is-active' : 'toggle-pill'
                }
                onClick={() => onChange('splitBillMode', 'equal')}
              >
                Equal split
              </button>
              <button
                type="button"
                className={
                  checkout.splitBillMode === 'by_item' ? 'toggle-pill is-active' : 'toggle-pill'
                }
                onClick={() => onChange('splitBillMode', 'by_item')}
              >
                Split by item
              </button>
            </div>

            {checkout.splitBillMode !== 'none' ? (
              <label className="field">
                <span>Guests</span>
                <select
                  value={checkout.splitParticipantCount}
                  onChange={(event) => onSplitParticipantCountChange(Number(event.target.value))}
                >
                  <option value={2}>2 guests</option>
                  <option value={3}>3 guests</option>
                  <option value={4}>4 guests</option>
                </select>
              </label>
            ) : null}

            {checkout.splitBillMode !== 'none' ? (
              <div className="form-grid">
                {checkout.splitParticipants.map((participant) => (
                  <label key={participant.id} className="field">
                    <span>Guest name</span>
                    <input
                      value={participant.name}
                      onChange={(event) =>
                        onSplitParticipantNameChange(participant.id, event.target.value)
                      }
                      placeholder="Guest"
                    />
                  </label>
                ))}
              </div>
            ) : null}

            {checkout.splitBillMode === 'by_item' ? (
              <>
                <div className="choice-list">
                  {cartLines.map((line) => (
                    <div key={line.id} className="split-line-card">
                      <MenuThumbnail src={line.image} alt="" className="split-line-card__thumb" />
                      <strong>
                        {line.quantity} x {line.name}
                      </strong>
                      <div className="split-chip-row">
                        {checkout.splitParticipants.map((participant) => {
                          const assigned = participant.itemLineIds.includes(line.id);
                          return (
                            <button
                              key={participant.id}
                              type="button"
                              className={assigned ? 'toggle-pill is-active' : 'toggle-pill'}
                              onClick={() => onToggleSplitItemAssignment(participant.id, line.id)}
                            >
                              {participant.name || 'Guest'}
                            </button>
                          );
                        })}
                      </div>
                    </div>
                  ))}
                </div>

                <div className="inline-notice">
                  <p>
                    {byItemUnassignedLineCount > 0
                      ? `${byItemUnassignedLineCount} item line${
                          byItemUnassignedLineCount === 1 ? '' : 's'
                        } not assigned yet. ORDERra keeps unassigned items, shared fees, and rounding under ${primarySplitParticipantName} so the demo bill can still close cleanly.`
                      : `All item lines have an owner. Shared fees and rounding stay under ${primarySplitParticipantName} for this demo-safe preview.`}
                  </p>
                </div>
              </>
            ) : null}

            {checkout.splitBillMode !== 'none' ? (
              <div className="summary-table" aria-label="Split bill preview">
                <div className="summary-table__total">
                  <span>Split preview</span>
                  <strong>{checkout.splitBillMode === 'equal' ? 'Equal split' : 'By item'}</strong>
                </div>
                {splitPreviewRows.map((row) => (
                  <div key={row.id}>
                    <span>{row.name}</span>
                    <strong>{formatCurrency(row.amount)}</strong>
                  </div>
                ))}
              </div>
            ) : null}

            <div className="inline-notice">
              <p>
                {activeTableSessionId
                  ? `This dine-in checkout is tied to session ${activeTableSessionId.slice(0, 13)}…. Your backend cart is linked before order placement where supported.`
                  : 'Open the table QR to join automatically, or type the seat reference manually. Additional orders can reuse the session after the first is placed.'}
              </p>
            </div>
          </div>
        ) : null}

        {orderError ? (
          <div className={orderErrorNoticeClassName}>
            <p>{orderError}</p>
          </div>
        ) : null}

        <div className="checkout-actions">
          <button type="button" className="button button--secondary" onClick={onBack}>
            Back to menu
          </button>
          <button
            type="button"
            className="button button--primary"
            disabled={orderSubmitting}
            onClick={onSubmit}
          >
            {orderSubmitting ? 'Placing order...' : 'Place order'}
          </button>
        </div>
      </div>

      <aside className="checkout-layout__aside">
        <div className="summary-panel">
          <p className="eyebrow">Summary</p>
          <h3>Review your order</h3>

          <div className="checkout-summary-items" aria-label="Items in this order">
            {cartLines.map((line) => (
              <div key={line.id} className="checkout-summary-item">
                <MenuThumbnail src={line.image} alt="" className="checkout-summary-item__thumb" />
                <div className="checkout-summary-item__body">
                  <strong>
                    {line.quantity} × {line.name}
                  </strong>
                  {line.selections.length > 0 ? (
                    <span>{line.selections.map((selection) => selection.label).join(' • ')}</span>
                  ) : null}
                </div>
                <strong className="checkout-summary-item__price">
                  {formatCurrency(line.unitPrice * line.quantity)}
                </strong>
              </div>
            ))}
          </div>

          <div className="summary-table">
            <div>
              <span>Subtotal</span>
              <strong>{formatCurrency(totals.subtotal)}</strong>
            </div>
            <div>
              <span>{fulfillmentSummaryLabel}</span>
              <strong>{formatCurrency(totals.deliveryFee)}</strong>
            </div>
            <div>
              <span>Service</span>
              <strong>{formatCurrency(totals.serviceFee)}</strong>
            </div>
            <div>
              <span>Discount</span>
              <strong>−{formatCurrency(totals.discount)}</strong>
            </div>
            <div className="summary-table__total">
              <span>Total due</span>
              <strong>{formatCurrency(totals.total)}</strong>
            </div>
          </div>

          <div className="inline-notice">
            <p>
              {isDelivery
                ? 'Delivery times can move slightly during peak periods, but the order reference stays the same once placed.'
                : isPickup
                  ? 'Pickup timing can tighten during rush windows, but the order reference stays the same once placed.'
                  : 'Dine-in timing can shift slightly with kitchen pacing, but the order reference stays the same once placed.'}
            </p>
          </div>
        </div>
      </aside>
    </section>
  );
}
