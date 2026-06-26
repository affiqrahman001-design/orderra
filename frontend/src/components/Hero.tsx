import { useEffect, useMemo, useRef, useState, type PointerEvent } from 'react';
import './HeroCarousel.css';
import { HERO_SLIDES, type HeroSlide } from './heroSlides';

interface HeroProps {
  featuredCount: number;
  onPrimaryAction: (slide: HeroSlide) => void;
  onSecondaryAction: (slide: HeroSlide) => void;
}

const DRAG_THRESHOLD = 56;

export function Hero({ featuredCount, onPrimaryAction, onSecondaryAction }: HeroProps) {
  const [activeIndex, setActiveIndex] = useState(0);
  const [isPaused, setIsPaused] = useState(false);
  const [isDragging, setIsDragging] = useState(false);
  const [failedImages, setFailedImages] = useState<Record<string, boolean>>({});

  const dragStartXRef = useRef<number | null>(null);
  const dragStartYRef = useRef<number | null>(null);

  const activeSlide = HERO_SLIDES[activeIndex];

  const highlights = useMemo(
    () => ['Fresh kitchen daily', 'Pickup & delivery', `${featuredCount} featured picks`],
    [featuredCount],
  );

  const goToPreviousSlide = () => {
    setActiveIndex((currentIndex) =>
      currentIndex === 0 ? HERO_SLIDES.length - 1 : currentIndex - 1,
    );
  };

  const goToNextSlide = () => {
    setActiveIndex((currentIndex) => (currentIndex + 1) % HERO_SLIDES.length);
  };

  useEffect(() => {
    if (isPaused || isDragging) {
      return undefined;
    }

    const timer = window.setInterval(goToNextSlide, 4200);

    return () => window.clearInterval(timer);
  }, [isPaused, isDragging]);

  const markImageAsFailed = (image: string) => {
    setFailedImages((currentImages) => ({
      ...currentImages,
      [image]: true,
    }));
  };

  const resetDragState = (event?: PointerEvent<HTMLElement>) => {
    if (event?.currentTarget.hasPointerCapture(event.pointerId)) {
      event.currentTarget.releasePointerCapture(event.pointerId);
    }

    dragStartXRef.current = null;
    dragStartYRef.current = null;
    setIsDragging(false);
  };

  const handlePointerDown = (event: PointerEvent<HTMLElement>) => {
    if (event.pointerType === 'mouse' && event.button !== 0) {
      return;
    }

    const target = event.target as HTMLElement;

    if (target.closest('button, a, input, textarea, select, label')) {
      return;
    }

    dragStartXRef.current = event.clientX;
    dragStartYRef.current = event.clientY;
    setIsDragging(true);
    event.currentTarget.setPointerCapture(event.pointerId);
  };

  const handlePointerMove = (event: PointerEvent<HTMLElement>) => {
    if (dragStartXRef.current === null || dragStartYRef.current === null) {
      return;
    }

    const diffX = event.clientX - dragStartXRef.current;
    const diffY = event.clientY - dragStartYRef.current;
    const isHorizontalDrag = Math.abs(diffX) > Math.abs(diffY);

    if (!isHorizontalDrag || Math.abs(diffX) < DRAG_THRESHOLD) {
      return;
    }

    if (diffX > 0) {
      goToPreviousSlide();
    } else {
      goToNextSlide();
    }

    resetDragState(event);
  };

  return (
    <section
      className={`hero-banner${isDragging ? ' is-dragging' : ''}`}
      aria-label="Featured ORDERra specials"
      onMouseEnter={() => setIsPaused(true)}
      onMouseLeave={() => setIsPaused(false)}
      onFocus={() => setIsPaused(true)}
      onBlur={() => setIsPaused(false)}
      onPointerDown={handlePointerDown}
      onPointerMove={handlePointerMove}
      onPointerUp={resetDragState}
      onPointerCancel={resetDragState}
    >
      <div className="hero-banner__ambient" aria-hidden="true" />

      <div className="hero-banner__media" aria-hidden="true">
        {!activeSlide.image || failedImages[activeSlide.image] ? (
          <div className="hero-banner__image-fallback">
            <span>ORDERra</span>
          </div>
        ) : (
          <img
            key={activeSlide.image}
            src={activeSlide.image}
            alt=""
            loading="eager"
            draggable="false"
            style={{ objectPosition: activeSlide.imagePosition ?? 'center center' }}
            onError={() => markImageAsFailed(activeSlide.image)}
          />
        )}
      </div>

      <div className="hero-banner__shade" aria-hidden="true" />
      <span className="hero-banner__badge">{activeSlide.badge}</span>

      <div className="hero-banner__nav" aria-label="Hero slide navigation">
        <button
          type="button"
          className="hero-banner__arrow hero-banner__arrow--previous"
          aria-label="Show previous special"
          onClick={goToPreviousSlide}
        >
          ‹
        </button>
        <button
          type="button"
          className="hero-banner__arrow hero-banner__arrow--next"
          aria-label="Show next special"
          onClick={goToNextSlide}
        >
          ›
        </button>
      </div>

      <div className="hero-banner__content">
        <p className="hero-banner__eyebrow">{activeSlide.eyebrow}</p>
        <h2>{activeSlide.title}</h2>
        <p className="hero-banner__copy">{activeSlide.copy}</p>

        <div className="hero-banner__actions">
          <button
            type="button"
            className="hero-banner__primary-action"
            onClick={() => onPrimaryAction(activeSlide)}
          >
            {activeSlide.primaryAction.label}
          </button>
          <button
            type="button"
            className="hero-banner__secondary-action"
            onClick={() => onSecondaryAction(activeSlide)}
          >
            {activeSlide.secondaryAction.label}
          </button>
        </div>

        <div className="hero-banner__highlights" aria-label="Ordering highlights">
          {highlights.map((highlight) => (
            <span key={highlight}>{highlight}</span>
          ))}
        </div>

        <div className="hero-banner__controls" aria-label="Hero slide controls">
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
      </div>
    </section>
  );
}
