interface EmptyStateProps {
  title: string;
  copy: string;
}

export function EmptyState({ title, copy }: EmptyStateProps) {
  return (
    <section className="empty-state">
      <div className="empty-state__icon" aria-hidden="true">
        ⌕
      </div>
      <h3>{title}</h3>
      <p>{copy}</p>
    </section>
  );
}
