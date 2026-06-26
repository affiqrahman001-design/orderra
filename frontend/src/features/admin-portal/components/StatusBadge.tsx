type StatusTone = "neutral" | "ok" | "warn" | "bad";

function toneFor(raw: string): StatusTone {
  const s = raw.toLowerCase();
  if (/(success|completed|paid|open|assigned|confirmed|approved|resolved)/.test(s)) return "ok";
  if (/(pending|queued|waiting|requested|manual|review)/.test(s)) return "warn";
  if (/(failed|cancel|error|reject|blocked|closed)/.test(s)) return "bad";
  return "neutral";
}

export function StatusBadge({ status }: { status: string }) {
  const tone = toneFor(status);
  return (
    <span className={`admin-status-badge admin-status-badge--${tone}`} title={status}>
      {status}
    </span>
  );
}
