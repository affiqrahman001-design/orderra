import type { ReactNode } from "react";
import { QRCodeSVG } from "qrcode.react";

interface QrCodeCardProps {
  value: string;
  title?: string;
  subtitle?: string;
  size?: number;
  footer?: ReactNode;
}

export function QrCodeCard({ value, title, subtitle, size = 180, footer }: QrCodeCardProps) {
  if (!value.trim()) return null;

  return (
    <div className="form-card dine-in-qr-card" role="presentation">
      {title ? <p className="eyebrow">{title}</p> : null}
      {subtitle ? <p className="dine-in-qr-card__subtitle">{subtitle}</p> : null}
      <div className="dine-in-qr-card__qr">
        <QRCodeSVG value={value} size={size} marginSize={2} bgColor="#0f172a" fgColor="#f8fafc" />
      </div>
      <p className="dine-in-qr-card__url">{value}</p>
      {footer}
    </div>
  );
}
