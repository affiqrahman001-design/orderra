# ORDERra Architecture

## What ORDERra is
ORDERra is a premium single-restaurant burger ordering demo platform.

It is not a marketplace.
It represents one premium fast-food burger brand from the customer product perspective.

## Core Direction
ORDERra is designed as:

- API-first backend
- separate frontend web app
- future-ready for PWA and mobile app reuse
- demo-safe system only for now

## Tech Direction
- Backend: Laravel
- Frontend: React + TypeScript
- Database: PostgreSQL
- Cache / Queue / Realtime support: Redis
- Auth: Laravel Sanctum

## Fulfillment Modes
ORDERra must support:
- delivery
- pickup
- dine_in

## Functional Scope
ORDERra must support:
- catalog browsing
- cart and checkout
- pricing calculation
- delivery / pickup / dine_in
- QR table ordering
- split bill
- payment simulation
- refund simulation
- webhook simulation
- rider simulation
- admin reference tools

## Demo-Safe Rule
ORDERra is a demo system.
There must be:
- no live payment capture
- no live payout
- no real charge execution
- no real webhook dependency
- no real rider dispatch dependency

Everything operational must be simulation-ready and sandbox-safe.

## Build Philosophy
ORDERra must be built backend-first:
1. architecture
2. modules
3. database plan
4. API contract
5. backend foundation
6. frontend integration
7. admin/reference tooling
