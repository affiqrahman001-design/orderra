export type HeroAction =
  | {
      type: 'quick-checkout';
      label: string;
      productSlugs: string[];
      fallbackCategoryId: string;
    }
  | {
      type: 'browse-category';
      label: string;
      categoryId: string;
    };

export interface HeroSlide {
  eyebrow: string;
  title: string;
  copy: string;
  image: string;
  imagePosition?: string;
  badge: string;
  primaryAction: HeroAction;
  secondaryAction: HeroAction;
}

export const HERO_SLIDES: HeroSlide[] = [
  {
    eyebrow: 'Premium burger ordering',
    title: 'Hot Honey Chicken Burger',
    copy: 'Crispy chicken, warm honey glaze, fresh slaw, and a clean premium finish.',
    image: '/images/hero/hot-honey-chicken-burger.webp',
    imagePosition: 'center center',
    badge: 'Chef pick',
    primaryAction: {
      type: 'quick-checkout',
      label: 'Order now',
      productSlugs: ['hot-honey-chicken-burger'],
      fallbackCategoryId: 'burgers',
    },
    secondaryAction: {
      type: 'browse-category',
      label: 'View menu',
      categoryId: 'burgers',
    },
  },
  {
    eyebrow: 'Fresh combo deal',
    title: 'Signature Combo Basket',
    copy: 'Burger, crisp sides, and a cold drink built for a quick premium lunch flow.',
    image: '/images/hero/combo-basket.webp',
    imagePosition: 'center center',
    badge: 'Combo ready',
    primaryAction: {
      type: 'quick-checkout',
      label: 'Order now',
      productSlugs: ['signature-combo-basket'],
      fallbackCategoryId: 'combos',
    },
    secondaryAction: {
      type: 'browse-category',
      label: 'View menu',
      categoryId: 'combos',
    },
  },
  {
    eyebrow: 'Clean drink finish',
    title: 'Citrus Sparkling Cooler',
    copy: 'Cold citrus soda, mint, and a lighter finish to complete the order.',
    image: '/images/hero/citrus-sparkling-cooler.webp',
    imagePosition: 'center center',
    badge: 'Refreshing',
    primaryAction: {
      type: 'quick-checkout',
      label: 'Add drink',
      productSlugs: ['citrus-sparkling-cooler', 'citrus-sparkling'],
      fallbackCategoryId: 'drinks',
    },
    secondaryAction: {
      type: 'browse-category',
      label: 'See drinks',
      categoryId: 'drinks',
    },
  },
];
