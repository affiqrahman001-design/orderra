import { useMemo, useRef, useState } from 'react';
import { ScrollToTopButton } from '../components/ScrollToTopButton';
import {
  HELP_CENTER_FAQ_COUNT,
  helpCenterCategories,
  helpCenterFaqs,
  type HelpCenterCategoryKey,
} from './helpCenterFaq';

interface HelpCenterPageProps {
  navigate: (path: string) => void;
}

type HelpCenterFilter = HelpCenterCategoryKey | 'all';

export function HelpCenterPage({ navigate }: HelpCenterPageProps) {
  const [activeCategory, setActiveCategory] = useState<HelpCenterFilter>('all');
  const [searchTerm, setSearchTerm] = useState('');
  const [openFaqId, setOpenFaqId] = useState<string>('gs-01');
  const categoryButtonRefs = useRef<Record<string, HTMLButtonElement | null>>({});

  const categoryCounts = useMemo(() => {
    return helpCenterFaqs.reduce<Record<string, number>>((counts, faq) => {
      counts[faq.category] = (counts[faq.category] ?? 0) + 1;
      return counts;
    }, {});
  }, []);

  const selectCategory = (category: HelpCenterFilter) => {
    setActiveCategory(category);
    setOpenFaqId('');

    window.setTimeout(() => {
      categoryButtonRefs.current[category]?.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest',
        inline: 'center',
      });
    }, 0);
  };

  const filteredFaqs = useMemo(() => {
    const normalizedSearch = searchTerm.trim().toLowerCase();

    return helpCenterFaqs.filter((faq) => {
      const matchesCategory = activeCategory === 'all' || faq.category === activeCategory;
      const searchableText = `${faq.question} ${faq.answer} ${faq.tags.join(' ')}`.toLowerCase();
      const matchesSearch =
        normalizedSearch.length === 0 || searchableText.includes(normalizedSearch);

      return matchesCategory && matchesSearch;
    });
  }, [activeCategory, searchTerm]);

  return (
    <div className="app-shell help-page">
      <header className="help-page__header">
        <div className="container help-page__header-inner">
          <button
            type="button"
            className="help-page__brand"
            onClick={() => navigate('/')}
            aria-label="Back to ORDERra home"
          >
            <img src="/logo-orderra.png" alt="ORDERra" />
          </button>

          <div className="help-page__header-actions">
            <button
              type="button"
              className="button button--quiet"
              onClick={() => navigate('/privacy-policy')}
            >
              Privacy Policy
            </button>
            <button type="button" className="button button--quiet" onClick={() => navigate('/')}>
              Back to menu
            </button>
          </div>
        </div>
      </header>

      <main className="container help-page__main">
        <section className="help-hero">
          <p className="eyebrow">Help Center</p>
          <h1>How can we help?</h1>
          <p>
            Find clear answers about ordering, dine-in QR sessions, pickup, delivery tracking,
            payment simulation, refunds, privacy, and demo support in ORDERra.
          </p>

          <label className="help-search" htmlFor="help-search-input">
            <span aria-hidden="true">⌕</span>
            <input
              id="help-search-input"
              type="search"
              placeholder="Search ORDERra help topics..."
              value={searchTerm}
              onChange={(event) => setSearchTerm(event.target.value)}
            />
          </label>

          <div className="help-hero__meta">
            <span>{HELP_CENTER_FAQ_COUNT} fixed Q&A</span>
            <span>Demo-safe support</span>
            <span>No real payment or live agent</span>
          </div>
        </section>

        <section className="help-layout" aria-label="ORDERra Help Center content">
          <aside className="help-sidebar" aria-label="Help categories">
            <button
              type="button"
              ref={(node) => {
                categoryButtonRefs.current.all = node;
              }}
              className={`help-category ${activeCategory === 'all' ? 'is-active' : ''}`}
              onClick={() => selectCategory('all')}
            >
              <span>All topics</span>
              <strong>{HELP_CENTER_FAQ_COUNT}</strong>
            </button>

            {helpCenterCategories.map((category) => (
              <button
                type="button"
                key={category.key}
                ref={(node) => {
                  categoryButtonRefs.current[category.key] = node;
                }}
                className={`help-category ${activeCategory === category.key ? 'is-active' : ''}`}
                onClick={() => selectCategory(category.key)}
              >
                <span>{category.label}</span>
                <strong>{categoryCounts[category.key] ?? 0}</strong>
              </button>
            ))}
          </aside>

          <section className="help-results">
            <div className="section-heading section-heading--row help-results__heading">
              <div>
                <p className="eyebrow">Support library</p>
                <h2>
                  {filteredFaqs.length === 1
                    ? '1 answer found'
                    : `${filteredFaqs.length} answers found`}
                </h2>
              </div>
              <p>
                Every answer is curated for the ORDERra demo boundary. Real payment, real dispatch,
                real refunds, and real live support remain disabled.
              </p>
            </div>

            {filteredFaqs.length > 0 ? (
              <div className="help-faq-list">
                {filteredFaqs.map((faq) => {
                  const isOpen = openFaqId === faq.id;

                  return (
                    <article className={`help-faq-item ${isOpen ? 'is-open' : ''}`} key={faq.id}>
                      <button
                        type="button"
                        className="help-faq-item__trigger"
                        onClick={() => setOpenFaqId(isOpen ? '' : faq.id)}
                        aria-expanded={isOpen}
                      >
                        <span>{faq.question}</span>
                        <strong aria-hidden="true">{isOpen ? '−' : '+'}</strong>
                      </button>

                      {isOpen ? (
                        <div className="help-faq-item__answer">
                          <p>{faq.answer}</p>
                          <div className="help-faq-item__tags" aria-label="Related tags">
                            {faq.tags.slice(0, 4).map((tag) => (
                              <span key={tag}>{tag}</span>
                            ))}
                          </div>
                        </div>
                      ) : null}
                    </article>
                  );
                })}
              </div>
            ) : (
              <div className="help-empty">
                <h3>No answer found</h3>
                <p>
                  Try searching for ordering, delivery, pickup, dine-in, payment simulation, refund,
                  privacy, or support agent.
                </p>
                <button
                  type="button"
                  className="button button--secondary"
                  onClick={() => {
                    setSearchTerm('');
                    setActiveCategory('all');
                    setOpenFaqId('gs-01');
                  }}
                >
                  Reset search
                </button>
              </div>
            )}
          </section>
        </section>
      </main>
      <ScrollToTopButton />
    </div>
  );
}
