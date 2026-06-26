import type { CartLine, FulfillmentMethod, TotalsSnapshot } from '../contracts/order';
import type { PromoApplication } from '../contracts/promo';
import { formatCurrency } from '../lib/currency';
import { getFulfillmentSummaryLabel } from '../lib/ordering';
import { MenuThumbnail } from './MenuThumbnail';

interface CartDrawerProps {
  open: boolean;
  lines: CartLine[];
  totals: TotalsSnapshot;
  fulfillment: FulfillmentMethod;
  promoInput: string;
  appliedPromo: PromoApplication | null;
  promoMessage: string | null;
  cartError?: string | null;
  onClose: () => void;
  onBrowseMenu: () => void;
  onQuantityChange: (lineId: string, quantity: number) => void;
  onRemove: (lineId: string) => void;
  onPromoInputChange: (value: string) => void;
  onApplyPromo: () => void;
  onClearPromo: () => void;
  onCheckout: () => void;
}

export function CartDrawer({
  open,
  lines,
  totals,
  fulfillment,
  promoInput,
  appliedPromo,
  promoMessage,
  cartError,
  onClose,
  onBrowseMenu,
  onQuantityChange,
  onRemove,
  onPromoInputChange,
  onApplyPromo,
  onClearPromo,
  onCheckout,
}: CartDrawerProps) {
  const fulfillmentSummaryLabel = getFulfillmentSummaryLabel(fulfillment);

  return (
    <>
      <div className={open ? 'drawer-backdrop is-visible' : 'drawer-backdrop'} onClick={onClose} />
      <aside className={open ? 'cart-drawer is-open' : 'cart-drawer'} aria-hidden={!open}>
        <div className="cart-drawer__header">
          <div>
            <p className="eyebrow">Basket</p>
            <h3>Your order</h3>
          </div>
          <button type="button" className="button button--quiet" onClick={onClose}>
            Close
          </button>
        </div>

        <div className="cart-drawer__body">
          {lines.length === 0 ? (
            <div className="cart-empty">
              <h4>Your basket is empty.</h4>
              <p>Browse the menu and add your favourite burger, combo, drink, or side.</p>
              <button type="button" className="button button--secondary" onClick={onBrowseMenu}>
                Browse menu
              </button>
            </div>
          ) : (
            <div className="cart-list">
              {lines.map((line) => (
                <article key={line.id} className="cart-line">
                  <MenuThumbnail src={line.image} alt="" className="cart-line__image" />
                  <div className="cart-line__body">
                    <div className="cart-line__top">
                      <div>
                        <h4>{line.name}</h4>
                        {line.selections.length > 0 ? (
                          <p className="cart-line__meta">
                            {line.selections.map((selection) => selection.label).join(' • ')}
                          </p>
                        ) : null}
                        {line.note ? <p className="cart-line__meta">Note: {line.note}</p> : null}
                      </div>
                      <strong>{formatCurrency(line.unitPrice * line.quantity)}</strong>
                    </div>

                    <div className="cart-line__actions">
                      <div className="quantity-stepper" aria-label="Quantity controls">
                        <button
                          type="button"
                          onClick={() => onQuantityChange(line.id, line.quantity - 1)}
                        >
                          <span className="quantity-stepper__icon">–</span>
                        </button>
                        <span>{line.quantity}</span>
                        <button
                          type="button"
                          onClick={() => onQuantityChange(line.id, line.quantity + 1)}
                        >
                          <span className="quantity-stepper__icon">+</span>
                        </button>
                      </div>

                      <button
                        type="button"
                        className="text-button"
                        onClick={() => onRemove(line.id)}
                      >
                        Remove
                      </button>
                    </div>
                  </div>
                </article>
              ))}
            </div>
          )}
        </div>

        <div className="cart-drawer__footer">
          <div className="promo-box">
            <label className="field">
              <span>Promo code</span>
              <div className="promo-box__row">
                <input
                  type="text"
                  value={promoInput}
                  onChange={(event) => onPromoInputChange(event.target.value)}
                  placeholder="WELCOME10"
                />
                <button type="button" className="button button--secondary" onClick={onApplyPromo}>
                  Apply
                </button>
              </div>
            </label>

            {appliedPromo ? (
              <div className="promo-box__applied">
                <span>{appliedPromo.code}</span>
                <button type="button" className="text-button" onClick={onClearPromo}>
                  Remove
                </button>
              </div>
            ) : null}

            {promoMessage ? <p className="promo-box__message">{promoMessage}</p> : null}
            {cartError ? <p className="promo-box__message field-error">{cartError}</p> : null}
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
              <span>Total</span>
              <strong>{formatCurrency(totals.total)}</strong>
            </div>
          </div>

          <button
            type="button"
            className="button button--primary button--block"
            disabled={lines.length === 0}
            onClick={onCheckout}
          >
            Continue to checkout
          </button>
        </div>
      </aside>
    </>
  );
}
