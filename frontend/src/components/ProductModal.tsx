import { useEffect, useMemo, useState } from 'react';
import type { SelectedOption } from '../contracts/order';
import type { Product } from '../contracts/product';
import { formatCurrency } from '../lib/currency';
import {
  buildInitialSelectionState,
  flattenSelections,
  getSelectionPriceDelta,
  validateRequiredSelections,
} from '../lib/ordering';
import { MenuThumbnail } from './MenuThumbnail';

interface ProductModalProps {
  product: Product;
  onClose: () => void;
  onAddToCart: (product: Product, selections: SelectedOption[], note?: string) => void;
}

export function ProductModal({ product, onClose, onAddToCart }: ProductModalProps) {
  const [selectionState, setSelectionState] = useState(() => buildInitialSelectionState(product));
  const [note, setNote] = useState('');
  const [errors, setErrors] = useState<string[]>([]);

  useEffect(() => {
    setSelectionState(buildInitialSelectionState(product));
    setNote('');
    setErrors([]);
  }, [product]);

  const selections = useMemo(
    () => flattenSelections(product, selectionState),
    [product, selectionState],
  );

  const total = product.price + getSelectionPriceDelta(selections);

  const handleSingleChange = (groupId: string, optionId: string) => {
    setSelectionState((current) => ({
      ...current,
      single: {
        ...current.single,
        [groupId]: optionId,
      },
    }));
  };

  const handleMultipleToggle = (groupId: string, optionId: string) => {
    setSelectionState((current) => {
      const selected = current.multiple[groupId] ?? [];
      const nextSelection = selected.includes(optionId)
        ? selected.filter((entry) => entry !== optionId)
        : [...selected, optionId];

      return {
        ...current,
        multiple: {
          ...current.multiple,
          [groupId]: nextSelection,
        },
      };
    });
  };

  const handleSubmit = () => {
    const validationErrors = validateRequiredSelections(product, selectionState);

    if (validationErrors.length > 0) {
      setErrors(validationErrors);
      return;
    }

    onAddToCart(product, selections, note);
  };

  if (product.flow === 'none') {
    return null;
  }

  const isSimple = product.flow === 'simple';

  return (
    <div className="modal-backdrop" role="presentation" onClick={onClose}>
      <div
        className={isSimple ? 'modal-frame modal-frame--simple' : 'modal-frame'}
        role="dialog"
        aria-modal="true"
        aria-labelledby={`product-modal-title-${product.id}`}
        onClick={(event) => event.stopPropagation()}
      >
        <button
          type="button"
          className="modal-shell__close"
          aria-label="Close product customisation"
          onClick={onClose}
        >
          ×
        </button>

        <div className={isSimple ? 'modal-shell modal-shell--simple' : 'modal-shell'}>
          <div className="modal-shell__header">
            <div>
              <p className="eyebrow">{isSimple ? 'Quick customise' : 'Build your order'}</p>
              <h3 id={`product-modal-title-${product.id}`}>{product.name}</h3>
              <p className="modal-shell__copy">{product.description}</p>
            </div>
          </div>

          <div className="modal-shell__media">
            <MenuThumbnail
              src={product.image}
              alt={product.imageAlt}
              className="modal-shell__media-img"
            />
          </div>

          <div className="modal-shell__content">
            {product.optionGroups.map((group) => (
              <section key={group.id} className="option-group">
                <div className="option-group__head">
                  <h4>{group.label}</h4>
                  {group.helperText ? <p>{group.helperText}</p> : null}
                </div>

                {group.selectionMode === 'single' ? (
                  <div className="choice-list">
                    {group.options?.map((option) => (
                      <label key={option.id} className="choice-row">
                        <input
                          type="radio"
                          name={group.id}
                          checked={selectionState.single[group.id] === option.id}
                          onChange={() => handleSingleChange(group.id, option.id)}
                        />
                        <span>{option.label}</span>
                        <strong>
                          {option.priceDelta > 0
                            ? `+${formatCurrency(option.priceDelta)}`
                            : 'Included'}
                        </strong>
                      </label>
                    ))}
                  </div>
                ) : null}

                {group.selectionMode === 'multiple' ? (
                  <div className="choice-list">
                    {group.options?.map((option) => {
                      const checked = (selectionState.multiple[group.id] ?? []).includes(option.id);

                      return (
                        <label key={option.id} className="choice-row">
                          <input
                            type="checkbox"
                            checked={checked}
                            onChange={() => handleMultipleToggle(group.id, option.id)}
                          />
                          <span>{option.label}</span>
                          <strong>
                            {option.priceDelta > 0
                              ? `+${formatCurrency(option.priceDelta)}`
                              : 'Included'}
                          </strong>
                        </label>
                      );
                    })}
                  </div>
                ) : null}
              </section>
            ))}

            <label className="field modal-shell__note-field">
              <span>Kitchen note</span>
              <textarea
                rows={4}
                placeholder="Optional. Keep it short and practical."
                value={note}
                onChange={(event) => setNote(event.target.value)}
              />
            </label>

            {errors.length > 0 ? (
              <div className="inline-notice inline-notice--error">
                {errors.map((error) => (
                  <p key={error}>{error}</p>
                ))}
              </div>
            ) : null}
          </div>

          <div className="modal-shell__footer">
            <div className="modal-shell__price-block">
              <span className="modal-shell__total-label">Item total</span>
              <strong className="modal-shell__total-value">{formatCurrency(total)}</strong>
            </div>

            <button
              type="button"
              className="button button--primary modal-shell__add-button"
              onClick={handleSubmit}
            >
              Add to cart
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
