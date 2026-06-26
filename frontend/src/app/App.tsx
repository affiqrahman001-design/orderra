import { useEffect, useMemo } from 'react';
import { CartDrawer } from '../components/CartDrawer';
import { CategoryTabs } from '../components/CategoryTabs';
import { CheckoutPanel } from '../components/CheckoutPanel';
import { ConfirmationPanel } from '../components/ConfirmationPanel';
import { EmptyState } from '../components/EmptyState';
import { FaqSection } from '../components/FaqSection';
import { FloatingCartButton } from '../components/FloatingCartButton';
import { Header } from '../components/Header';
import { Hero } from '../components/Hero';
import type { HeroAction, HeroSlide } from '../components/heroSlides';
import { MobileHeroStrip } from '../components/MobileHeroStrip';
import { ProductGrid } from '../components/ProductGrid';
import { ProductModal } from '../components/ProductModal';
import { PromoBanner } from '../components/PromoBanner';
import { ScrollToTopButton } from '../components/ScrollToTopButton';
import { SupportSection } from '../components/SupportSection';
import type { SelectedOption } from '../contracts/order';
import type { Product } from '../contracts/product';
import { useAuthStore } from '../features/auth/authStore';
import { DemoAiAssistant } from '../features/demo-ai-assistant/DemoAiAssistant';
import { sortCategoriesForMenu, sortProductsForMenu } from '../lib/menuOrdering';
import { getCartItemCount, getTotals } from '../lib/pricing';
import { useAppStore } from '../store/appStore';

function scrollToMenu() {
  window.setTimeout(() => {
    document.querySelector('.section-block--menu')?.scrollIntoView({
      behavior: 'smooth',
      block: 'start',
    });
  }, 0);
}

function buildDefaultSelections(product: Product): SelectedOption[] {
  return product.optionGroups.flatMap((group) => {
    if (!group.required || group.selectionMode !== 'single') return [];

    const option = group.options?.[0];
    if (!option) return [];

    return [
      {
        groupId: group.id,
        groupLabel: group.label,
        optionId: option.id,
        label: option.label,
        priceDelta: option.priceDelta,
      },
    ];
  });
}

export default function App({ navigate }: { navigate: (path: string) => void }) {
  const {
    initialized,
    loading,
    catalogError,
    categories,
    products,
    promos,
    faqs,
    searchQuery,
    selectedCategoryId,
    activeProduct,
    cartOpen,
    cartLines,
    cartError,
    serverTotals,
    appliedPromo,
    promoInput,
    promoMessage,
    checkout,
    checkoutErrors,
    currentView,
    latestOrder,
    orderSubmitting,
    orderError,
    latestPayment,
    activeTableSession,
    initialize,
    setSearchQuery,
    setSelectedCategoryId,
    setActiveProduct,
    setCartOpen,
    addProductToCart,
    updateCartLineQuantity,
    removeCartLine,
    setPromoInput,
    applyPromo,
    clearPromo,
    setCheckoutField,
    setSplitParticipantCount,
    setSplitParticipantName,
    toggleSplitItemAssignment,
    goToCheckout,
    goToCatalog,
    placeOrder,
    callWaiter,
    requestBill,
    setPayAtTableReady,
    simulateRefund,
    simulateWebhook,
    advanceRiderSimulation,
    resetOrderFlow,
  } = useAppStore();
  const user = useAuthStore((s) => s.user);
  const logout = useAuthStore((s) => s.logout);

  useEffect(() => {
    void initialize();
  }, [initialize]);

  const featuredProducts = useMemo(
    () => products.filter((product) => product.featured),
    [products],
  );

  const orderedCategories = useMemo(() => sortCategoriesForMenu(categories), [categories]);

  const visibleProducts = useMemo(() => {
    const query = searchQuery.trim().toLowerCase();

    const filteredProducts = products.filter((product) => {
      const matchesCategory =
        selectedCategoryId === 'all' || product.categoryId === selectedCategoryId;

      const matchesQuery =
        !query ||
        `${product.name} ${product.shortName} ${product.description}`.toLowerCase().includes(query);

      return matchesCategory && matchesQuery;
    });

    return sortProductsForMenu(filteredProducts, selectedCategoryId);
  }, [products, searchQuery, selectedCategoryId]);

  const localCartTotals = useMemo(
    () => getTotals(cartLines, checkout.fulfillment, appliedPromo),
    [cartLines, checkout.fulfillment, appliedPromo],
  );
  const cartTotals = serverTotals ?? localCartTotals;
  const cartCount = useMemo(() => getCartItemCount(cartLines), [cartLines]);

  useEffect(() => {
    if (currentView === 'checkout' || currentView === 'confirmation') {
      window.setTimeout(() => window.scrollTo({ top: 0, left: 0, behavior: 'auto' }), 0);
    }
  }, [currentView]);

  const handleSelectProduct = (product: Product) => {
    if (product.flow === 'none') {
      void addProductToCart(product, []);
      return;
    }

    setActiveProduct(product);
  };

  const browseHeroCategory = (categoryId: string) => {
    const categoryExists =
      categoryId === 'all' || categories.some((category) => category.id === categoryId);

    setSearchQuery('');
    setSelectedCategoryId(categoryExists ? categoryId : 'all');
    scrollToMenu();
  };

  const handleHeroAction = async (action: HeroAction) => {
    if (action.type === 'browse-category') {
      browseHeroCategory(action.categoryId);
      return;
    }

    const product = products.find(
      (entry) => action.productSlugs.includes(entry.slug) || action.productSlugs.includes(entry.id),
    );

    if (!product) {
      browseHeroCategory(action.fallbackCategoryId);
      return;
    }

    await addProductToCart(product, buildDefaultSelections(product));
    goToCheckout();
  };

  const handleHeroPrimaryAction = (slide: HeroSlide) => {
    void handleHeroAction(slide.primaryAction);
  };

  const handleHeroSecondaryAction = (slide: HeroSlide) => {
    void handleHeroAction(slide.secondaryAction);
  };

  const handleBackToMenuFromConfirmation = () => {
    resetOrderFlow();
    scrollToMenu();
  };

  if (loading && !initialized) {
    return (
      <div className="app-shell">
        <div className="container app-shell__loading">
          <p className="eyebrow">ORDERra v2</p>
          <h2>Preparing the catalog…</h2>
        </div>
      </div>
    );
  }

  if (catalogError) {
    return (
      <div className="app-shell">
        <div className="container app-shell__loading">
          <p className="eyebrow">Catalog issue</p>
          <h2>The menu could not be loaded.</h2>
          <p>{catalogError}</p>
        </div>
      </div>
    );
  }

  return (
    <div className="app-shell">
      <Header
        searchQuery={searchQuery}
        onSearchChange={setSearchQuery}
        cartCount={cartCount}
        cartSubtotal={cartTotals.total}
        onOpenCart={() => setCartOpen(true)}
        userName={user?.name ?? null}
        role={user?.orderra_role ?? null}
        onCustomerLogin={() => navigate('/login')}
        onPortalLogin={() => navigate('/portal/login')}
        onAccount={() => navigate('/account')}
        onPortal={() => navigate(user?.orderra_role === 'staff' ? '/portal/staff' : '/admin')}
        onLogout={() => {
          void (async () => {
            await logout();
            navigate('/');
          })();
        }}
      />

      <main className="container main-layout">
        {currentView === 'catalog' ? (
          <>
            <section className="catalog-intro-shell">
              <div className="catalog-hero-desktop">
                <Hero
                  featuredCount={featuredProducts.length}
                  onPrimaryAction={handleHeroPrimaryAction}
                  onSecondaryAction={handleHeroSecondaryAction}
                />
              </div>

              <div className="catalog-promo-desktop">
                <PromoBanner promos={promos} />
              </div>
            </section>

            <section className="catalog-mobile-hero">
              <MobileHeroStrip
                onPrimaryAction={handleHeroPrimaryAction}
                onSecondaryAction={handleHeroSecondaryAction}
              />
            </section>

            <section className="section-block section-block--menu">
              <div className="section-heading section-heading--row section-heading--menu">
                <div>
                  <p className="eyebrow">Menu</p>
                  <h2>Choose your favourites</h2>
                </div>
                <p>Browse by category or search the menu directly.</p>
              </div>

              <CategoryTabs
                categories={orderedCategories}
                selectedCategoryId={selectedCategoryId}
                onSelectCategory={setSelectedCategoryId}
              />

              {visibleProducts.length > 0 ? (
                <>
                  <div className="catalog-promo-mobile">
                    <PromoBanner promos={promos} />
                  </div>

                  <div className="catalog-grid-desktop">
                    <ProductGrid products={visibleProducts} onSelectProduct={handleSelectProduct} />
                  </div>

                  <div className="catalog-mobile-flow">
                    <ProductGrid products={visibleProducts} onSelectProduct={handleSelectProduct} />
                  </div>
                </>
              ) : (
                <EmptyState
                  title="Nothing matched that search."
                  copy="Try a broader term or switch back to the full menu."
                />
              )}
            </section>

            <SupportSection />
            <FaqSection items={faqs} />
          </>
        ) : null}

        {currentView === 'checkout' ? (
          <CheckoutPanel
            checkout={checkout}
            checkoutErrors={checkoutErrors}
            totals={cartTotals}
            cartLines={cartLines}
            orderError={orderError}
            orderSubmitting={orderSubmitting}
            paymentResult={latestPayment}
            activeTableSessionId={activeTableSession?.sessionId ?? null}
            onChange={setCheckoutField}
            onSplitParticipantCountChange={setSplitParticipantCount}
            onSplitParticipantNameChange={setSplitParticipantName}
            onToggleSplitItemAssignment={toggleSplitItemAssignment}
            onBack={goToCatalog}
            onSubmit={() => void placeOrder()}
          />
        ) : null}

        {currentView === 'confirmation' && latestOrder ? (
          <ConfirmationPanel
            order={latestOrder}
            onBackToMenu={handleBackToMenuFromConfirmation}
            onCallWaiter={callWaiter}
            onRequestBill={requestBill}
            onPayAtTableReady={setPayAtTableReady}
            onAdvanceRiderSimulation={() => void advanceRiderSimulation()}
            onSimulateRefund={(type) => void simulateRefund(type)}
            onSimulateWebhook={(type) => void simulateWebhook(type)}
          />
        ) : null}
      </main>

      <footer className="site-footer">
        <div className="container site-footer__inner site-footer__inner--brand">
          <a className="site-footer__brand" href="/" aria-label="ORDERra home">
            <img className="site-footer__logo" src="/logo-orderra.png" alt="ORDERra" />
          </a>

          <nav className="site-footer__links" aria-label="ORDERra footer links">
            <button
              type="button"
              className="site-footer__text-link"
              onClick={() => navigate('/help-center')}
            >
              Help Center
            </button>

            <button
              type="button"
              className="site-footer__text-link"
              onClick={() => navigate('/privacy-policy')}
            >
              Privacy Policy
            </button>
          </nav>

          <nav className="site-footer__socials" aria-label="ORDERra social links">
            <a className="site-footer__social-link" href="#" aria-label="Instagram">
              <svg
                aria-hidden="true"
                focusable="false"
                className="site-footer__social-icon"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path d="M12 4.51405C14.4518 4.51405 14.7583 4.51405 15.7543 4.59044C18.2827 4.66683 19.432 5.88905 19.5086 8.3335C19.5852 9.32655 19.5852 9.55572 19.5852 12.0002C19.5852 14.4446 19.5852 14.7502 19.5086 15.6668C19.432 18.1113 18.2061 19.3335 15.7543 19.4099C14.7583 19.4863 14.5284 19.4863 12 19.4863C9.54821 19.4863 9.24173 19.4863 8.32231 19.4099C5.7939 19.3335 4.64463 18.1113 4.56801 15.6668C4.49139 14.6738 4.49139 14.4446 4.49139 12.0002C4.49139 9.55572 4.49139 9.25016 4.56801 8.3335C4.64463 5.88905 5.87052 4.66683 8.32231 4.59044C9.24173 4.51405 9.54821 4.51405 12 4.51405ZM12 2.8335C9.47159 2.8335 9.16511 2.8335 8.24569 2.90988C4.87448 3.06266 3.03564 4.896 2.8824 8.25711C2.80579 9.17377 2.80579 9.47933 2.80579 12.0002C2.80579 14.521 2.80579 14.8266 2.8824 15.7432C3.03564 19.1043 4.87448 20.9377 8.24569 21.0904C9.16511 21.1668 9.47159 21.1668 12 21.1668C14.5284 21.1668 14.8349 21.1668 15.7543 21.0904C19.1255 20.9377 20.9643 19.1043 21.1176 15.7432C21.1942 14.8266 21.1942 14.521 21.1942 12.0002C21.1942 9.47933 21.1942 9.17377 21.1176 8.25711C20.9643 4.896 19.1255 3.06266 15.7543 2.90988C14.8349 2.8335 14.5284 2.8335 12 2.8335ZM12 7.26405C9.39497 7.26405 7.24965 9.40294 7.24965 12.0002C7.24965 14.5974 9.39497 16.7363 12 16.7363C14.605 16.7363 16.7503 14.5974 16.7503 12.0002C16.7503 9.40294 14.605 7.26405 12 7.26405ZM12 15.0557C10.3144 15.0557 8.93526 13.6807 8.93526 12.0002C8.93526 10.3196 10.3144 8.94461 12 8.94461C13.6856 8.94461 15.0647 10.3196 15.0647 12.0002C15.0647 13.6807 13.6856 15.0557 12 15.0557ZM16.9036 6.04183C16.2906 6.04183 15.8309 6.50016 15.8309 7.11127C15.8309 7.72238 16.2906 8.18072 16.9036 8.18072C17.5165 8.18072 17.9762 7.72238 17.9762 7.11127C17.9762 6.50016 17.5165 6.04183 16.9036 6.04183Z" />
              </svg>
            </a>

            <a className="site-footer__social-link" href="#" aria-label="Facebook">
              <svg
                aria-hidden="true"
                focusable="false"
                className="site-footer__social-icon"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path d="M9.49247 8.66667H6.98495V12H9.49247V22H13.6717V12H16.6807L17.015 8.66667H13.6717V7.25C13.6717 6.5 13.8388 6.16667 14.5911 6.16667H17.015V2H13.8388C10.8298 2 9.49247 3.33333 9.49247 5.83333V8.66667Z" />
              </svg>
            </a>
          </nav>
        </div>
      </footer>

      {activeProduct ? (
        <ProductModal
          product={activeProduct}
          onClose={() => setActiveProduct(null)}
          onAddToCart={addProductToCart}
        />
      ) : null}

      <CartDrawer
        open={cartOpen}
        lines={cartLines}
        totals={cartTotals}
        fulfillment={checkout.fulfillment}
        promoInput={promoInput}
        appliedPromo={appliedPromo}
        promoMessage={promoMessage}
        cartError={cartError}
        onClose={() => setCartOpen(false)}
        onBrowseMenu={() => {
          setCartOpen(false);
          goToCatalog();
          scrollToMenu();
        }}
        onQuantityChange={updateCartLineQuantity}
        onRemove={removeCartLine}
        onPromoInputChange={setPromoInput}
        onApplyPromo={() => void applyPromo()}
        onClearPromo={clearPromo}
        onCheckout={goToCheckout}
      />

      <FloatingCartButton
        itemCount={cartCount}
        total={cartTotals.total}
        onClick={() => setCartOpen(true)}
      />

      <ScrollToTopButton offsetForCart={cartCount > 0} />

      {currentView === 'catalog' ? (
        <DemoAiAssistant products={products} categories={categories} navigate={navigate} />
      ) : null}
    </div>
  );
}
