import { useState } from "react";
import type { FaqItem } from "../contracts/faq";

interface FaqSectionProps {
  items: FaqItem[];
}

export function FaqSection({ items }: FaqSectionProps) {
  const [activeId, setActiveId] = useState<string | null>(items[0]?.id ?? null);

  return (
    <section className="faq-section">
      <div className="section-heading">
        <p className="eyebrow">FAQ</p>
        <h2>Clear answers before the order is placed</h2>
        <p>
          The essentials are here: timing, promos, pickup, payment, and a few practical ordering notes.
        </p>
      </div>

      <div className="faq-list">
        {items.map((item) => {
          const active = activeId === item.id;

          return (
            <article key={item.id} className={active ? "faq-item is-open" : "faq-item"}>
              <button
                type="button"
                className="faq-item__trigger"
                onClick={() => setActiveId(active ? null : item.id)}
              >
                <span>{item.question}</span>
                <strong>{active ? "−" : "+"}</strong>
              </button>
              {active ? <p className="faq-item__answer">{item.answer}</p> : null}
            </article>
          );
        })}
      </div>
    </section>
  );
}
