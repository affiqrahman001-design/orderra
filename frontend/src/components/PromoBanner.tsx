import type { Promo } from "../contracts/promo";

interface PromoBannerProps {
  promos: Promo[];
}

export function PromoBanner({ promos }: PromoBannerProps) {
  if (promos.length === 0) {
    return null;
  }

  return (
    <section className="promo-strip" aria-label="Active promo offers">
      {promos.map((promo) => (
        <article key={promo.code} className="promo-strip__item">
          <div className="promo-strip__meta">
            {promo.badge ? <span className="promo-strip__badge">{promo.badge}</span> : null}
            <strong>{promo.code}</strong>
          </div>
          <h3>{promo.title}</h3>
          <p>{promo.description}</p>
        </article>
      ))}
    </section>
  );
}
