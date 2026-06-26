import { ScrollToTopButton } from '../components/ScrollToTopButton';

interface PrivacyPolicyPageProps {
  navigate: (path: string) => void;
}

const demoBoundaries = [
  'ORDERra does not process real payments or charge real cards.',
  'ORDERra does not store real card numbers, CVV codes, bank credentials, or wallet credentials.',
  'ORDERra does not execute real refunds, payouts, courier dispatch, or live rider assignment.',
  'ORDERra does not contact a real live support agent from this demo experience.',
];

const dataSections = [
  {
    title: 'Demo account and login data',
    body: 'If you sign in during the demo, ORDERra may use sample account information such as a name, email address, role, and session token to demonstrate customer and restaurant portal flows.',
  },
  {
    title: 'Cart, order, and fulfillment data',
    body: 'The demo may use menu selections, cart items, order notes, fulfillment mode, pickup timing, delivery address examples, dine-in table sessions, QR session codes, split bill choices, and order status history to show how an ordering platform works.',
  },
  {
    title: 'Payment simulation data',
    body: 'Payment screens, payment attempts, payment status changes, refunds, webhook events, and receipts are simulated for portfolio review. They are not submitted to a live payment processor.',
  },
  {
    title: 'Support and refund simulation data',
    body: 'Support prompts, refund reasons, and demo support requests may be used to demonstrate service workflows. Submitting a support request in the demo does not create a real ticket unless a future backend integration is explicitly enabled.',
  },
  {
    title: 'Browser storage',
    body: 'ORDERra may use browser storage such as localStorage to remember demo cart state, support widget position, support widget messages, session state, and UI preferences on the same device.',
  },
  {
    title: 'Cookies and similar storage',
    body: 'The demo may use cookies or browser-managed storage for authentication, session continuity, or framework behavior. No advertising cookie system is required for the portfolio demo.',
  },
];

const rights = [
  'Review the information you entered in the demo.',
  'Clear browser storage to remove locally saved demo state.',
  'Avoid entering sensitive personal, payment, medical, legal, or financial information into demo fields.',
  'Contact the project owner if you need a demo data note reviewed or removed from a controlled test environment.',
];

export function PrivacyPolicyPage({ navigate }: PrivacyPolicyPageProps) {
  return (
    <div className="app-shell privacy-page">
      <header className="privacy-page__header">
        <div className="container privacy-page__header-inner">
          <button
            type="button"
            className="privacy-page__brand"
            onClick={() => navigate('/')}
            aria-label="Back to ORDERra home"
          >
            <img src="/logo-orderra.png" alt="ORDERra" />
          </button>

          <button type="button" className="button button--quiet" onClick={() => navigate('/')}>
            Back to menu
          </button>
        </div>
      </header>

      <main className="container privacy-page__main">
        <section className="privacy-hero">
          <p className="eyebrow">Privacy Policy</p>
          <h1>ORDERra Privacy Policy</h1>
          <p>
            This Privacy Policy explains how ORDERra handles information inside this demo-safe
            portfolio ordering platform. ORDERra is designed to look and feel like a real restaurant
            ordering product, but it does not run real payment, dispatch, refund, or live support
            operations.
          </p>
          <div className="privacy-hero__meta" aria-label="Policy metadata">
            <span>Effective date: Demo version</span>
            <span>Product: ORDERra portfolio demo</span>
          </div>
        </section>

        <section className="privacy-notice" aria-labelledby="demo-safe-title">
          <div>
            <p className="eyebrow">Demo-safe boundary</p>
            <h2 id="demo-safe-title">What ORDERra does not do</h2>
          </div>
          <ul>
            {demoBoundaries.map((item) => (
              <li key={item}>{item}</li>
            ))}
          </ul>
        </section>

        <section className="privacy-section" aria-labelledby="information-title">
          <div className="section-heading section-heading--row">
            <div>
              <p className="eyebrow">Information use</p>
              <h2 id="information-title">What data may be used in the demo</h2>
            </div>
            <p>
              The data below is used only to demonstrate ordering, checkout, fulfillment, support,
              and admin-style workflows.
            </p>
          </div>

          <div className="privacy-grid">
            {dataSections.map((section) => (
              <article className="privacy-card" key={section.title}>
                <h3>{section.title}</h3>
                <p>{section.body}</p>
              </article>
            ))}
          </div>
        </section>

        <section className="privacy-section privacy-section--split" aria-labelledby="logs-title">
          <article className="privacy-card privacy-card--large">
            <p className="eyebrow">Logs and analytics</p>
            <h2 id="logs-title">Operational logs and analytics placeholders</h2>
            <p>
              ORDERra may include development logs, simulated event records, demo webhook payloads,
              payment status logs, admin action examples, and analytics placeholders to explain how
              a production system could be observed. These records are for demonstration and quality
              assurance only.
            </p>
          </article>

          <article className="privacy-card privacy-card--large">
            <p className="eyebrow">Human support</p>
            <h2>Human agent and support request notice</h2>
            <p>
              Any “Talk to human agent” or support request flow in ORDERra is demo-safe. It may show
              a confirmation message for the portfolio experience, but it does not contact a real
              agent or send a live support ticket unless a future backend integration is
              deliberately added.
            </p>
          </article>
        </section>

        <section
          className="privacy-section privacy-section--split"
          aria-labelledby="retention-title"
        >
          <article className="privacy-card privacy-card--large">
            <p className="eyebrow">Retention</p>
            <h2 id="retention-title">Data retention</h2>
            <p>
              Locally stored demo data may remain in your browser until you clear it, reset the
              demo, or use another browser/device. Server-side demo data, when enabled, should be
              treated as temporary sample data and not as a production customer record.
            </p>
          </article>

          <article className="privacy-card privacy-card--large">
            <p className="eyebrow">Security</p>
            <h2>Security approach</h2>
            <p>
              ORDERra uses a demo-first safety boundary, avoids real payment collection, and keeps
              live external service behavior disabled. For production use, payment, dispatch,
              analytics, and support integrations would need a separate compliance and security
              review.
            </p>
          </article>
        </section>

        <section className="privacy-section" aria-labelledby="rights-title">
          <div className="privacy-card privacy-card--large">
            <p className="eyebrow">Your choices</p>
            <h2 id="rights-title">User rights and safe demo use</h2>
            <ul className="privacy-list">
              {rights.map((item) => (
                <li key={item}>{item}</li>
              ))}
            </ul>
          </div>
        </section>

        <section
          className="privacy-section privacy-section--split"
          aria-labelledby="children-title"
        >
          <article className="privacy-card privacy-card--large">
            <p className="eyebrow">Children privacy</p>
            <h2 id="children-title">Children’s privacy</h2>
            <p>
              ORDERra is a portfolio demo for product review and development reference. It is not
              intended to collect information from children or to operate as a child-directed
              service.
            </p>
          </article>

          <article className="privacy-card privacy-card--large">
            <p className="eyebrow">Changes</p>
            <h2>Policy changes</h2>
            <p>
              This policy may be updated as ORDERra’s demo features evolve. Any future move from
              demo simulation to production behavior must be documented clearly before real
              customer, payment, dispatch, or support data is processed.
            </p>
          </article>
        </section>

        <section className="privacy-contact" aria-labelledby="contact-title">
          <div>
            <p className="eyebrow">Contact</p>
            <h2 id="contact-title">Questions about this demo policy?</h2>
            <p>
              Use the demo support area for product walkthrough questions, or contact the project
              owner through the portfolio handoff channel. Do not enter real card data or sensitive
              personal information into ORDERra demo fields.
            </p>
          </div>
          <button type="button" className="button button--primary" onClick={() => navigate('/')}>
            Return to ORDERra
          </button>
        </section>
      </main>
      <ScrollToTopButton />
    </div>
  );
}
