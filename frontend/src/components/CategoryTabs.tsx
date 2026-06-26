import type { Category } from "../contracts/category";

interface CategoryTabsProps {
  categories: Category[];
  selectedCategoryId: string;
  onSelectCategory: (categoryId: string) => void;
}

export function CategoryTabs({
  categories,
  selectedCategoryId,
  onSelectCategory,
}: CategoryTabsProps) {
  return (
    <section className="category-tabs" aria-label="Browse by category">
      <button
        type="button"
        className={selectedCategoryId === "all" ? "tab-chip is-active" : "tab-chip"}
        onClick={() => onSelectCategory("all")}
      >
        <span>All</span>
        <small>Entire menu</small>
      </button>

      {categories.map((category) => (
        <button
          key={category.id}
          type="button"
          className={selectedCategoryId === category.id ? "tab-chip is-active" : "tab-chip"}
          onClick={() => onSelectCategory(category.id)}
        >
          <span>{category.name}</span>
          <small>{category.itemCountLabel}</small>
        </button>
      ))}
    </section>
  );
}
