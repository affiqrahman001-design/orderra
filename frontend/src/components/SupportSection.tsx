export function SupportSection() {
  return (
    <section className="support-grid">
      <article className="support-card">
        <p className="eyebrow">Support</p>
        <h3>Need help with allergies, group orders, or timing?</h3>
        <p>
          Reach out before checkout and we can advise on practical substitutions or a cleaner split
          for larger baskets.
        </p>
        <a href="mailto:support@orderra.local" className="text-button text-button--inline">
          support@orderra.local
        </a>
      </article>

      <article className="support-card">
        <p className="eyebrow">Kitchen notes</p>
        <h3>Keep notes short and useful for the pass.</h3>
        <p>
          “No onions” or “sauce on the side” works well. Brief requests travel more clearly through
          the kitchen and delivery handoff.
        </p>
      </article>

      <article className="support-card">
        <p className="eyebrow">Pickup</p>
        <h3>Need a quieter handoff? Pickup stays available.</h3>
        <p>
          Switch to pickup at checkout and we will hold the order for a short collection window once
          it is ready.
        </p>
      </article>
    </section>
  );
}
