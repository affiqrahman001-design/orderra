export function AdminLoadingState({ label = "Loading…" }: { label?: string }) {
  return <p className="admin-muted">{label}</p>;
}

export function AdminEmptyState({
  title,
  copy,
}: {
  title: string;
  copy?: string;
}) {
  return (
    <div className="inline-notice">
      <p>
        <strong>{title}</strong>
        {copy ? ` ${copy}` : null}
      </p>
    </div>
  );
}

export function AdminErrorState({
  title,
}: {
  title: string;
}) {
  return (
    <div className="inline-notice inline-notice--error">
      <p>{title}</p>
    </div>
  );
}
