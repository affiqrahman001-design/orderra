# ORDERra Step 5 — API Contract Lock

Dokumen ini mengunci API contract ORDERra sebelum backend implementation besar dimulakan.

Scope dokumen ini:

- lock API style
- lock route groups
- lock request / response shape
- lock validation expectations
- lock pagination / filter / sort rules
- lock endpoint contract utama untuk frontend dan backend

Dokumen ini **bukan** controller code.
Dokumen ini **bukan** service code.
Dokumen ini ialah source of truth untuk:

- backend routes
- form requests
- API resources
- frontend API layer
- test contract

---

# [1] API design principles

## 1.1 API versioning

Semua route utama menggunakan prefix:

- `/api/v1`

Contoh:

- `/api/v1/auth/login`
- `/api/v1/catalog/items`
- `/api/v1/cart/current`

## 1.2 Route file mapping

Route group dikunci ikut fail berikut:

- `backend/routes/auth.php`
- `backend/routes/customer.php`
- `backend/routes/admin.php`
- `backend/routes/simulation.php`
- `backend/routes/webhooks.php`
- `backend/routes/internal.php`

## 1.3 Auth mode

ORDERra menggunakan **Laravel Sanctum**.

Untuk contract semasa, protected endpoints anggap guna:

- `Authorization: Bearer <token>`

Bagi flow guest cart:

- guna header `X-Cart-Token: <token>`

Rule:

- customer login => boleh guna bearer token
- guest checkout => boleh kekal tanpa login, tetapi mesti ada `X-Cart-Token`

## 1.4 ID exposure rule

Path parameter dan public reference menggunakan:

- `public_id`
- `slug`
- `session_code`
- `share_reference`

API tidak expose numeric database `id`.

## 1.5 Response envelope

Semua response berjaya ikut bentuk asas ini:

```json
{
  "data": {},
  "meta": {},
  "links": {}
}
```
