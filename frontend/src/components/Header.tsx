import { useState } from 'react';
import { formatCurrency } from '../lib/currency';

interface HeaderProps {
  searchQuery: string;
  onSearchChange: (value: string) => void;
  cartCount: number;
  cartSubtotal: number;
  onOpenCart: () => void;
  userName: string | null;
  role: string | null;
  onCustomerLogin: () => void;
  onPortalLogin: () => void;
  onAccount: () => void;
  onPortal: () => void;
  onLogout: () => void;
}

export function Header({
  searchQuery,
  onSearchChange,
  cartCount,
  cartSubtotal,
  onOpenCart,
  userName,
  role,
  onCustomerLogin,
  onPortalLogin,
  onAccount,
  onPortal,
  onLogout,
}: HeaderProps) {
  const [open, setOpen] = useState(false);
  const isPortal = role === 'admin' || role === 'staff';
  const isCustomer = role === 'customer';

  return (
    <>
      <header className="site-header">
        <div className="container site-header__inner">
          <div className="brand-block" aria-label="ORDERra">
            <img className="brand-block__logo" src="/logo-orderra.png" alt="ORDERra" />
          </div>

          <label
            className="search-field site-header__search-desktop"
            htmlFor="orderra-menu-search-desktop"
          >
            <span className="search-field__icon" aria-hidden="true">
              ⌕
            </span>
            <input
              id="orderra-menu-search-desktop"
              name="menuSearchDesktop"
              type="search"
              autoComplete="off"
              enterKeyHint="search"
              value={searchQuery}
              onChange={(event) => onSearchChange(event.target.value)}
              placeholder="Search burgers, fries, shakes, desserts..."
            />
          </label>

          <div className="site-header__actions">
            <div className="signin-menu">
              <button
                type="button"
                className="button button--quiet signin-menu__trigger"
                onClick={() => setOpen((value) => !value)}
              >
                {userName ? userName : 'Sign in'}
              </button>
              {open ? (
                <div className="signin-menu__panel">
                  {!userName ? (
                    <>
                      <button type="button" className="signin-menu__item" onClick={onCustomerLogin}>
                        Customer account
                      </button>
                      <button type="button" className="signin-menu__item" onClick={onPortalLogin}>
                        Restaurant portal
                      </button>
                    </>
                  ) : (
                    <>
                      {isCustomer ? (
                        <button type="button" className="signin-menu__item" onClick={onAccount}>
                          Account
                        </button>
                      ) : null}
                      {isPortal ? (
                        <button type="button" className="signin-menu__item" onClick={onPortal}>
                          Restaurant portal
                        </button>
                      ) : null}
                      <button type="button" className="signin-menu__item" onClick={onLogout}>
                        Logout
                      </button>
                    </>
                  )}
                </div>
              ) : null}
            </div>

            <button type="button" className="cart-pill" onClick={onOpenCart}>
              <span className="cart-pill__count">{cartCount}</span>
              <span>Cart</span>
              <strong>{formatCurrency(cartSubtotal)}</strong>
            </button>
          </div>
        </div>
      </header>

      <div className="mobile-sticky-search">
        <div className="container">
          <label className="search-field" htmlFor="orderra-menu-search-mobile">
            <span className="search-field__icon" aria-hidden="true">
              ⌕
            </span>
            <input
              id="orderra-menu-search-mobile"
              name="menuSearchMobile"
              type="search"
              autoComplete="off"
              enterKeyHint="search"
              value={searchQuery}
              onChange={(event) => onSearchChange(event.target.value)}
              placeholder="Search burgers, fries, shakes, desserts..."
            />
          </label>
        </div>
      </div>
    </>
  );
}
