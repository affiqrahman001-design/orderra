# ORDERra API Overview

ORDERra uses an API-first backend under the Laravel API prefix:

```txt
/api/v1
```

This document gives a reviewer-friendly overview of the main API surface. It is not a replacement for backend route files, controllers, request validation, or tests.

ORDERra remains a demo-safe portfolio system. Payment, refund, webhook, rider, QR, AI Support, human support handoff, support ticket, and admin simulation/reference flows must not execute real financial, logistics, external AI, or live support actions.

---

## 1. Base path

All endpoints below assume this base path:

```txt
/api/v1
```

Example:

```txt
GET /api/v1/health
GET /api/v1/catalog/items
POST /api/v1/payments/intents
POST /api/v1/admin/auth/login
```

Do not set `APP_URL=/api/v1`.

`APP_URL` should remain the app base URL, while `/api/v1` is the route prefix handled by Laravel routing.

---

## 2. Response style

Most successful responses use one of these shapes:

```json
{
  "data": {}
}
```

```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

Some list endpoints may include pagination metadata. Some detail endpoints wrap one resource in `data`.

Frontend adapters should unwrap nested envelopes consistently instead of assuming every endpoint returns the exact same payload depth.

---

## 3. Authentication overview

ORDERra separates public customer flows and staff/admin reference flows.

### Public customer routes

Most customer routes are public demo routes and may use:

```txt
X-Cart-Token: <cart-token>
```

for guest cart continuity.

### Customer auth routes

```txt
POST /api/v1/auth/register
POST /api/v1/auth/login
POST /api/v1/auth/logout
GET  /api/v1/auth/me
```

Protected customer auth routes use:

```txt
Authorization: Bearer <token>
```

### Admin auth routes

```txt
POST /api/v1/admin/auth/login
POST /api/v1/admin/auth/logout
GET  /api/v1/admin/auth/me
```

Admin/staff routes use bearer-token style access through backend guard middleware.

---

## 4. Health endpoint

| Method | Endpoint         | Purpose                                                                 |
| ------ | ---------------- | ----------------------------------------------------------------------- |
| GET    | `/api/v1/health` | Lightweight readiness response showing app name, version, and demo mode |

Example response:

```json
{
  "ok": true,
  "app": "ORDERra",
  "mode": "demo",
  "version": "v1"
}
```

---

## 5. Customer catalog API

| Method | Endpoint                       | Purpose                |
| ------ | ------------------------------ | ---------------------- |
| GET    | `/api/v1/catalog`              | Customer menu overview |
| GET    | `/api/v1/catalog/categories`   | Menu categories        |
| GET    | `/api/v1/catalog/items`        | Menu item list         |
| GET    | `/api/v1/catalog/items/{item}` | Menu item detail       |

Frontend usage:

- menu page
- category filters
- product detail modal/card
- local fallback comparison

Notes:

- Menu is burger-focused.
- Drinks should stay visually secondary and usually appear later in ordering flow.
- API can support richer catalog structure than current UI needs.

---

## 6. Promo API

| Method | Endpoint                  | Purpose                                        |
| ------ | ------------------------- | ---------------------------------------------- |
| GET    | `/api/v1/promos`          | List demo promo records                        |
| POST   | `/api/v1/promos/validate` | Validate promo code against cart/order context |

Promo validation should return clear success/failure messaging and should not hardcode pricing logic in frontend.

---

## 7. Cart API

| Method | Endpoint                      | Purpose                                            |
| ------ | ----------------------------- | -------------------------------------------------- |
| POST   | `/api/v1/cart`                | Create or initialize cart                          |
| GET    | `/api/v1/cart`                | Get current cart using token/header context        |
| GET    | `/api/v1/cart/{cartToken}`    | Get cart by UUID token                             |
| POST   | `/api/v1/cart/lines`          | Add item line                                      |
| PATCH  | `/api/v1/cart/lines/{lineId}` | Update quantity/modifiers                          |
| DELETE | `/api/v1/cart/lines/{lineId}` | Remove cart line                                   |
| PATCH  | `/api/v1/cart/fulfillment`    | Update delivery/pickup/dine-in fulfillment context |
| PATCH  | `/api/v1/cart/tip`            | Update tip                                         |
| PATCH  | `/api/v1/cart/promo`          | Apply/update promo                                 |

Expected frontend behavior:

- keep `X-Cart-Token` once received
- never trust frontend totals as final
- always refresh quote/summary before checkout
- keep empty cart CTA friendly

---

## 8. Pricing quote API

| Method | Endpoint                | Purpose                                                                 |
| ------ | ----------------------- | ----------------------------------------------------------------------- |
| POST   | `/api/v1/pricing/quote` | Return cart subtotal, fees, tax, tip, discount, delivery fee, and total |

Quote responsibilities:

- subtotal
- delivery fee
- service/small-order fee if applicable
- tax estimate
- promo discount
- tip
- final total
- fulfillment-specific notes

Frontend should treat pricing quote as backend-authoritative.

---

## 9. Checkout and order API

| Method | Endpoint                                  | Purpose                            |
| ------ | ----------------------------------------- | ---------------------------------- |
| POST   | `/api/v1/checkout`                        | Main checkout/order creation entry |
| POST   | `/api/v1/orders`                          | Alternative order creation entry   |
| GET    | `/api/v1/orders/{orderPublicId}`          | Customer order detail              |
| GET    | `/api/v1/orders/{orderPublicId}/timeline` | Customer order timeline            |
| POST   | `/api/v1/orders/{orderPublicId}/refunds`  | Request refund for an order        |

Checkout behavior:

- delivery success payment simulation can create order
- failed payment simulation must not create order
- pending payment simulation must not create confirmed order
- pickup should keep delivery fee at zero
- dine-in should validate table/session context
- confirmation wording should match fulfillment mode

---

## 10. Payment demo API

| Method | Endpoint                                                    | Purpose                                              |
| ------ | ----------------------------------------------------------- | ---------------------------------------------------- |
| POST   | `/api/v1/payments/intents`                                  | Create demo payment intent                           |
| GET    | `/api/v1/payments/intents/{paymentIntentPublicId}`          | View payment intent                                  |
| POST   | `/api/v1/payments/intents/{paymentIntentPublicId}/simulate` | Simulate success, failed, or pending payment outcome |

Allowed simulation outcomes:

```txt
success
failed
pending
```

Safety rules:

- no real charge
- no real capture
- no real payout
- no real payment SDK required
- no customer card data storage
- demo/sandbox/test provider mode only

Payment simulation is for customer flow proof only. It must not be presented as live payment processing.

---

## 11. Dine-in and QR API

| Method | Endpoint                                                    | Purpose                                         |
| ------ | ----------------------------------------------------------- | ----------------------------------------------- |
| POST   | `/api/v1/dine-in/qr-sessions`                               | Open/reuse dine-in QR session                   |
| POST   | `/api/v1/dine-in/sessions/open`                             | Alternative open session route                  |
| GET    | `/api/v1/dine-in/qr-sessions/by-code/{sessionCode}`         | Resolve QR session by code                      |
| GET    | `/api/v1/qr/{sessionCode}`                                  | Short public QR resolver for printed collateral |
| GET    | `/api/v1/dine-in/sessions/{qrSessionPublicId}`              | Dine-in session detail                          |
| POST   | `/api/v1/dine-in/sessions/{qrSessionPublicId}/attach-cart`  | Attach cart to dine-in table session            |
| POST   | `/api/v1/dine-in/sessions/{qrSessionPublicId}/call-waiter`  | Simulate waiter request                         |
| POST   | `/api/v1/dine-in/sessions/{qrSessionPublicId}/request-bill` | Simulate bill request                           |
| POST   | `/api/v1/dine-in/sessions/{qrSessionPublicId}/expire`       | Expire demo session                             |

QR flow notes:

- QR table sessions are demo-safe
- QR rotation is admin-controlled
- printed short QR route points to the same resolver logic
- QR session should not expose sensitive admin data

---

## 12. Split bill API

| Method | Endpoint                                                                                   | Purpose                                     |
| ------ | ------------------------------------------------------------------------------------------ | ------------------------------------------- |
| POST   | `/api/v1/dine-in/split-bills`                                                              | Create standalone split bill demo/reference |
| GET    | `/api/v1/dine-in/sessions/{qrSessionPublicId}/split-bill`                                  | View split bill plan for session            |
| POST   | `/api/v1/dine-in/sessions/{qrSessionPublicId}/split-bill`                                  | Create/update split bill plan               |
| POST   | `/api/v1/dine-in/sessions/{qrSessionPublicId}/split-bill/{splitBillPlanPublicId}/finalize` | Finalize demo split bill plan               |

Supported split concepts:

- equal split
- split by item
- default two guests for dine-in preview
- primary payer/reference owner remains clear

---

## 13. Customer refund and support ticket API

| Method | Endpoint                                          | Purpose                        |
| ------ | ------------------------------------------------- | ------------------------------ |
| POST   | `/api/v1/refunds`                                 | Standalone refund request      |
| GET    | `/api/v1/refunds/{refundPublicId}`                | Customer refund detail         |
| POST   | `/api/v1/support/tickets`                         | Create support ticket          |
| GET    | `/api/v1/support/tickets/{supportTicketPublicId}` | Customer support ticket detail |

Refund/support ticket behavior:

- refund is demo review/simulation
- no automatic provider refund execution
- support ticket workflow is operational reference only
- no live support ticketing provider is contacted
- no real human agent is assigned
- missing item / wrong item / late delivery scenarios can be simulated

---

## 14. Admin dashboard API

All admin routes use:

```txt
/api/v1/admin
```

and require admin/staff guard middleware.

| Method | Endpoint                       | Purpose                 |
| ------ | ------------------------------ | ----------------------- |
| GET    | `/api/v1/admin/dashboard`      | Dashboard summary       |
| GET    | `/api/v1/admin/demo-scenarios` | Demo scenario catalogue |

Frontend usage:

- admin overview cards
- quick actions
- demo-safe scenario explanation
- reviewer walkthrough

---

## 15. Admin orders API

| Method | Endpoint                                      | Purpose                 |
| ------ | --------------------------------------------- | ----------------------- |
| GET    | `/api/v1/admin/orders`                        | Order list              |
| GET    | `/api/v1/admin/orders/{orderPublicId}`        | Order detail            |
| POST   | `/api/v1/admin/orders/{orderPublicId}/status` | Transition order status |

Order status transitions should remain audited and demo-safe.

---

## 16. Admin payment logs API

| Method | Endpoint                                                     | Purpose                    |
| ------ | ------------------------------------------------------------ | -------------------------- |
| GET    | `/api/v1/admin/payments/intents`                             | Payment intent list        |
| GET    | `/api/v1/admin/payments/intents/{paymentIntentPublicId}`     | Payment intent detail      |
| GET    | `/api/v1/admin/payments/attempts`                            | Payment attempt list       |
| GET    | `/api/v1/admin/payments/attempts/{paymentAttemptId}`         | Payment attempt detail     |
| GET    | `/api/v1/admin/payments/transactions`                        | Payment transaction list   |
| GET    | `/api/v1/admin/payments/transactions/{paymentTransactionId}` | Payment transaction detail |

These routes are logs/reference views only. They must not expose production provider secrets or real card data.

---

## 17. Admin refunds API

| Method | Endpoint                                        | Purpose                         |
| ------ | ----------------------------------------------- | ------------------------------- |
| GET    | `/api/v1/admin/refunds`                         | Refund list                     |
| GET    | `/api/v1/admin/refunds/{refundPublicId}`        | Refund detail                   |
| POST   | `/api/v1/admin/refunds/{refundPublicId}/review` | Simulate refund review decision |

Refund review may simulate:

- approved
- rejected
- partial refund
- store credit / compensation reference

No provider refund execution should happen in the demo build.

---

## 18. Admin webhooks API

| Method | Endpoint                                           | Purpose              |
| ------ | -------------------------------------------------- | -------------------- |
| GET    | `/api/v1/admin/webhooks`                           | Webhook event list   |
| GET    | `/api/v1/admin/webhooks/{opsWebhookEventPublicId}` | Webhook event detail |

This is an operations viewer for demo/simulated webhook events.

---

## 19. Admin riders and assignments API

| Method | Endpoint                                                                | Purpose                           |
| ------ | ----------------------------------------------------------------------- | --------------------------------- |
| GET    | `/api/v1/admin/riders`                                                  | Rider list                        |
| GET    | `/api/v1/admin/riders/pool`                                             | Available rider pool              |
| GET    | `/api/v1/admin/riders/assignments`                                      | Assignment list                   |
| POST   | `/api/v1/admin/riders/orders/{orderPublicId}/assignments`               | Simulate assigning rider to order |
| GET    | `/api/v1/admin/riders/assignments/{deliveryAssignmentPublicId}`         | Assignment detail                 |
| POST   | `/api/v1/admin/riders/assignments/{deliveryAssignmentPublicId}/advance` | Advance simulated rider timeline  |

Rider API is simulation only. It must not connect to a real courier marketplace.

---

## 20. Admin dine-in, tables, and QR API

| Method | Endpoint                                          | Purpose                   |
| ------ | ------------------------------------------------- | ------------------------- |
| GET    | `/api/v1/admin/tables`                            | Table list                |
| POST   | `/api/v1/admin/tables/{tablePublicId}/qr-session` | Rotate table QR session   |
| GET    | `/api/v1/admin/dine-in/sessions`                  | Dine-in QR session list   |
| GET    | `/api/v1/admin/dine-in/sessions/{qrSessionId}`    | Dine-in QR session detail |

QR session rotation should be audited as an admin demo operation.

---

## 21. Admin support ticket, audit, and notification API

| Method | Endpoint                                                           | Purpose                          |
| ------ | ------------------------------------------------------------------ | -------------------------------- |
| GET    | `/api/v1/admin/support/tickets`                                    | Support ticket list              |
| GET    | `/api/v1/admin/support/tickets/{supportTicketPublicId}`            | Support ticket detail            |
| POST   | `/api/v1/admin/support/tickets/{supportTicketPublicId}/transition` | Transition support ticket status |
| GET    | `/api/v1/admin/audit-logs`                                         | Audit log list                   |
| GET    | `/api/v1/admin/audit-logs/{auditLogPublicId}`                      | Audit log detail                 |
| GET    | `/api/v1/admin/notification-logs`                                  | Notification log list            |
| GET    | `/api/v1/admin/notification-logs/{notificationLogId}`              | Notification log detail          |

These routes make the admin panel feel operational without executing real external services, live support ticketing, external AI, or real human agent workflows.

---

## 22. Admin reference settings API

| Domain          | Endpoints                                                                                          |
| --------------- | -------------------------------------------------------------------------------------------------- |
| Branches        | `GET/POST /api/v1/admin/branches`, `GET/PATCH /api/v1/admin/branches/{branchId}`                   |
| Delivery zones  | `GET/POST /api/v1/admin/delivery-zones`, `GET/PATCH /api/v1/admin/delivery-zones/{deliveryZoneId}` |
| Tax rules       | `GET/POST /api/v1/admin/tax-rules`, `GET/PATCH /api/v1/admin/tax-rules/{taxRuleId}`                |
| Fee rules       | `GET/POST /api/v1/admin/fee-rules`, `GET/PATCH /api/v1/admin/fee-rules/{feeRuleId}`                |
| Menu categories | `GET/POST /api/v1/admin/menu-categories`, `GET/PATCH /api/v1/admin/menu-categories/{categoryId}`   |
| Menu items      | `GET/POST /api/v1/admin/menu-items`, `GET/PATCH /api/v1/admin/menu-items/{itemId}`                 |
| Promos          | `GET/POST /api/v1/admin/promos`, `GET/PATCH /api/v1/admin/promos/{promoId}`                        |
| Modifier groups | `GET/POST /api/v1/admin/modifier-groups`, `GET/PATCH /api/v1/admin/modifier-groups/{groupId}`      |

These are reference/admin configuration routes. Empty states should remain clean in the frontend.

---

## 23. Admin-only simulation API

All simulation routes use:

```txt
/api/v1/simulation
```

and require admin guard middleware.

### Payment simulation hooks

| Method | Endpoint                                                                   | Purpose                           |
| ------ | -------------------------------------------------------------------------- | --------------------------------- |
| POST   | `/api/v1/simulation/payments/intents/{paymentIntentPublicId}/webhooks`     | Inject demo payment webhook event |
| POST   | `/api/v1/simulation/payments/intents/{paymentIntentPublicId}/refund-hooks` | Inject demo refund hook event     |

### Rider simulation

| Method | Endpoint                                                                     | Purpose                           |
| ------ | ---------------------------------------------------------------------------- | --------------------------------- |
| POST   | `/api/v1/simulation/riders/orders/{orderPublicId}/assignments`               | Create simulated rider assignment |
| GET    | `/api/v1/simulation/riders/assignments/{deliveryAssignmentPublicId}`         | View simulated assignment         |
| POST   | `/api/v1/simulation/riders/assignments/{deliveryAssignmentPublicId}/advance` | Advance simulated rider state     |

### Ops webhook simulation

| Method | Endpoint                                                           | Purpose                      |
| ------ | ------------------------------------------------------------------ | ---------------------------- |
| POST   | `/api/v1/simulation/ops/webhooks`                                  | Create simulated ops webhook |
| GET    | `/api/v1/simulation/ops/webhooks/{opsWebhookEventPublicId}`        | View simulated ops webhook   |
| POST   | `/api/v1/simulation/ops/webhooks/{opsWebhookEventPublicId}/replay` | Simulate replay action       |

Simulation routes are admin-only and demo-safe. They must not call live third-party services.

---

## 24. Webhook simulation API

| Method | Endpoint                    | Purpose                                                    |
| ------ | --------------------------- | ---------------------------------------------------------- |
| POST   | `/api/v1/webhooks/simulate` | Create simulated webhook event through webhook route group |

This route remains guarded by admin middleware in the current route setup.

It is not a public live provider webhook endpoint.

---

## 25. Internal routes

Internal routes are mounted under:

```txt
/api/v1/internal
```

Current internal route file is intentionally minimal.

Do not expose future internal routes publicly without:

- dedicated auth
- rate limit
- audit logging
- environment restriction
- deployment/VPN decision

---

## 26. Throttle groups

ORDERra uses named throttle groups such as:

- `public-api`
- `admin-auth`
- `admin-reference`
- `payment-simulation`
- `webhook-simulation`

Purpose:

- protect public customer routes
- protect auth routes
- reduce simulation abuse
- keep admin reference endpoints stable
- make demo safer during review

---

## 27. Frontend integration guidance

Frontend should use a dedicated API layer instead of calling `fetch` directly everywhere.

Recommended frontend API rules:

- keep base URL in environment config
- keep endpoint strings centralized by feature
- preserve `X-Cart-Token`
- attach bearer token only for protected routes
- normalize `{ data }` response envelopes
- display loading/error/empty states
- keep payment language demo-safe
- keep AI Support answers controlled, friendly, and demo-safe
- route AI Support CTA buttons internally only
- never send card numbers or real bank details
- never load production payment SDKs in this demo
- never call external AI, live support ticketing, or real human agent APIs in this demo

AI Support note:

ORDERra AI Support is currently a frontend-controlled demo helper. It uses prepared local answers and internal CTA navigation only. It does not require a backend AI endpoint, OpenAI API, external AI provider, live ticketing provider, or real human agent connection.

---

## 28. Demo safety checklist for API review

Before sharing or demoing ORDERra:

1. Confirm `PAYMENTS_DEMO_MODE=true`.
2. Confirm `PAYMENTS_BLOCK_LIVE_EXECUTION=true`.
3. Confirm no production PSP keys exist in `.env`.
4. Confirm `.env` is not included in clean zip or GitHub.
5. Confirm payment simulation routes do not execute real provider calls.
6. Confirm webhook simulation routes are admin-only.
7. Confirm rider simulation does not call real courier APIs.
8. Confirm frontend does not collect real card data.
9. Confirm AI Support uses prepared local answers only.
10. Confirm AI Support CTA buttons use internal navigation only.
11. Confirm human support handoff remains demo-only.
12. Confirm support ticket routes are treated as demo/reference flows only.
13. Confirm admin simulation UI explains demo-safe behavior.
14. Run backend route list and tests.

Recommended verification:

```bash
cd backend
php artisan route:list --path=api
php artisan test
```

```bash
cd frontend
npm run build
```

```bash
python build_project.py
python check_zip_clean.py
```

---

## 29. Source of truth files

Route definitions:

```txt
backend/routes/api.php
backend/routes/customer.php
backend/routes/admin.php
backend/routes/simulation.php
backend/routes/webhooks.php
backend/routes/internal.php
```

Safety references:

```txt
docs/demo-safety-rules.md
backend/config/payments.php
backend/app/Services/Payments/DemoPaymentGuard.php
backend/app/Exceptions/Payments/LivePaymentExecutionBlockedException.php
backend/tests/Feature/PaymentDemoGuardTest.php
```

Portfolio references:

```txt
README.md
docs/PORTFOLIO_NOTES.md
docs/API_OVERVIEW.md
docs/FINAL_QA_SIGNOFF.md
docs/api-contract.md
docs/architecture.md
docs/order-lifecycle.md
```
