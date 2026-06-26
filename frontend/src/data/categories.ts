import type { Category } from '../contracts/category';

export const categories: Category[] = [
  {
    id: 'burgers',
    name: 'Burgers',
    description: 'Premium signature burger lineup.',
    itemCountLabel: '3 signature builds',
  },
  {
    id: 'combos',
    name: 'Combos',
    description: 'Premium meal bundles for demo checkout.',
    itemCountLabel: '1 bundle',
  },
  {
    id: 'plates-pasta',
    name: 'Plates & Pasta',
    description: 'Warm bowls and pasta plates for expanded demo ordering.',
    itemCountLabel: '4 mains',
  },
  {
    id: 'sides',
    name: 'Sides',
    description: 'Crispy sides and warm add-ons.',
    itemCountLabel: '2 side picks',
  },
  {
    id: 'desserts',
    name: 'Desserts',
    description: 'Small premium desserts.',
    itemCountLabel: '2 sweet picks',
  },
  {
    id: 'drinks',
    name: 'Drinks',
    description: 'Clean premium drinks selection.',
    itemCountLabel: '4 quick additions',
  },
];
