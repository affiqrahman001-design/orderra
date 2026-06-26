# Portfolio notes — ORDERra v2

## Positioning

ORDERra v2 demonstrates how a boutique hospitality brand could run a focused **single-location** ordering experience on the modern web instead of cramming flows into marketplace apps. Everything is intentional: tight catalog copy, restrained motion, luminous surfaces, QR-first dine-in cues, a believable fulfillment timeline, helpful support content, and a controlled demo AI Support experience.

The codebase is optimised for reviewers who understand **shipping vertical slices**, not scaffolding noise.

---

## Architectural stance

### Backend-first

The Laravel tier owns authoritative state:

- carts, pricing quotes, audits, webhook envelopes, refunds, dine-in QR sessions
- support ticket reference records and demo-safe support flows
- deterministic transitions for orders and simulations
- staff-only modules gated by `orderra.admin` middleware + bearer auth

React consumes JSON resources and adapts shapes in thin HTTP adapters (`frontend/src/services/adapters/http`).

### Modular domains

Distinct route files (`routes/customer.php`, `routes/admin.php`, `routes/simulation.php`, `routes/webhooks.php`) keep demos narratable—you can screenshot route lists alongside README claims.

### Demo payment boundary

All payment activity funnels through **registered demo providers** with guard rails:

- `LivePaymentExecutionBlockedException` surfaces if someone attempts a disallowed mode.
- Admin + customer layers only ever exercise **simulate** verbs for intents.

Assume any future PSP integration swaps the simulator for a façade while retaining the contracts.

---

## Payment simulation recap

Purpose: Show how intents, attempts, webhook fan-out, and refunds interlock **without risking Cardholder Data Environment scope**.

Operational toggles (`PAYMENTS_DEMO_MODE`, structured provider registry seeders) make the story reproducible locally and in CI.

---

## Support experience recap

Purpose: Show how a premium ordering platform can guide customers without pretending to run a live AI or live support operation.

ORDERra includes:

- Help Center with controlled customer-facing Q&A
- Privacy Policy page with clear demo-safe data wording
- controlled demo AI Support using prepared local answers
- smart internal CTA buttons for Help Center and Privacy Policy navigation
- demo-only human support handoff card
- support ticket reference areas for admin review

AI Support does not contact OpenAI, external AI providers, live support ticketing systems, real human agents, payment providers, rider dispatch providers, or external support APIs.

---

## QR dine-in flow

1. `RestaurantTable` rows represent physical seats.
2. `QrSession` records capture join codes, linkage to carts/orders, and lifecycle states (`open`, `bill_requested`, `payment_ready`, `expired`).
3. Admin can **rotate** sessions per table, logging `dine_in.table.qr_rotate` in the audit channel.
4. Customers hit `/qr/{sessionCode}` in the SPA, which resolves the session server-side (`GET /api/v1/qr/{sessionCode}`) and primes Zustand with table context plus optional cart attach.

Short URLs minimise printable QR payloads; keep `FRONTEND_URL` synced with whichever host reviewers scan.

---

## Admin operations storyline

Demonstrate operational maturity without fabricating brittle CRUD everywhere:

1. Ops snapshot via dashboard aggregates.
2. Orders + kitchen consoles for pacing.
3. Reference hubs (payments, refunds, Ops webhooks, support tickets, audit trails, rider assignments).
4. Help Center, Privacy Policy, and controlled demo AI Support complete the customer guidance layer.
5. Scenario catalogue page enumerating **safe simulations** exposed by `/admin/demo-scenarios`.

Each module maps to artisan-seeded artefacts so empties rarely happen immediately after migrate.

---

## What production would demand

Not exhaustive, but the obvious deltas:

| Area          | Demo today                                | Production expectation                                                     |
| ------------- | ----------------------------------------- | -------------------------------------------------------------------------- |
| Auth          | Staff bearer + reference key              | SSO / scoped policies, device posture checks                               |
| Payments      | Deterministic simulations                 | Contracts with acquirers, settlement recon, AML hooks                      |
| Menus         | Seeded SQLite                             | CDN-backed media pipeline, versioning, multilingual                        |
| Realtime      | Silent / stubbed websockets               | Event bus + fan-out, mobile push                                           |
| Support       | Prepared demo answers + reference tickets | Verified support workflow, human staffing, SLAs, abuse handling            |
| AI            | Controlled local answer matching          | Optional external AI only after privacy, security, cost, and safety review |
| Observability | HTTP logs optional                        | Structured tracing, alerting, anomaly detection                            |

ORDERra purposely stops short of wiring those so portfolio readers can extrapolate without drowning in infra code. The support experience is also intentionally demo-safe: it feels helpful, but it does not call real AI, real ticketing, real human agents, live payment providers, or live dispatch services.
