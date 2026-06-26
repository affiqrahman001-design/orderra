# ORDERra Demo Safety Rules

ORDERra is a portfolio/demo ordering platform. It is designed to look realistic, but it must not process real money, real payouts, live provider webhooks, real delivery dispatch, external AI support, live support tickets, or real human agent handoff.

This document is the safety boundary for all customer, staff, admin, payment, refund, webhook, rider, QR, AI Support, human support handoff, support ticket, and simulation features.

---

## 1. Core principle

ORDERra must remain demo-safe.

The system may simulate real-world operational flows, but it must not perform real financial or logistics execution.

Allowed project purpose:

- portfolio demo
- backend architecture reference
- restaurant ordering system reference
- payment abstraction reference
- dispatch/tracking reference
- admin operations reference

Not allowed project purpose in this build:

- live payment collection
- live payment capture
- real payout
- real payment settlement
- real rider dispatch
- live third-party webhook processing
- external AI support execution
- live support ticketing execution
- real human agent handoff
- production customer transaction processing

---

## 2. Hard rules

The following are blocked for this demo build:

- no real card charge
- no live payment capture
- no live payout execution
- no production payment keys
- no real PSP provider driver
- no live webhook provider dependency
- no real rider dispatch integration
- no real delivery marketplace integration
- no automatic real refund execution
- no production banking / ACH / FPX / DuitNow execution
- no customer card data storage
- no external AI API execution
- no live support ticket provider execution
- no real human agent handoff
- no PCI scope behavior

---

## 3. Required demo environment flags

The public portfolio build should keep these values enabled:

```env
PAYMENTS_DEMO_MODE=true
PAYMENTS_BLOCK_LIVE_EXECUTION=true
PAYMENTS_ALLOW_WEBHOOK_SIMULATION=true
PAYMENTS_SAFE_PROVIDER_MODES=demo,sandbox,test
PAYMENTS_DEMO_PROVIDER_DRIVERS=demo
```

Do not disable demo mode for portfolio use.

Do not add production PSP keys to `.env`.

Do not expose private `.env` files in GitHub, clean zip, screenshots, screen recordings, or client handoff archives.

---

## 4. Allowed simulation behavior

The following are allowed because they do not execute real transactions:

- simulated payment success
- simulated payment failure
- simulated payment pending state
- simulated payment intent records
- simulated payment attempts
- simulated payment transactions
- simulated webhook events
- simulated refund review
- simulated partial refund outcome
- simulated store credit outcome
- simulated rider assignment
- simulated rider movement
- simulated ETA updates
- simulated QR table sessions
- controlled demo AI Support answers
- internal AI Support CTA navigation
- simulated human support handoff card
- simulated support ticket workflow
- simulated admin operations
- seeded demo orders, refunds, riders, webhooks, support tickets, and audit logs

---

## 5. Server-side guard expectation

Demo safety must not rely on frontend UI only.

The backend must enforce payment safety through server-side guardrails.

Expected backend safety behavior:

- payment providers must remain in `demo`, `sandbox`, or `test` mode
- real provider drivers must be rejected
- providers marked as live must be rejected when live execution is blocked
- payment simulation must fail closed if demo mode is disabled
- webhook simulation must be explicitly allowed by config
- admin simulation routes must remain authenticated / staff-only
- audit logs should describe demo/admin simulation actions clearly

Current safety reference points:

- `backend/config/payments.php`
- `backend/app/Services/Payments/DemoPaymentGuard.php`
- `backend/app/Exceptions/Payments/LivePaymentExecutionBlockedException.php`
- `backend/tests/Feature/PaymentDemoGuardTest.php`

---

## 6. Frontend safety expectation

The frontend may show realistic payment, refund, rider, webhook, and QR experiences, but it must only call demo-safe API flows.

Frontend must not:

- load a real payment SDK
- collect real card data
- send card numbers to ORDERra APIs
- expose production provider keys
- present demo payment as a real charge
- call external AI APIs
- call live support ticket APIs
- connect to real human agents
- imply that real payout, real rider dispatch, real AI support, or real human support has happened

Customer-facing wording should stay clear:

- payment is simulated
- pending payment does not create a real charge
- failed payment does not create an order
- rider tracking and ETA are demo timeline values
- AI Support uses prepared local answers only
- human support handoff is demo-only

---

## 7. Admin / staff safety expectation

Admin and staff modules may display realistic operations, but they must remain reference/simulation tools.

Allowed:

- view orders
- view payment logs
- view webhook logs
- simulate rider assignment
- simulate rider movement
- simulate refund review
- rotate demo QR sessions
- view support tickets
- view audit logs
- view settings reference pages

Not allowed:

- trigger real payout
- trigger real capture
- trigger real refund to a payment provider
- push to a real courier marketplace
- replay to a live third-party webhook endpoint
- store or reveal production secrets

---

## 8. Production upgrade boundary

Production payment, dispatch, and webhook support must be treated as a separate upgrade project.

A real production upgrade would require at minimum:

- PSP contract and account approval
- PCI scope review
- provider SDK integration
- webhook signature verification
- idempotency keys
- settlement reconciliation
- refund reconciliation
- fraud / risk handling
- real customer notification policy
- production logging policy
- secret management
- incident response plan
- legal / compliance review
- real delivery partner contract if dispatch is enabled

Do not present the current demo build as production payment-ready.

The current architecture is provider-ready as a reference, but execution remains intentionally blocked.

---

## 9. Reviewer checklist

Before sharing ORDERra publicly:

1. Confirm `.env` files are not included in clean zip or GitHub.
2. Confirm `.env.example` remains available.
3. Confirm `PAYMENTS_DEMO_MODE=true`.
4. Confirm `PAYMENTS_BLOCK_LIVE_EXECUTION=true`.
5. Confirm no production PSP keys are present.
6. Confirm no real payment SDK is loaded in the frontend.
7. Confirm no live webhook provider URL is configured.
8. Confirm no real courier/dispatch provider is configured.
9. Run backend tests that cover the demo payment guard.
10. Run clean archive check before sharing the project zip.

Recommended commands:

```bash
python build_project.py
python check_zip_clean.py
cd backend && php artisan test
cd frontend && npm run build
```

---

## 10. Safe wording for portfolio

Recommended wording:

> ORDERra is a demo-safe restaurant ordering platform with simulated payment, refund, webhook, rider, QR, AI Support, human support handoff, support ticket, and admin operations.

Avoid wording like:

- “accepts real payments”
- “processes card payments”
- “dispatches real riders”
- “integrates live webhooks”
- “production-ready payment gateway”
- “real payout support”
- “real AI support service”
- “live support ticketing system”
- “real human agent service”

Better wording:

- “payment architecture reference”
- “demo payment simulation”
- “sandbox-ready provider abstraction”
- “admin operations reference”
- “rider tracking simulation”
- “webhook simulation viewer”
- “controlled demo AI Support”
- “demo-only human support handoff”
- “support ticket reference workflow”
