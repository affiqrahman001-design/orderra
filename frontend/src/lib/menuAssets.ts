/** Stable placeholder when slug and remote URL fail (broken/missing asset). */
export const MENU_PLACEHOLDER_IMAGE = "/media/menu/hot-honey-chicken-burger.webp";

const imageBySlug: Record<string, string> = {
  "signature-smash-burger": "/media/menu/signature-smash-burger.webp",
  "hot-honey-chicken-burger": "/media/menu/hot-honey-chicken-burger.webp",
  "smokehouse-prime-burger": "/media/menu/smokehouse-prime-burger.webp",
  "signature-combo-basket": "/media/menu/signature-combo-basket.webp",
  "parmesan-fries": "/media/menu/parmesan-fries.webp",
  "burnt-butter-corn": "/media/menu/burnt-butter-corn.webp",
  "citrus-sparkling-cooler": "/media/menu/citrus-sparkling-cooler.webp",
  "classic-cola": "/media/menu/classic-cola.webp",
  "still-water": "/media/menu/still-water.webp",
  "house-cappuccino": "/media/menu/house-cappuccino.webp",
  "charred-beef-bowl": "/media/menu/charred-beef-bowl.webp",
  "teriyaki-salmon-bowl": "/media/menu/teriyaki-salmon-bowl.webp",
  "pomodoro-rigatoni": "/media/menu/pomodoro-rigatoni.webp",
  "truffle-mushroom-pasta": "/media/menu/truffle-mushroom-pasta.webp",
  "basque-cheesecake": "/media/menu/basque-cheesecake.webp",
  "dark-chocolate-tart": "/media/menu/dark-chocolate-tart.webp",
};

export function resolveMenuImage(slug?: string | null, imageUrl?: string | null): string {
  const url = typeof imageUrl === "string" ? imageUrl.trim() : "";
  if (url) return url;

  const key = slug?.trim();
  if (!key) return MENU_PLACEHOLDER_IMAGE;

  return imageBySlug[key] ?? `/media/menu/${key}.webp`;
}
