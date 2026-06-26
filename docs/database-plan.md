# ORDERra Step 4 — Database Plan Lock

Dokumen ini mengunci database plan ORDERra sebelum migrations sebenar ditulis.

Scope dokumen ini:
- lock table list
- lock purpose setiap table
- lock FK direction
- lock indexes dan unique constraints utama
- lock table priority
- lock enum/status penting

Dokumen ini bukan migration code.
Dokumen ini ialah source of truth sebelum:
- migration batching
- naming convention final
- model relationship implementation
- API contract alignment
- seeding implementation

---

# [1] Database design principles

## 1.1 Database engine
ORDERra menggunakan **PostgreSQL** sebagai database utama.

Sebab:
- kuat untuk schema relational yang serius
- sesuai untuk payment / refund / audit trail
- sesuai untuk JSONB snapshots dan config payload
- lebih selesa untuk reporting dan filtering pada masa depan

## 1.2 Identifier strategy
Setiap table operational utama menggunakan:
- `id BIGINT` sebagai primary key dalaman
- `public_id UUID` untuk reference awam / API

Rule:
- semua foreign key gunakan `id`
- semua API response expose `public_id` bila sesuai

## 1.3 Money storage rule
Semua nilai wang simpan dalam **integer amount**.
Contoh:
- USD 12.99 => `1299`

Field naming:
- `*_amount`
- `*_subtotal_amount`
- `*_fee_amount`
- `*_discount_amount`

## 1.4 Enum strategy
Status penting dikunci sebagai **application enum string**, bukan lookup table berasingan buat masa ini.

Sebab:
- lebih practical
- lebih cepat untuk implementation
- kurang over-engineering
- masih mudah diubah kemudian jika perlu table reference

## 1.5 Snapshot strategy
Data yang boleh berubah selepas order dibuat mesti disimpan sebagai snapshot:
- item name
- variant name
- modifier name
- pricing breakdown
- payment payload
- summary snapshot
- simulation payload

## 1.6 History-first rule
Semua proses penting yang berubah state mesti ada history/log table:
- order status history
- order timeline events
- payment attempts
- payment transactions
- refund events
- rider tracking events
- payment webhook events
- audit logs
- simulation runs

## 1.7 Config-driven rule
Behavior berikut tidak boleh hardcode:
- tax
- fee
- tip
- payment capability
- delivery pricing
- refund logic
- promo logic
- demo scenario behavior

## 1.8 Soft delete rule
Soft delete digunakan pada data master yang mungkin “disable” tetapi perlu kekal untuk rujukan:
- users
- restaurants
- branches
- menu_categories
- menu_items

Soft delete **tidak** digunakan pada:
- order history
- payment logs
- refund events
- audit logs
- webhook events
- rider tracking events

## 1.9 Current project scope rule
ORDERra sekarang ialah:
- single restaurant
- branch-ready
- API-first
- demo-safe
- serious backend reference

Jadi schema mesti:
- tidak terlalu kecil
- tidak terlalu enterprise-heavy
- practical untuk implementation Laravel

---

# [2] Senarai table mengikut domain

## Identity & Access
- users
- roles
- permissions
- role_user
- permission_role

## Restaurant & Branch
- restaurants
- restaurant_settings
- branches
- branch_hours
- branch_settings
- delivery_zones

## Catalog
- menu_categories
- menu_items
- menu_item_variants
- modifier_groups
- modifier_options
- menu_item_modifier_group
- combo_meal_items

## Cart
- carts
- cart_items
- cart_item_modifiers

## Orders
- orders
- order_items
- order_item_modifiers
- order_status_histories
- order_timeline_events

## Fulfillment
- order_fulfillments
- delivery_details
- pickup_details
- dine_in_details

## Dine-In & QR
- dining_tables
- qr_sessions
- split_bills
- split_bill_shares
- split_bill_items

## Riders
- riders
- delivery_assignments
- rider_tracking_events

## Payments
- payment_providers
- payment_methods
- branch_payment_methods
- payment_intents
- payment_attempts
- payment_transactions
- payment_webhook_events

## Refunds
- refunds
- refund_items
- refund_events

## Pricing / Promo
- tax_rules
- fee_rules
- tip_rules
- promos
- promo_redemptions
- coupons

## Support / Notifications
- support_tickets
- support_ticket_messages
- notifications

## Audit / Demo Simulation
- audit_logs
- demo_scenarios
- simulation_runs

---

# [3] Table plan terperinci

## Identity & Access

### users
**Purpose**
- simpan customer, admin, manager, staff, rider

**Key columns**
- id
- public_id
- first_name
- last_name
- full_name
- email
- phone
- password
- email_verified_at
- phone_verified_at
- is_active
- locale
- timezone
- metadata
- created_at
- updated_at
- deleted_at

**Foreign keys**
- none

**Indexes**
- index(phone)
- index(is_active)

**Unique constraints**
- unique(public_id)
- unique(email)

**Soft delete**
- ya

---

### roles
**Purpose**
- role utama sistem

**Key columns**
- id
- code
- name
- description
- created_at
- updated_at

**Foreign keys**
- none

**Indexes**
- none

**Unique constraints**
- unique(code)

**Soft delete**
- tidak

---

### permissions
**Purpose**
- permission reference

**Key columns**
- id
- code
- name
- description
- created_at
- updated_at

**Foreign keys**
- none

**Indexes**
- none

**Unique constraints**
- unique(code)

**Soft delete**
- tidak

---

### role_user
**Purpose**
- assign role kepada user, optionally scoped by branch

**Key columns**
- id
- user_id
- role_id
- branch_id
- created_at
- updated_at

**Foreign keys**
- user_id -> users.id
- role_id -> roles.id
- branch_id -> branches.id nullable

**Indexes**
- index(user_id)
- index(role_id)
- index(branch_id)

**Unique constraints**
- unique(user_id, role_id, branch_id)

**Soft delete**
- tidak

---

### permission_role
**Purpose**
- assign permission kepada role

**Key columns**
- id
- role_id
- permission_id
- created_at
- updated_at

**Foreign keys**
- role_id -> roles.id
- permission_id -> permissions.id

**Indexes**
- index(role_id)
- index(permission_id)

**Unique constraints**
- unique(role_id, permission_id)

**Soft delete**
- tidak

---

## Restaurant & Branch

### restaurants
**Purpose**
- root restaurant entity

**Key columns**
- id
- public_id
- slug
- name
- legal_name
- brand_description
- support_email
- support_phone
- default_currency
- default_country
- is_demo
- is_active
- metadata
- created_at
- updated_at
- deleted_at

**Foreign keys**
- none

**Indexes**
- index(is_active)

**Unique constraints**
- unique(public_id)
- unique(slug)

**Soft delete**
- ya

---

### restaurant_settings
**Purpose**
- restaurant-wide feature toggles dan default config

**Key columns**
- id
- restaurant_id
- ordering_enabled
- dine_in_enabled
- pickup_enabled
- delivery_enabled
- split_bill_enabled
- qr_ordering_enabled
- demo_mode_enabled
- default_tax_profile_code
- default_fee_profile_code
- config
- created_at
- updated_at

**Foreign keys**
- restaurant_id -> restaurants.id

**Indexes**
- none

**Unique constraints**
- unique(restaurant_id)

**Soft delete**
- tidak

---

### branches
**Purpose**
- branch restoran

**Key columns**
- id
- public_id
- restaurant_id
- code
- name
- address_line_1
- address_line_2
- city
- state
- postal_code
- country
- latitude
- longitude
- phone
- email
- timezone
- currency
- prep_time_min_minutes
- prep_time_max_minutes
- pickup_buffer_minutes
- is_active
- metadata
- created_at
- updated_at
- deleted_at

**Foreign keys**
- restaurant_id -> restaurants.id

**Indexes**
- index(restaurant_id, is_active)
- index(city, state)

**Unique constraints**
- unique(public_id)
- unique(code)

**Soft delete**
- ya

---

### branch_hours
**Purpose**
- jam operasi branch mengikut hari

**Key columns**
- id
- branch_id
- day_of_week
- open_time
- close_time
- is_closed
- created_at
- updated_at

**Foreign keys**
- branch_id -> branches.id

**Indexes**
- index(branch_id)

**Unique constraints**
- unique(branch_id, day_of_week)

**Soft delete**
- tidak

---

### branch_settings
**Purpose**
- config branch untuk ordering, fulfillment, dan payment display

**Key columns**
- id
- branch_id
- accepts_cash
- accepts_card
- accepts_apple_pay
- accepts_google_pay
- accepts_paypal
- accepts_ach
- accepts_fpx
- accepts_duitnow_qr
- delivery_enabled
- pickup_enabled
- dine_in_enabled
- self_rider_enabled
- third_party_rider_placeholder_enabled
- min_delivery_subtotal_amount
- default_service_fee_amount
- small_order_fee_amount
- default_tip_suggestions
- config
- created_at
- updated_at

**Foreign keys**
- branch_id -> branches.id

**Indexes**
- none

**Unique constraints**
- unique(branch_id)

**Soft delete**
- tidak

---

### delivery_zones
**Purpose**
- zone delivery dan pricing reference

**Key columns**
- id
- public_id
- branch_id
- name
- code
- pricing_mode
- base_fee_amount
- per_km_fee_amount
- min_order_amount
- peak_surcharge_amount
- estimated_minutes_min
- estimated_minutes_max
- is_active
- polygon_json
- created_at
- updated_at

**Foreign keys**
- branch_id -> branches.id

**Indexes**
- index(branch_id, is_active)

**Unique constraints**
- unique(public_id)
- unique(branch_id, code)

**Soft delete**
- tidak

---

## Catalog

### menu_categories
**Purpose**
- kategori menu

**Key columns**
- id
- public_id
- branch_id
- parent_id
- slug
- name
- description
- sort_order
- is_active
- image_url
- created_at
- updated_at
- deleted_at

**Foreign keys**
- branch_id -> branches.id
- parent_id -> menu_categories.id nullable

**Indexes**
- index(branch_id, is_active)

**Unique constraints**
- unique(public_id)
- unique(branch_id, slug)

**Soft delete**
- ya

---

### menu_items
**Purpose**
- item menu utama

**Key columns**
- id
- public_id
- branch_id
- menu_category_id
- sku
- slug
- name
- short_description
- description
- base_price_amount
- compare_at_price_amount
- currency
- product_type
- customization_flow
- image_url
- calories_label
- is_featured
- is_active
- available_from
- available_until
- metadata
- created_at
- updated_at
- deleted_at

**Foreign keys**
- branch_id -> branches.id
- menu_category_id -> menu_categories.id

**Indexes**
- index(branch_id, is_active)
- index(menu_category_id, is_active)
- index(is_featured)

**Unique constraints**
- unique(public_id)
- unique(sku)
- unique(branch_id, slug)

**Soft delete**
- ya

---

### menu_item_variants
**Purpose**
- variant item seperti size atau version

**Key columns**
- id
- public_id
- menu_item_id
- code
- name
- price_delta_amount
- sort_order
- is_default
- is_active
- created_at
- updated_at

**Foreign keys**
- menu_item_id -> menu_items.id

**Indexes**
- index(menu_item_id)

**Unique constraints**
- unique(public_id)
- unique(menu_item_id, code)

**Soft delete**
- tidak

---

### modifier_groups
**Purpose**
- group modifier seperti sauces, add-ons, doneness

**Key columns**
- id
- public_id
- branch_id
- code
- name
- description
- selection_mode
- min_select
- max_select
- is_required
- sort_order
- is_active
- created_at
- updated_at

**Foreign keys**
- branch_id -> branches.id

**Indexes**
- index(branch_id, is_active)

**Unique constraints**
- unique(public_id)
- unique(branch_id, code)

**Soft delete**
- tidak

---

### modifier_options
**Purpose**
- pilihan dalam modifier group

**Key columns**
- id
- public_id
- modifier_group_id
- code
- name
- price_delta_amount
- sort_order
- is_default
- is_active
- created_at
- updated_at

**Foreign keys**
- modifier_group_id -> modifier_groups.id

**Indexes**
- index(modifier_group_id, is_active)

**Unique constraints**
- unique(public_id)
- unique(modifier_group_id, code)

**Soft delete**
- tidak

---

### menu_item_modifier_group
**Purpose**
- pivot antara menu item dan modifier group

**Key columns**
- id
- menu_item_id
- modifier_group_id
- sort_order
- created_at
- updated_at

**Foreign keys**
- menu_item_id -> menu_items.id
- modifier_group_id -> modifier_groups.id

**Indexes**
- index(menu_item_id)
- index(modifier_group_id)

**Unique constraints**
- unique(menu_item_id, modifier_group_id)

**Soft delete**
- tidak

---

### combo_meal_items
**Purpose**
- child mapping untuk combo meal

**Key columns**
- id
- menu_item_id
- child_menu_item_id
- role_code
- is_required
- created_at
- updated_at

**Foreign keys**
- menu_item_id -> menu_items.id
- child_menu_item_id -> menu_items.id

**Indexes**
- index(menu_item_id)

**Unique constraints**
- none mandatory now

**Soft delete**
- tidak

---

## Cart

### carts
**Purpose**
- active cart customer / guest / qr session

**Key columns**
- id
- public_id
- restaurant_id
- branch_id
- user_id
- qr_session_id
- session_token
- fulfillment_type
- currency
- status
- guest_name
- guest_email
- guest_phone
- subtotal_amount
- discount_amount
- tax_amount
- fee_amount
- tip_amount
- total_amount
- pricing_snapshot
- expires_at
- last_activity_at
- created_at
- updated_at

**Foreign keys**
- restaurant_id -> restaurants.id
- branch_id -> branches.id
- user_id -> users.id nullable
- qr_session_id -> qr_sessions.id nullable

**Indexes**
- index(branch_id, status)
- index(user_id)
- index(qr_session_id)
- index(session_token)

**Unique constraints**
- unique(public_id)

**Soft delete**
- tidak

---

### cart_items
**Purpose**
- item dalam cart

**Key columns**
- id
- public_id
- cart_id
- menu_item_id
- menu_item_variant_id
- quantity
- unit_price_amount
- line_subtotal_amount
- notes
- item_name_snapshot
- item_snapshot
- created_at
- updated_at

**Foreign keys**
- cart_id -> carts.id
- menu_item_id -> menu_items.id
- menu_item_variant_id -> menu_item_variants.id nullable

**Indexes**
- index(cart_id)

**Unique constraints**
- unique(public_id)

**Soft delete**
- tidak

---

### cart_item_modifiers
**Purpose**
- modifier yang dipilih untuk cart item

**Key columns**
- id
- cart_item_id
- modifier_group_id
- modifier_option_id
- quantity
- price_delta_amount
- modifier_name_snapshot
- option_name_snapshot
- created_at
- updated_at

**Foreign keys**
- cart_item_id -> cart_items.id
- modifier_group_id -> modifier_groups.id
- modifier_option_id -> modifier_options.id nullable

**Indexes**
- index(cart_item_id)

**Unique constraints**
- none mandatory now

**Soft delete**
- tidak

---

## Orders

### orders
**Purpose**
- root order record

**Key columns**
- id
- public_id
- order_number
- restaurant_id
- branch_id
- user_id
- cart_id
- qr_session_id
- primary_payer_user_id
- fulfillment_type
- current_status
- currency
- customer_name
- customer_email
- customer_phone
- guest_order
- subtotal_amount
- discount_amount
- tax_amount
- fee_amount
- delivery_fee_amount
- service_fee_amount
- small_order_fee_amount
- tip_amount
- refund_amount_total
- total_amount
- notes
- pricing_snapshot
- source_channel
- placed_at
- completed_at
- cancelled_at
- created_at
- updated_at
- deleted_at

**Foreign keys**
- restaurant_id -> restaurants.id
- branch_id -> branches.id
- user_id -> users.id nullable
- cart_id -> carts.id nullable
- qr_session_id -> qr_sessions.id nullable
- primary_payer_user_id -> users.id nullable

**Indexes**
- index(branch_id, current_status)
- index(user_id)
- index(qr_session_id)
- index(placed_at)

**Unique constraints**
- unique(public_id)
- unique(order_number)

**Soft delete**
- ya

---

### order_items
**Purpose**
- line items dalam order

**Key columns**
- id
- public_id
- order_id
- parent_order_item_id
- menu_item_id
- menu_item_variant_id
- quantity
- unit_price_amount
- total_price_amount
- item_type
- item_name_snapshot
- variant_name_snapshot
- item_snapshot
- notes
- created_at
- updated_at

**Foreign keys**
- order_id -> orders.id
- parent_order_item_id -> order_items.id nullable
- menu_item_id -> menu_items.id nullable
- menu_item_variant_id -> menu_item_variants.id nullable

**Indexes**
- index(order_id)

**Unique constraints**
- unique(public_id)

**Soft delete**
- tidak

---

### order_item_modifiers
**Purpose**
- modifier snapshot untuk order item

**Key columns**
- id
- order_item_id
- modifier_group_id
- modifier_option_id
- quantity
- price_delta_amount
- modifier_name_snapshot
- option_name_snapshot
- created_at
- updated_at

**Foreign keys**
- order_item_id -> order_items.id
- modifier_group_id -> modifier_groups.id nullable
- modifier_option_id -> modifier_options.id nullable

**Indexes**
- index(order_item_id)

**Unique constraints**
- none mandatory now

**Soft delete**
- tidak

---

### order_status_histories
**Purpose**
- simpan perubahan status order

**Key columns**
- id
- order_id
- from_status
- to_status
- changed_by_user_id
- source
- reason
- metadata
- created_at

**Foreign keys**
- order_id -> orders.id
- changed_by_user_id -> users.id nullable

**Indexes**
- index(order_id, created_at)
- index(to_status)

**Unique constraints**
- none

**Soft delete**
- tidak

---

### order_timeline_events
**Purpose**
- event timeline untuk customer tracking dan admin trace

**Key columns**
- id
- order_id
- event_type
- title
- description
- visibility
- payload
- occurred_at
- created_at
- updated_at

**Foreign keys**
- order_id -> orders.id

**Indexes**
- index(order_id, occurred_at)
- index(event_type)

**Unique constraints**
- none

**Soft delete**
- tidak

---

## Fulfillment

### order_fulfillments
**Purpose**
- summary fulfillment level untuk order

**Key columns**
- id
- order_id
- fulfillment_type
- scheduled_for
- estimated_ready_at
- actual_ready_at
- completed_at
- created_at
- updated_at

**Foreign keys**
- order_id -> orders.id

**Indexes**
- none mandatory

**Unique constraints**
- unique(order_id)

**Soft delete**
- tidak

---

### delivery_details
**Purpose**
- detail penghantaran untuk order delivery

**Key columns**
- id
- order_id
- delivery_zone_id
- contact_name
- contact_phone
- address_line_1
- address_line_2
- city
- state
- postal_code
- country
- latitude
- longitude
- instructions
- distance_km
- estimated_minutes
- created_at
- updated_at

**Foreign keys**
- order_id -> orders.id
- delivery_zone_id -> delivery_zones.id nullable

**Indexes**
- index(delivery_zone_id)

**Unique constraints**
- unique(order_id)

**Soft delete**
- tidak

---

### pickup_details
**Purpose**
- detail pickup verification

**Key columns**
- id
- order_id
- pickup_code
- pickup_name
- pickup_phone
- verification_mode
- ready_window_starts_at
- ready_window_ends_at
- picked_up_at
- created_at
- updated_at

**Foreign keys**
- order_id -> orders.id

**Indexes**
- index(pickup_code)

**Unique constraints**
- unique(order_id)

**Soft delete**
- tidak

---

### dine_in_details
**Purpose**
- detail dine-in order

**Key columns**
- id
- order_id
- dining_table_id
- qr_session_id
- guest_count
- waiter_call_requested_at
- bill_requested_at
- served_at
- created_at
- updated_at

**Foreign keys**
- order_id -> orders.id
- dining_table_id -> dining_tables.id
- qr_session_id -> qr_sessions.id nullable

**Indexes**
- index(dining_table_id)
- index(qr_session_id)

**Unique constraints**
- unique(order_id)

**Soft delete**
- tidak

---

## Dine-In & QR

### dining_tables
**Purpose**
- meja branch untuk dine-in dan QR

**Key columns**
- id
- public_id
- branch_id
- code
- label
- seats
- area_name
- is_active
- created_at
- updated_at

**Foreign keys**
- branch_id -> branches.id

**Indexes**
- index(branch_id, is_active)

**Unique constraints**
- unique(public_id)
- unique(branch_id, code)

**Soft delete**
- tidak

---

### qr_sessions
**Purpose**
- sesi order melalui QR table

**Key columns**
- id
- public_id
- branch_id
- dining_table_id
- session_code
- status
- opened_by_user_id
- primary_order_id
- guest_name
- guest_count
- opened_at
- closed_at
- expires_at
- metadata
- created_at
- updated_at

**Foreign keys**
- branch_id -> branches.id
- dining_table_id -> dining_tables.id
- opened_by_user_id -> users.id nullable
- primary_order_id -> orders.id nullable

**Indexes**
- index(branch_id, status)
- index(dining_table_id, status)

**Unique constraints**
- unique(public_id)
- unique(session_code)

**Soft delete**
- tidak

---

### split_bills
**Purpose**
- root split bill record

**Key columns**
- id
- public_id
- order_id
- split_type
- status
- created_by_user_id
- summary_snapshot
- created_at
- updated_at

**Foreign keys**
- order_id -> orders.id
- created_by_user_id -> users.id nullable

**Indexes**
- index(order_id)

**Unique constraints**
- unique(public_id)

**Soft delete**
- tidak

---

### split_bill_shares
**Purpose**
- share setiap payer dalam split bill

**Key columns**
- id
- split_bill_id
- user_id
- payer_name
- payer_email
- payer_phone
- share_amount
- paid_amount
- status
- share_reference
- created_at
- updated_at

**Foreign keys**
- split_bill_id -> split_bills.id
- user_id -> users.id nullable

**Indexes**
- index(split_bill_id, status)

**Unique constraints**
- unique(share_reference)

**Soft delete**
- tidak

---

### split_bill_items
**Purpose**
- item allocation untuk split by item

**Key columns**
- id
- split_bill_share_id
- order_item_id
- allocated_amount
- created_at
- updated_at

**Foreign keys**
- split_bill_share_id -> split_bill_shares.id
- order_item_id -> order_items.id

**Indexes**
- index(order_item_id)

**Unique constraints**
- unique(split_bill_share_id, order_item_id)

**Soft delete**
- tidak

---

## Riders

### riders
**Purpose**
- rider master data

**Key columns**
- id
- public_id
- user_id
- branch_id
- code
- display_name
- phone
- vehicle_type
- provider_type
- status
- current_latitude
- current_longitude
- metadata
- created_at
- updated_at

**Foreign keys**
- user_id -> users.id nullable
- branch_id -> branches.id

**Indexes**
- index(branch_id)
- index(status)

**Unique constraints**
- unique(public_id)
- unique(code)

**Soft delete**
- tidak

---

### delivery_assignments
**Purpose**
- rider assignment untuk delivery order

**Key columns**
- id
- order_id
- rider_id
- provider_type
- assignment_status
- assigned_at
- accepted_at
- picked_up_at
- delivered_at
- eta_minutes
- fee_amount
- simulation_payload
- created_at
- updated_at

**Foreign keys**
- order_id -> orders.id
- rider_id -> riders.id nullable

**Indexes**
- index(rider_id)
- index(assignment_status)

**Unique constraints**
- unique(order_id)

**Soft delete**
- tidak

---

### rider_tracking_events
**Purpose**
- tracking event simulation / history

**Key columns**
- id
- delivery_assignment_id
- latitude
- longitude
- status_label
- eta_minutes
- payload
- occurred_at
- created_at
- updated_at

**Foreign keys**
- delivery_assignment_id -> delivery_assignments.id

**Indexes**
- index(delivery_assignment_id, occurred_at)

**Unique constraints**
- none

**Soft delete**
- tidak

---

## Payments

### payment_providers
**Purpose**
- provider abstraction layer

**Key columns**
- id
- code
- name
- provider_type
- country_code
- is_active
- supports_webhooks
- supports_refunds
- config
- created_at
- updated_at

**Foreign keys**
- none

**Indexes**
- index(is_active)

**Unique constraints**
- unique(code)

**Soft delete**
- tidak

---

### payment_methods
**Purpose**
- method seperti card, apple_pay, cash, paypal

**Key columns**
- id
- code
- name
- method_type
- country_code
- is_active
- display_order
- config
- created_at
- updated_at

**Foreign keys**
- none

**Indexes**
- index(country_code, is_active)

**Unique constraints**
- unique(code)

**Soft delete**
- tidak

---

### branch_payment_methods
**Purpose**
- branch-level capability matrix antara payment method dan provider

**Key columns**
- id
- branch_id
- payment_method_id
- payment_provider_id
- is_enabled
- demo_only
- config
- created_at
- updated_at

**Foreign keys**
- branch_id -> branches.id
- payment_method_id -> payment_methods.id
- payment_provider_id -> payment_providers.id

**Indexes**
- index(branch_id)
- index(payment_provider_id)

**Unique constraints**
- unique(branch_id, payment_method_id)

**Soft delete**
- tidak

---

### payment_intents
**Purpose**
- root payment object sebelum success/fail/cancel/refund

**Key columns**
- id
- public_id
- order_id
- split_bill_share_id
- payment_provider_id
- payment_method_id
- intent_reference
- currency
- amount
- capturable_amount
- refunded_amount
- status
- demo_scenario
- provider_payload
- expires_at
- created_at
- updated_at

**Foreign keys**
- order_id -> orders.id
- split_bill_share_id -> split_bill_shares.id nullable
- payment_provider_id -> payment_providers.id
- payment_method_id -> payment_methods.id

**Indexes**
- index(order_id, status)
- index(split_bill_share_id)

**Unique constraints**
- unique(public_id)
- unique(intent_reference)

**Soft delete**
- tidak

---

### payment_attempts
**Purpose**
- setiap cubaan proses payment intent

**Key columns**
- id
- payment_intent_id
- attempt_number
- status
- requested_amount
- response_code
- response_message
- demo_scenario
- requested_by_user_id
- request_payload
- response_payload
- attempted_at
- created_at
- updated_at

**Foreign keys**
- payment_intent_id -> payment_intents.id
- requested_by_user_id -> users.id nullable

**Indexes**
- index(payment_intent_id)
- index(attempted_at)

**Unique constraints**
- unique(payment_intent_id, attempt_number)

**Soft delete**
- tidak

---

### payment_transactions
**Purpose**
- ledger-like transaksi provider abstraction

**Key columns**
- id
- payment_intent_id
- transaction_type
- transaction_reference
- amount
- currency
- status
- provider_status
- provider_payload
- processed_at
- created_at
- updated_at

**Foreign keys**
- payment_intent_id -> payment_intents.id

**Indexes**
- index(payment_intent_id, transaction_type)

**Unique constraints**
- unique(transaction_reference)

**Soft delete**
- tidak

---

### payment_webhook_events
**Purpose**
- rekod inbound webhook simulated / provider callback log

**Key columns**
- id
- payment_provider_id
- event_id
- event_type
- source_mode
- signature_valid
- payload
- processed_at
- status
- created_at
- updated_at

**Foreign keys**
- payment_provider_id -> payment_providers.id

**Indexes**
- index(event_type)
- index(status)

**Unique constraints**
- unique(payment_provider_id, event_id)

**Soft delete**
- tidak

---

## Refunds

### refunds
**Purpose**
- root refund request / review record

**Key columns**
- id
- public_id
- order_id
- payment_intent_id
- refund_reference
- refund_type
- reason_code
- status
- requested_amount
- approved_amount
- currency
- requested_by_user_id
- reviewed_by_user_id
- resolution_notes
- store_credit_amount
- created_at
- updated_at
- reviewed_at
- completed_at

**Foreign keys**
- order_id -> orders.id
- payment_intent_id -> payment_intents.id nullable
- requested_by_user_id -> users.id nullable
- reviewed_by_user_id -> users.id nullable

**Indexes**
- index(order_id, status)

**Unique constraints**
- unique(public_id)
- unique(refund_reference)

**Soft delete**
- tidak

---

### refund_items
**Purpose**
- pecahan item-level refund jika partial

**Key columns**
- id
- refund_id
- order_item_id
- refund_amount
- reason_note
- created_at
- updated_at

**Foreign keys**
- refund_id -> refunds.id
- order_item_id -> order_items.id nullable

**Indexes**
- index(refund_id)

**Unique constraints**
- none

**Soft delete**
- tidak

---

### refund_events
**Purpose**
- event log untuk refund process

**Key columns**
- id
- refund_id
- event_type
- actor_user_id
- payload
- created_at

**Foreign keys**
- refund_id -> refunds.id
- actor_user_id -> users.id nullable

**Indexes**
- index(refund_id, created_at)

**Unique constraints**
- none

**Soft delete**
- tidak

---

## Pricing / Promo

### tax_rules
**Purpose**
- tax config ikut branch dan jurisdiction

**Key columns**
- id
- branch_id
- code
- jurisdiction_country
- jurisdiction_state
- jurisdiction_city
- tax_name
- rate_basis_points
- applies_to_delivery
- applies_to_pickup
- applies_to_dine_in
- is_active
- priority
- created_at
- updated_at

**Foreign keys**
- branch_id -> branches.id

**Indexes**
- index(branch_id, is_active)

**Unique constraints**
- unique(branch_id, code)

**Soft delete**
- tidak

---

### fee_rules
**Purpose**
- service fee, small order fee, peak surcharge, packaging fee

**Key columns**
- id
- branch_id
- code
- fee_type
- name
- calculation_mode
- amount
- rate_basis_points
- minimum_amount
- maximum_amount
- applies_to_delivery
- applies_to_pickup
- applies_to_dine_in
- is_active
- config
- created_at
- updated_at

**Foreign keys**
- branch_id -> branches.id

**Indexes**
- index(branch_id, is_active)

**Unique constraints**
- unique(branch_id, code)

**Soft delete**
- tidak

---

### tip_rules
**Purpose**
- preset tip suggestion

**Key columns**
- id
- branch_id
- code
- tip_type
- option_label
- value
- sort_order
- is_default
- is_active
- created_at
- updated_at

**Foreign keys**
- branch_id -> branches.id

**Indexes**
- index(branch_id, is_active)

**Unique constraints**
- unique(branch_id, code)

**Soft delete**
- tidak

---

### promos
**Purpose**
- promo definition

**Key columns**
- id
- public_id
- branch_id
- code
- name
- description
- discount_type
- discount_value
- max_discount_amount
- minimum_subtotal_amount
- starts_at
- ends_at
- usage_limit
- usage_per_user_limit
- applies_to_fulfillment_types
- applies_to_category_ids
- applies_to_item_ids
- stackable
- is_active
- created_at
- updated_at

**Foreign keys**
- branch_id -> branches.id

**Indexes**
- index(branch_id, is_active)
- index(starts_at, ends_at)

**Unique constraints**
- unique(public_id)
- unique(code)

**Soft delete**
- tidak

---

### promo_redemptions
**Purpose**
- usage log promo pada order

**Key columns**
- id
- promo_id
- user_id
- order_id
- code_snapshot
- discount_amount
- created_at
- updated_at

**Foreign keys**
- promo_id -> promos.id
- user_id -> users.id nullable
- order_id -> orders.id

**Indexes**
- index(promo_id)
- index(user_id)

**Unique constraints**
- none

**Soft delete**
- tidak

---

### coupons
**Purpose**
- coupon placeholder untuk promo granular

**Key columns**
- id
- public_id
- promo_id
- coupon_code
- assigned_user_id
- status
- expires_at
- redeemed_at
- created_at
- updated_at

**Foreign keys**
- promo_id -> promos.id
- assigned_user_id -> users.id nullable

**Indexes**
- index(promo_id, status)

**Unique constraints**
- unique(public_id)
- unique(coupon_code)

**Soft delete**
- tidak

---

## Support / Notifications

### support_tickets
**Purpose**
- support issue intake

**Key columns**
- id
- public_id
- order_id
- user_id
- branch_id
- ticket_number
- category
- subject
- description
- priority
- status
- resolution_summary
- assigned_to_user_id
- created_at
- updated_at
- closed_at

**Foreign keys**
- order_id -> orders.id nullable
- user_id -> users.id nullable
- branch_id -> branches.id
- assigned_to_user_id -> users.id nullable

**Indexes**
- index(order_id)
- index(status)

**Unique constraints**
- unique(public_id)
- unique(ticket_number)

**Soft delete**
- tidak

---

### support_ticket_messages
**Purpose**
- perbualan dalam support ticket

**Key columns**
- id
- support_ticket_id
- sender_user_id
- sender_type
- message
- attachments
- created_at
- updated_at

**Foreign keys**
- support_ticket_id -> support_tickets.id
- sender_user_id -> users.id nullable

**Indexes**
- index(support_ticket_id, created_at)

**Unique constraints**
- none

**Soft delete**
- tidak

---

### notifications
**Purpose**
- database notification log

**Key columns**
- id
- public_id
- user_id
- channel
- notification_type
- title
- body
- data
- status
- sent_at
- read_at
- created_at
- updated_at

**Foreign keys**
- user_id -> users.id nullable

**Indexes**
- index(user_id, status)
- index(notification_type)

**Unique constraints**
- unique(public_id)

**Soft delete**
- tidak

---

## Audit / Demo Simulation

### audit_logs
**Purpose**
- audit trail untuk admin/system actions

**Key columns**
- id
- actor_user_id
- auditable_type
- auditable_id
- action
- description
- before_state
- after_state
- metadata
- ip_address
- user_agent
- created_at

**Foreign keys**
- actor_user_id -> users.id nullable

**Indexes**
- index(auditable_type, auditable_id)
- index(actor_user_id)
- index(action)

**Unique constraints**
- none

**Soft delete**
- tidak

---

### demo_scenarios
**Purpose**
- define reusable simulation scenarios

**Key columns**
- id
- code
- category
- name
- description
- is_active
- config
- created_at
- updated_at

**Foreign keys**
- none

**Indexes**
- index(category, is_active)

**Unique constraints**
- unique(code)

**Soft delete**
- tidak

---

### simulation_runs
**Purpose**
- simpan execution log untuk scenario simulation

**Key columns**
- id
- demo_scenario_id
- related_type
- related_id
- triggered_by_user_id
- status
- input_payload
- output_payload
- created_at
- updated_at

**Foreign keys**
- demo_scenario_id -> demo_scenarios.id
- triggered_by_user_id -> users.id nullable

**Indexes**
- index(demo_scenario_id)
- index(related_type, related_id)

**Unique constraints**
- none

**Soft delete**
- tidak

---

# [4] Table mana wajib bina sekarang

## Batch 1 — wajib untuk backend core
- users
- roles
- permissions
- role_user
- permission_role
- restaurants
- restaurant_settings
- branches
- branch_hours
- branch_settings
- delivery_zones
- menu_categories
- menu_items
- menu_item_variants
- modifier_groups
- modifier_options
- menu_item_modifier_group
- carts
- cart_items
- cart_item_modifiers
- orders
- order_items
- order_item_modifiers
- order_status_histories
- order_timeline_events
- order_fulfillments
- delivery_details
- pickup_details
- dine_in_details
- dining_tables
- qr_sessions
- split_bills
- split_bill_shares
- split_bill_items
- riders
- delivery_assignments
- rider_tracking_events
- payment_providers
- payment_methods
- branch_payment_methods
- payment_intents
- payment_attempts
- payment_transactions
- payment_webhook_events
- refunds
- refund_items
- refund_events
- tax_rules
- fee_rules
- tip_rules
- promos
- promo_redemptions
- support_tickets
- support_ticket_messages
- notifications
- audit_logs
- demo_scenarios
- simulation_runs

## Batch 2 — bagus ada, tetapi boleh datang kemudian jika mahu kurangkan beban awal
- combo_meal_items
- coupons

---

# [5] Table mana boleh ditangguh

Table berikut boleh ditangguh jika Abg mahu migration batch pertama lebih fokus:

## Boleh ditangguh
- combo_meal_items
- coupons

## Boleh dibina minimum dahulu
- notifications
- support_ticket_messages
- promo_redemptions

Maksud “minimum dahulu”:
- table tetap wujud
- implementation logic boleh ringan dulu

---

# [6] Enum / status penting yang perlu dikunci awal

## user role codes
- super_admin
- admin
- manager
- staff
- customer
- rider

## fulfillment_type
- delivery
- pickup
- dine_in

## cart_status
- active
- converted
- abandoned
- expired

## order_status
Core:
- cart_draft
- pending_payment
- payment_authorized
- placed
- confirmed
- preparing
- ready

Delivery:
- awaiting_rider
- rider_assigned
- picked_up
- near_customer
- delivered

Pickup:
- ready_for_pickup
- picked_up_by_customer

Dine-in:
- served
- bill_requested
- paid_at_table

Closure:
- completed
- cancelled
- refund_pending
- refunded
- partially_refunded

## order_status_source
- system
- admin
- customer
- payment
- simulation
- rider

## product_type
- standard
- combo
- drink
- dessert

## customization_flow
- none
- simple
- full

## modifier_selection_mode
- single
- multiple
- text

## pricing_mode
- zone_flat
- distance_based
- hybrid

## split_bill_type
- equal
- by_item
- custom_reference

## split_bill_status
- draft
- pending
- partially_paid
- paid
- cancelled

## split_bill_share_status
- pending
- paid
- failed
- cancelled

## rider_provider_type
- self
- third_party_placeholder

## rider_status
- offline
- available
- assigned
- busy

## delivery_assignment_status
- pending
- assigned
- accepted
- picked_up
- delivered
- cancelled

## payment_provider_type
- demo
- sandbox
- live_reference_only

## payment_method_code
- card
- apple_pay
- google_pay
- ach
- cash
- paypal
- fpx
- duitnow_qr

## payment_intent_status
- requires_action
- pending
- authorized
- succeeded
- failed
- cancelled
- refunded
- partially_refunded

## payment_attempt_status
- pending
- processing
- succeeded
- failed
- cancelled

## payment_transaction_type
- authorization
- capture
- sale
- refund
- void
- adjustment

## payment_transaction_status
- pending
- succeeded
- failed
- cancelled

## webhook_source_mode
- simulated
- sandbox

## webhook_status
- received
- processed
- failed
- ignored

## refund_type
- full
- partial
- compensation
- store_credit

## refund_status
- requested
- under_review
- approved
- rejected
- completed
- partially_completed

## support_ticket_status
- open
- in_review
- waiting_customer
- resolved
- closed

## support_ticket_priority
- low
- normal
- high
- urgent

## notification_status
- queued
- sent
- failed
- read

## demo_scenario_category
- payment
- refund
- rider
- webhook
- order

## simulation_run_status
- pending
- completed
- failed

---

# [7] Lock notes sebelum migrations sebenar

1. Migration batching mesti ikut dependency order:
   - identity
   - restaurant
   - catalog
   - cart
   - orders
   - fulfillment
   - dine-in / qr
   - riders
   - payments
   - refunds
   - pricing / promos
   - support / notifications
   - audit / simulation

2. Semua FK yang menyentuh table belum wujud mesti ikut urutan batch.
3. Table logs/history tidak guna soft delete.
4. Semua public API patut guna `public_id`, bukan raw numeric id.
5. Semua monetary columns guna integer amount.
6. Semua status string mesti dikunci konsisten dalam enum PHP sebelum implementation service.
7. JSONB diguna untuk:
   - metadata
   - config
   - pricing_snapshot
   - item_snapshot
   - summary_snapshot
   - request_payload
   - response_payload
   - provider_payload
   - payload
   - polygon_json

---

# Step 4 completion note

Step 4 dianggap siap apabila selepas ini kita sambung kepada:
1. migration priority order final
2. migration naming convention final
3. relationship mapping checklist
4. implementation batch migration pertama
