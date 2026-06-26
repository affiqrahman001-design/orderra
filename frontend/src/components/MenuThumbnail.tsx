import { memo, useEffect, useState } from 'react';
import { MENU_PLACEHOLDER_IMAGE } from '../lib/menuAssets';

interface MenuThumbnailProps {
  src: string;
  alt: string;
  className?: string;
}

export const MenuThumbnail = memo(function MenuThumbnail({
  src,
  alt,
  className,
}: MenuThumbnailProps) {
  const [effectiveSrc, setEffectiveSrc] = useState(() => src || MENU_PLACEHOLDER_IMAGE);

  useEffect(() => {
    setEffectiveSrc(src || MENU_PLACEHOLDER_IMAGE);
  }, [src]);

  return (
    <img
      src={effectiveSrc || MENU_PLACEHOLDER_IMAGE}
      alt={alt}
      className={className}
      loading="lazy"
      decoding="async"
      onError={() => setEffectiveSrc(MENU_PLACEHOLDER_IMAGE)}
    />
  );
});
