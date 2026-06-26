import { useEffect, useState } from 'react';

interface ScrollToTopButtonProps {
  offsetForCart?: boolean;
}

const SHOW_AFTER_PX = 420;

export function ScrollToTopButton({ offsetForCart = false }: ScrollToTopButtonProps) {
  const [isVisible, setIsVisible] = useState(false);

  useEffect(() => {
    const updateVisibility = () => {
      setIsVisible(window.scrollY > SHOW_AFTER_PX);
    };

    updateVisibility();

    window.addEventListener('scroll', updateVisibility, { passive: true });

    return () => {
      window.removeEventListener('scroll', updateVisibility);
    };
  }, []);

  const handleScrollToTop = () => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    window.scrollTo({
      top: 0,
      left: 0,
      behavior: prefersReducedMotion ? 'auto' : 'smooth',
    });
  };

  return (
    <button
      type="button"
      className={[
        'scroll-to-top-button',
        isVisible ? 'is-visible' : '',
        offsetForCart ? 'scroll-to-top-button--with-cart' : '',
      ]
        .filter(Boolean)
        .join(' ')}
      onClick={handleScrollToTop}
      aria-label="Back to top"
      title="Back to top"
    >
      <svg
        aria-hidden="true"
        focusable="false"
        width="48"
        height="48"
        viewBox="0 0 48 48"
        xmlns="http://www.w3.org/2000/svg"
      >
        <defs>
          <mask id="orderraScrollToTopMask">
            <g fill="none" stroke="#fff" strokeLinejoin="round" strokeWidth="4">
              <path
                fill="#555"
                d="M24 44c11.046 0 20-8.954 20-20S35.046 4 24 4S4 12.954 4 24s8.954 20 20 20Z"
              />
              <path strokeLinecap="round" d="M24 33.5v-18m9 9l-9-9l-9 9" />
            </g>
          </mask>
        </defs>
        <path fill="#ea580c" d="M0 0h48v48H0z" mask="url(#orderraScrollToTopMask)" />
      </svg>
    </button>
  );
}
