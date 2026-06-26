import { useEffect, useState } from 'react';
import { HERO_SLIDES, type HeroSlide } from './heroSlides';

interface MobileHeroStripProps {
  onPrimaryAction: (slide: HeroSlide) => void;
  onSecondaryAction: (slide: HeroSlide) => void;
}

const MOBILE_INTERVAL_MS = 4200;

export function MobileHeroStrip({ onPrimaryAction, onSecondaryAction }: MobileHeroStripProps) {
  const [activeIndex, setActiveIndex] = useState(0);
  const activeSlide = HERO_SLIDES[activeIndex];

  useEffect(() => {
    const timer = window.setInterval(() => {
      setActiveIndex((currentIndex) => (currentIndex + 1) % HERO_SLIDES.length);
    }, MOBILE_INTERVAL_MS);

    return () => window.clearInterval(timer);
  }, []);

  return (
    <section className="mobile-hero-strip" aria-label="Featured ORDERra specials">
      <button
        type="button"
        className="mobile-hero-strip__image"
        onClick={() => onSecondaryAction(activeSlide)}
      >
        <img
          src={activeSlide.image}
          alt=""
          loading="eager"
          draggable="false"
          style={{ objectPosition: activeSlide.imagePosition ?? 'center center' }}
        />

        <span className="mobile-hero-strip__shade" aria-hidden="true" />

        <span className="mobile-hero-strip__content">
          <span className="mobile-hero-strip__eyebrow">{activeSlide.eyebrow}</span>
          <strong>{activeSlide.title}</strong>
        </span>
      </button>

      <div className="mobile-hero-strip__actions">
        <button type="button" onClick={() => onPrimaryAction(activeSlide)}>
          {activeSlide.primaryAction.label}
        </button>
        <button type="button" onClick={() => onSecondaryAction(activeSlide)}>
          {activeSlide.secondaryAction.label}
        </button>
      </div>

      <div className="mobile-hero-strip__dots" aria-label="Hero slide controls">
        {HERO_SLIDES.map((slide, index) => (
          <button
            key={slide.title}
            type="button"
            className={index === activeIndex ? 'is-active' : ''}
            aria-label={`Show ${slide.title}`}
            aria-pressed={index === activeIndex}
            onClick={() => setActiveIndex(index)}
          />
        ))}
      </div>
    </section>
  );
}
