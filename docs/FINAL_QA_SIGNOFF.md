# ORDERra Final QA Sign-off

ORDERra v2 is a demo-safe, portfolio-ready single-restaurant ordering platform.

This document records the final QA scope before sharing ORDERra as a portfolio project, GitHub backup, or reviewer handoff.

ORDERra must remain demo-safe. Payment, refund, webhook, rider, QR, AI Support, human support handoff, support ticket, and admin operations are simulation/reference flows only.

---

## 1. Project identity

Product name:

```txt
ORDERra v2
```

Project type:

```txt
Premium single-restaurant burger ordering platform demo
```

Architecture:

```txt
Laravel API backend + React TypeScript frontend
```

Primary purpose:

- portfolio project
- backend-first architecture reference
- restaurant ordering system reference
- demo-safe payment/refund/rider/webhook simulation reference
- admin/staff operations reference
- controlled demo AI Support reference

Not intended as:

- live payment processing software
- production payment gateway
- real dispatch platform
- real payout system
- PCI-ready card processing system
- real AI support service
- real support ticketing system
- real human agent service

---

## 2. Demo safety sign-off

Required demo flags:

```env
PAYMENTS_DEMO_MODE=true
PAYMENTS_BLOCK_LIVE_EXECUTION=true
PAYMENTS_ALLOW_WEBHOOK_SIMULATION=true
PAYMENTS_SAFE_PROVIDER_MODES=demo,sandbox,test
PAYMENTS_DEMO_PROVIDER_DRIVERS=demo
```

Safety checks:

- [ ] No real card charge.
- [ ] No live payment capture.
- [ ] No real payout.
- [ ] No production payment provider keys.
- [ ] No real PSP provider execution.
- [ ] No live webhook provider dependency.
- [ ] No real rider dispatch.
- [ ] No real courier marketplace integration.
- [ ] No automatic real provider refund.
- [ ] No customer card data storage.
- [ ] No real AI API call.
- [ ] No real human support handoff.
- [ ] No real support ticket API call.
- [ ] No `.env` files included in clean zip or GitHub handoff.

Safety source files:

```txt
docs/demo-safety-rules.md
backend/config/payments.php
backend/app/Services/Payments/DemoPaymentGuard.php
backend/app/Exceptions/Payments/LivePaymentExecutionBlockedException.php
backend/tests/Feature/PaymentDemoGuardTest.php
```

---

## 3. Customer flow sign-off

Customer flow covered:

```txt
Menu → Add to cart → Cart drawer → Checkout → Payment simulation → Confirmation → Back to menu
```

Manual test checklist:

- [ ] Menu loads.
- [ ] Category filters work.
- [ ] Product cards display correctly.
- [ ] Add to cart works.
- [ ] Cart drawer opens.
- [ ] Cart quantity stepper is visually centered.
- [ ] Empty cart CTA is clear.
- [ ] Checkout page loads.
- [ ] Checkout validation triggers correctly.
- [ ] Validation auto-focus works.
- [ ] Delivery quote displays correctly.
- [ ] Pickup quote keeps delivery fee at zero.
- [ ] Dine-in table reference validation works.
- [ ] Dine-in split bill preview works.
- [ ] Split bill defaults to 2 guests.
- [ ] Desktop checkout sticky summary works.
- [ ] Mobile checkout layout works.
- [ ] Payment success simulation creates confirmation.
- [ ] Payment failed simulation does not create order.
- [ ] Payment pending simulation does not create confirmed order.
- [ ] Payment failed message is clear.
- [ ] Payment pending message is neutral.
- [ ] Confirmation wording matches fulfillment mode.
- [ ] Back-to-menu after confirmation works.

Expected result:

```txt
PASS
```

---

## 4. Admin / staff portal sign-off

Admin/staff covered areas:

- dashboard
- orders
- kitchen board
- order detail transition
- payment logs
- refund logs/review
- webhook viewer
- riders
- assignments
- support tickets
- audit logs
- notification logs
- table QR management
- demo simulator
- settings reference pages
- restricted route recovery

Manual test checklist:

- [ ] Admin login works.
- [ ] Staff login works.
- [ ] Admin route boot guard works.
- [ ] Login route cleanup works.
- [ ] Admin navigation is clear.
- [ ] Backend role hardening works.
- [ ] Dashboard quick actions work.
- [ ] Mobile admin header/nav does not overlap.
- [ ] Mobile/tablet active nav auto-scroll works.
- [ ] Table QR copy link works.
- [ ] Kitchen board refresh UX works.
- [ ] Order detail transition polish works.
- [ ] Admin reference pages show manual refresh and last updated meta.
- [ ] Settings reference empty states are clean.
- [ ] Admin simulator shows demo-safe notice and rules.
- [ ] Staff restricted route recovery is clear.
- [ ] Back to dashboard button works for restricted staff route.
- [ ] Reference pages do not look broken when data is empty.
- [ ] Admin simulator does not execute real actions.

Expected result:

```txt
PASS
```

---

## 5. API and backend verification

Run from project root:

```bash
cd backend
php artisan route:list --path=api
php artisan test
```

Expected:

- [ ] API routes load successfully.
- [ ] No route registration error.
- [ ] No missing controller error.
- [ ] No missing middleware error.
- [ ] Payment demo guard tests pass.
- [ ] Backend tests pass locally.
- [ ] No real provider execution is required.

Known note:

If a sandbox or remote environment lacks PHP extensions such as `dom`, `mbstring`, `xml`, or `xmlwriter`, backend tests may fail there even if local tests pass. Local environment should have required PHP extensions installed.

---

## 6. Frontend verification

Run from project root:

```bash
cd frontend
npx tsc -b --pretty false
npm run build
```

Optional:

```bash
npm run lint
```

Expected:

- [ ] TypeScript build passes.
- [ ] Vite build completes.
- [ ] Customer app compiles.
- [ ] Checkout flow compiles.
- [ ] Admin/staff portal compiles.
- [ ] Demo AI assistant module compiles if included.
- [ ] No production payment SDK is required.
- [ ] No real card collection UI is introduced.

Known note:

If a sandbox zip extraction causes executable permission issues for `vite` or `eslint`, run the check locally after `npm install`.

---

## 6A. AI Support, Help Center, and Privacy sign-off

AI Support covered areas:

- floating AI Support dock
- desktop hover expansion
- desktop draggable open panel
- mobile centered open panel above header
- close button using `X`
- friendly controlled Q&A
- smart internal CTA buttons
- Help Center CTA
- Privacy Policy CTA
- human support demo boundary
- premium human support handoff card
- quick reply hygiene
- mobile spacing polish
- input `id` / `name` accessibility fix

Manual AI Support checklist:

- [ ] AI Support opens and closes correctly.
- [ ] Desktop floating dock hover expands from `AI` to `AI Support`.
- [ ] Desktop open panel can be dragged from the header.
- [ ] Mobile closed state stays compact at the screen edge.
- [ ] Mobile open panel stays centered and above the site header.
- [ ] `X` close button works on desktop and mobile.
- [ ] Answers stay friendly, short, and non-technical.
- [ ] Payment answer clearly says no real card, wallet, bank, PayPal, or payment charge is made.
- [ ] Refund answer clearly explains demo refund behavior.
- [ ] Privacy answer can guide users to the Privacy Policy page.
- [ ] Help Center answer can guide users to the Help Center page.
- [ ] Human support answer clearly says the human agent flow is demo-only.
- [ ] Premium support handoff card appears for human support requests.
- [ ] Smart CTA buttons appear only when useful.
- [ ] CTA buttons use internal navigation only.
- [ ] Quick replies do not duplicate.
- [ ] Quick replies have clean bottom spacing on mobile.
- [ ] Unsupported outside topics are politely redirected back to ORDERra topics.
- [ ] No real AI API, support ticket API, or human agent API is called.
- [ ] Browser console does not show input `id` / `name` accessibility warning.

Required AI Support test prompts:

```txt
What is ORDERra?
How payment works?
Will my card be charged?
Refund demo
Privacy
Dine-in QR
Request bill
human agent
contact support
What is Bitcoin price?
```

Expected result:

```txt
PASS
```

AI Support limitation:

```txt
ORDERra AI Support is a controlled demo helper. It uses prepared local answers only. It does not contact OpenAI, external AI services, real human agents, live ticketing systems, real payment providers, real rider dispatch providers, or external support APIs.
```

---

## 7. Clean portfolio zip sign-off

Run from project root:

```bash
python build_project.py
python check_zip_clean.py
```

Expected:

```txt
CLEAN
```

Generated archive:

```txt
project_jutawan.zip
```

Clean archive must exclude:

- [ ] `.env`
- [ ] `.env.*`, except `.env.example`
- [ ] `node_modules/`
- [ ] `vendor/`
- [ ] `frontend/dist/`
- [ ] `build/`
- [ ] `.tsbuildinfo`
- [ ] `.phpunit.result.cache`
- [ ] `.sqlite`
- [ ] `.sqlite3`
- [ ] `database.sqlite`
- [ ] `laravel.log`
- [ ] `storage/logs/`
- [ ] `storage/framework/cache/`
- [ ] `storage/framework/sessions/`
- [ ] `storage/framework/views/`
- [ ] `bootstrap/cache/*.php`

Expected result:

```txt
PASS
```

---

## 8. GitHub backup sign-off

Run from project root:

```bash
python scripts/prepare_github_backup.py
python check_zip_clean.py ../orderra-v2-github-clean.zip
```

Expected:

```txt
CLEAN
```

Generated archive:

```txt
../orderra-v2-github-clean.zip
```

Expected result:

```txt
PASS
```

---

## 9. Raw working zip audit

If checking raw local zip:

```bash
python check_zip_clean.py ../orderra-v2.zip
```

Raw working zip may return:

```txt
NOT CLEAN
```

This is acceptable for private working backups if it contains dependencies, `.env`, build output, logs, or local SQLite database files.

Raw working zip is not suitable for:

- GitHub upload
- public portfolio sharing
- reviewer handoff
- client handoff
- long-term clean backup

Use `project_jutawan.zip` for clean sharing.

---

## 10. Documentation sign-off

Documentation files to review:

```txt
README.md
docs/demo-safety-rules.md
docs/PORTFOLIO_NOTES.md
docs/API_OVERVIEW.md
docs/FINAL_QA_SIGNOFF.md
```

Checklist:

- [ ] README has clean setup instructions.
- [ ] README has final verification checklist.
- [ ] Demo safety rules are clear.
- [ ] Portfolio notes describe ORDERra correctly.
- [ ] API overview explains public, customer, admin, and simulation routes.
- [ ] Final QA sign-off exists.
- [ ] AI Support notes explain friendly Q&A, smart CTA buttons, Help Center CTA, Privacy Policy CTA, and demo-only human support.
- [ ] AI Support notes explain quick reply hygiene, mobile spacing polish, support handoff card, and input accessibility fix.
- [ ] No document claims real payment processing.
- [ ] No document claims real rider dispatch.
- [ ] No document claims real AI support, real ticketing, or real human agent service.
- [ ] No document claims production payment readiness.

Expected result:

```txt
PASS
```

---

## 11. Safe portfolio wording

Recommended short description:

> ORDERra is a premium demo-safe burger ordering platform built with Laravel API and React TypeScript. It supports customer ordering, delivery/pickup/dine-in flows, QR table sessions, split bill preview, payment simulation, refund simulation, rider tracking simulation, webhook references, staff/admin operations, Help Center, Privacy Policy, and controlled demo AI Support.

Recommended technical description:

> ORDERra is a backend-first restaurant ordering system demo with a Laravel API, React TypeScript frontend, modular order/payment/fulfillment domains, demo-safe payment guardrails, admin operations tooling, Help Center, Privacy Policy, controlled demo AI Support, and clean portfolio handoff scripts. It is built as a realistic architecture reference while intentionally blocking live payment, payout, webhook, dispatch, external AI, real ticketing, and human agent execution.

Avoid:

- accepts real payments
- processes card payments
- dispatches real riders
- live payment gateway
- production payout system
- PCI-ready card processing
- live webhook integration
- real AI support
- real human agent service
- live support ticketing system

---

## 12. Final sign-off status

Use this section manually before final sharing.

```txt
Customer flow QA:              PASS / FAIL
Admin staff QA:                PASS / FAIL
Backend route check:           PASS / FAIL
Backend tests:                 PASS / FAIL
Frontend TypeScript check:     PASS / FAIL
Frontend build:                PASS / FAIL
Clean portfolio zip:           PASS / FAIL
GitHub backup zip:             PASS / FAIL
Demo safety review:            PASS / FAIL
AI Support review:             PASS / FAIL
Documentation review:          PASS / FAIL
```

Final decision:

```txt
ORDERra portfolio handoff status: READY / NOT READY
```

Reviewer note:

```txt
ORDERra is demo-safe. All payment, refund, webhook, rider, QR, AI Support, human support handoff, support ticket, and admin operation flows are simulation/reference flows only.
```
