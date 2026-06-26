export function StatCard({ label, value }: { label: string; value: string }) {
  return (
    <div className="admin-stat-card">
      <span className="admin-stat-card__label">{label}</span>
      <strong className="admin-stat-card__value">{value}</strong>
    </div>
  );
}
