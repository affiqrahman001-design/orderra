# ORDERra Documentation Index

This folder contains the main documentation for ORDERra v2.

ORDERra is a demo-safe, portfolio-ready single-restaurant burger ordering platform built with a Laravel API backend and React TypeScript frontend.

Payment, refund, webhook, rider, QR, support, and admin operations are simulation/reference flows only.

---

## 1. Recommended reading order

For portfolio reviewers:

1. Start with the root `README.md`.
2. Read `PORTFOLIO_NOTES.md`.
3. Read `demo-safety-rules.md`.
4. Read `API_OVERVIEW.md`.
5. Read `FINAL_QA_SIGNOFF.md`.

For developers:

1. Start with the root `README.md`.
2. Read `architecture.md`.
3. Read `api-contract.md`.
4. Read `order-lifecycle.md`.
5. Read `API_OVERVIEW.md`.
6. Read `FINAL_QA_SIGNOFF.md`.

For demo safety review:

1. Read `demo-safety-rules.md`.
2. Check `API_OVERVIEW.md`.
3. Check `FINAL_QA_SIGNOFF.md`.
4. Confirm clean archive verification in the root `README.md`.

---

## 2. Main documents

| Document               | Purpose                                                                                                     |
| ---------------------- | ----------------------------------------------------------------------------------------------------------- |
| `PORTFOLIO_NOTES.md`   | Explains how to present ORDERra as a premium demo-safe portfolio project                                    |
| `demo-safety-rules.md` | Defines demo safety boundaries for payment, refund, webhook, rider, QR, and admin simulation                |
| `API_OVERVIEW.md`      | Gives a reviewer-friendly overview of public, customer, admin, simulation, webhook, and internal API groups |
| `FINAL_QA_SIGNOFF.md`  | Final QA checklist for customer flow, admin/staff portal, backend, frontend, clean zip, and demo safety     |
| `architecture.md`      | Architecture notes for the Laravel API, React frontend, and backend-first structure                         |
| `api-contract.md`      | API contract/reference notes                                                                                |
| `order-lifecycle.md`   | Order status and fulfillment lifecycle notes                                                                |

---

## 3. Clean handoff documents

Use these docs when preparing ORDERra for GitHub, portfolio sharing, or reviewer handoff:

- root `README.md`
- `PORTFOLIO_NOTES.md`
- `demo-safety-rules.md`
- `FINAL_QA_SIGNOFF.md`

Recommended commands from the project root:

```bash
python build_project.py
python check_zip_clean.py
```

Expected result:

```txt
CLEAN
```

The clean portfolio archive is:

```txt
project_jutawan.zip
```

---

## 4. API review documents

Use these docs when reviewing backend/API structure:

- `API_OVERVIEW.md`
- `api-contract.md`
- `architecture.md`
- `order-lifecycle.md`

Recommended backend checks:

```bash
cd backend
php artisan route:list --path=api
php artisan test
```

---

## 5. Frontend review notes

Frontend behavior should remain aligned with the API and demo-safety boundary.

Recommended frontend check:

```bash
cd frontend
npm run build
```

Frontend must not:

- collect real card data
- load a production payment SDK
- expose production payment keys
- imply that payment, refund, rider, or webhook actions are real
- bypass backend pricing/order/payment state

---

## 6. Demo safety reminder

ORDERra must remain demo-safe.

Allowed:

- simulated payment success
- simulated payment failure
- simulated payment pending state
- simulated refund review
- simulated webhook events
- simulated rider assignment
- simulated rider movement
- simulated QR table sessions
- simulated admin operations

Blocked:

- real card charge
- live payment capture
- real payout
- production payment provider keys
- live webhook provider dependency
- real rider dispatch
- real provider refund execution
- customer card data storage

Safe wording:

> ORDERra is a demo-safe restaurant ordering platform with simulated payment, refund, webhook, rider, QR, and admin operations.

---

## 7. Final handoff checklist

Before sharing ORDERra:

1. Run backend route check.
2. Run backend tests.
3. Run frontend build.
4. Generate clean zip.
5. Run clean zip checker.
6. Confirm `.env` files are excluded.
7. Confirm dependencies and build outputs are excluded.
8. Confirm demo payment flags remain enabled.
9. Review `demo-safety-rules.md`.
10. Review `FINAL_QA_SIGNOFF.md`.

Recommended full verification:

```bash
cd backend && php artisan route:list --path=api
cd backend && php artisan test
cd frontend && npm run build
python build_project.py
python check_zip_clean.py
```
