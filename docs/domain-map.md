# ORDERra Step 3 — Domain Map + File Responsibility

Dokumen ini mengunci tanggungjawab domain backend dan feature frontend untuk ORDERra.

Scope dokumen ini:
- lock domain map
- lock file responsibility
- lock priority domain
- lock anchor files awal
- lock apa yang belum patut dibina

Dokumen ini **bukan** implementation code.
Dokumen ini **bukan** migration plan.
Dokumen ini **bukan** API contract final.

Tujuan dokumen ini ialah supaya implementation selepas ini:
- kemas
- backend-first
- tidak bercampur tanggungjawab
- tidak lompat terlalu jauh
- tidak over-engineer

---

# [1] Domain priority order

## Backend priority order

Urutan ini ialah urutan implementasi semasa, bukan semestinya urutan folder.

1. Auth
2. Restaurant
3. Catalog
4. Pricing
5. Cart
6. Orders
7. Payments
8. Fulfillment
9. QrSessions
10. DineIn
11. Riders
12. Refunds
13. Audit
14. Webhooks
15. DemoSimulation
16. Promos
17. Support
18. Notifications
19. Admin

## Frontend feature priority order

Frontend hanya ikut backend yang sudah cukup stabil.

1. catalog
2. cart
3. checkout
4. fulfillment
5. payments
6. orders
7. tracking
8. qr-session
9. dine-in
10. promos
11. support
12. admin-reference

---

# [2] Backend domain responsibility table

## Auth

**Tujuan**
- login
- logout
- current authenticated user
- token/session flow dengan Sanctum
- role bootstrap untuk admin, customer, rider, staff

**Perlu**
- model: ya
- service: ya
- action: ya
- request validation: ya
- policy: belum wajib pada batch awal
- resource transformer: ya
- config: minimum sahaja
- seed data: ya
- routes: ya
- tests: ya

**Priority**
- backend-first priority now

**Anchor files awal**
- `backend/app/Models/User.php`
- `backend/app/Models/Role.php`
- `backend/app/Models/Permission.php`
- `backend/app/Services/Auth/AuthService.php`
- `backend/app/Http/Controllers/Api/Auth/AuthController.php`
- `backend/app/Http/Requests/Auth/LoginRequest.php`
- `backend/app/Http/Requests/Auth/RegisterRequest.php`
- `backend/app/Http/Resources/Auth/AuthUserResource.php`
- `backend/database/seeders/RoleSeeder.php`
- `backend/database/seeders/AdminUserSeeder.php`
- `backend/routes/auth.php`

**Jangan bina lagi sekarang**
- social login
- 2FA
- passwordless auth
- device session management

---

## Restaurant

**Tujuan**
- restaurant root data
- branch
- branch hours
- branch settings
- delivery zone reference
- active branch context

**Perlu**
- model: ya
- service: ya
- action: ya
- request validation: ya
- policy: ya
- resource transformer: ya
- config: minimum sahaja
- seed data: ya
- routes: ya
- tests: ya

**Priority**
- backend-first priority now

**Anchor files awal**
- `backend/app/Models/Restaurant.php`
- `backend/app/Models/RestaurantSetting.php`
- `backend/app/Models/Branch.php`
- `backend/app/Models/BranchHour.php`
- `backend/app/Models/BranchSetting.php`
- `backend/app/Services/Restaurant/RestaurantService.php`
- `backend/app/Http/Controllers/Api/Restaurant/BranchController.php`
- `backend/app/Http/Requests/Restaurant/StoreBranchRequest.php`
- `backend/app/Http/Resources/Restaurant/BranchResource.php`
- `backend/database/seeders/RestaurantSeeder.php`
- `backend/routes/customer.php`
- `backend/routes/admin.php`

**Jangan bina lagi sekarang**
- multi-restaurant tenancy
- media manager
- staff rostering
- branch CMS

---

## Catalog

**Tujuan**
- kategori menu
- menu item
- variants
- modifier groups
- modifier options
- browse menu customer flow

**Perlu**
- model: ya
- service: ya
- action: ya
- request validation: ya
- policy: ya untuk admin nanti
- resource transformer: ya
- config: tidak perlu berasingan sekarang
- seed data: ya
- routes: ya
- tests: ya

**Priority**
- backend-first priority now

**Anchor files awal**
- `backend/app/Models/MenuCategory.php`
- `backend/app/Models/MenuItem.php`
- `backend/app/Models/MenuItemVariant.php`
- `backend/app/Models/ModifierGroup.php`
- `backend/app/Models/ModifierOption.php`
- `backend/app/Services/Catalog/CatalogService.php`
- `backend/app/Http/Controllers/Api/Catalog/CatalogController.php`
- `backend/app/Http/Requests/Catalog/StoreMenuItemRequest.php`
- `backend/app/Http/Resources/Catalog/MenuCategoryResource.php`
- `backend/app/Http/Resources/Catalog/MenuItemResource.php`
- `backend/database/seeders/MenuSeeder.php`
- `backend/routes/customer.php`
- `backend/routes/admin.php`

**Jangan bina lagi sekarang**
- CMS editor berat
- search engine khas
- inventory sync
- recommendation engine

---

## Cart

**Tujuan**
- active cart
- add item
- update quantity
- remove item
- modifier snapshot
- cart ownership by token / user
- cart totals snapshot

**Perlu**
- model: ya
- service: ya
- action: ya
- request validation: ya
- policy: belum perlu berasingan
- resource transformer: ya
- config: tidak perlu sekarang
- seed data: tidak perlu
- routes: ya
- tests: ya

**Priority**
- backend-first priority now

**Anchor files awal**
- `backend/app/Models/Cart.php`
- `backend/app/Models/CartItem.php`
- `backend/app/Models/CartItemModifier.php`
- `backend/app/Services/Cart/CartService.php`
- `backend/app/Actions/Cart/AddItemToCart.php`
- `backend/app/Actions/Cart/UpdateCartItem.php`
- `backend/app/Actions/Cart/RemoveCartItem.php`
- `backend/app/Http/Controllers/Api/Cart/CartController.php`
- `backend/app/Http/Requests/Cart/AddCartItemRequest.php`
- `backend/app/Http/Requests/Cart/UpdateCartItemRequest.php`
- `backend/app/Http/Resources/Cart/CartResource.php`
- `backend/routes/customer.php`

**Jangan bina lagi sekarang**
- abandoned cart recovery
- advanced cart merge cross-device
- offline cart conflict engine

---

## Pricing

**Tujuan**
- subtotal calculation
- tax
- service fee
- delivery fee
- small order fee
- tip
- promo impact
- pricing snapshot
- refund amount effect

**Perlu**
- model: ya
- service: ya
- action: ya
- request validation: tidak perlu domain route khas sekarang
- policy: nanti untuk admin config
- resource transformer: ya
- config: ya
- seed data: ya
- routes: belum perlu route domain khas
- tests: ya

**Priority**
- backend-first priority now

**Anchor files awal**
- `backend/app/Models/TaxRule.php`
- `backend/app/Models/FeeRule.php`
- `backend/app/Models/TipRule.php`
- `backend/app/Services/Pricing/PricingService.php`
- `backend/app/Actions/Pricing/CalculateCartPricing.php`
- `backend/app/Http/Resources/Pricing/PricingBreakdownResource.php`
- `backend/config/pricing.php`
- `backend/database/seeders/PricingSeeder.php`

**Jangan bina lagi sekarang**
- external tax provider integration
- over-complex jurisdiction engine
- reporting engine

---

## Orders

**Tujuan**
- place order
- order number
- order snapshot
- order lifecycle
- order status transitions
- order history
- order timeline events

**Perlu**
- model: ya
- service: ya
- action: ya
- request validation: ya
- policy: ya
- resource transformer: ya
- config: tidak perlu berasingan sekarang
- seed data: ya
- routes: ya
- tests: ya

**Priority**
- backend-first priority now

**Anchor files awal**
- `backend/app/Models/Order.php`
- `backend/app/Models/OrderItem.php`
- `backend/app/Models/OrderStatusHistory.php`
- `backend/app/Models/OrderTimelineEvent.php`
- `backend/app/Services/Orders/OrderService.php`
- `backend/app/Actions/Orders/PlaceOrder.php`
- `backend/app/Actions/Orders/TransitionOrderStatus.php`
- `backend/app/Http/Controllers/Api/Orders/OrderController.php`
- `backend/app/Http/Requests/Orders/PlaceOrderRequest.php`
- `backend/app/Http/Resources/Orders/OrderResource.php`
- `backend/routes/customer.php`
- `backend/routes/admin.php`

**Jangan bina lagi sekarang**
- event sourcing penuh
- kitchen display integration
- bulk admin tooling berat

---

## Fulfillment

**Tujuan**
- delivery / pickup / dine_in attachment
- delivery address payload
- pickup verification basics
- estimated ready time
- fulfillment-specific metadata

**Perlu**
- model: ya
- service: ya
- action: ya
- request validation: ya
- policy: guna order/admin policy dahulu
- resource transformer: ya
- config: ya
- seed data: ya
- routes: ya
- tests: ya

**Priority**
- backend-first priority now

**Anchor files awal**
- `backend/app/Models/OrderFulfillment.php`
- `backend/app/Models/DeliveryDetail.php`
- `backend/app/Models/PickupDetail.php`
- `backend/app/Models/DineInDetail.php`
- `backend/app/Services/Fulfillment/FulfillmentService.php`
- `backend/app/Actions/Fulfillment/AttachFulfillmentToOrder.php`
- `backend/app/Http/Controllers/Api/Fulfillment/FulfillmentController.php`
- `backend/app/Http/Requests/Fulfillment/SetFulfillmentRequest.php`
- `backend/app/Http/Resources/Fulfillment/FulfillmentResource.php`
- `backend/config/fulfillment.php`
- `backend/routes/customer.php`
- `backend/routes/admin.php`

**Jangan bina lagi sekarang**
- external courier integration
- map routing
- route optimization

---

## DineIn

**Tujuan**
- dine-in order flow
- call waiter
- request bill
- split bill
- merge under table session

**Perlu**
- model: ya
- service: ya
- action: ya
- request validation: ya
- policy: ya
- resource transformer: ya
- config: minimum sahaja
- seed data: ya
- routes: ya
- tests: ya

**Priority**
- backend-first priority now

**Anchor files awal**
- `backend/app/Models/DineInDetail.php`
- `backend/app/Models/SplitBill.php`
- `backend/app/Models/SplitBillShare.php`
- `backend/app/Models/SplitBillItem.php`
- `backend/app/Services/DineIn/DineInService.php`
- `backend/app/Actions/DineIn/RequestBill.php`
- `backend/app/Actions/DineIn/CreateSplitBill.php`
- `backend/app/Actions/DineIn/CallWaiter.php`
- `backend/app/Http/Controllers/Api/DineIn/DineInController.php`
- `backend/app/Http/Requests/DineIn/RequestBillRequest.php`
- `backend/app/Http/Resources/DineIn/SplitBillResource.php`
- `backend/config/dinein.php`
- `backend/routes/customer.php`

**Jangan bina lagi sekarang**
- waiter tablet workflow
- seat map
- kitchen printer flow

---

## QrSessions

**Tujuan**
- QR table session
- session resolution
- open session
- attach cart and order to session
- guest dine-in entry point

**Perlu**
- model: ya
- service: ya
- action: ya
- request validation: ya
- policy: belum wajib berasingan
- resource transformer: ya
- config: ya
- seed data: ya
- routes: ya
- tests: ya

**Priority**
- backend-first priority now

**Anchor files awal**
- `backend/app/Models/DiningTable.php`
- `backend/app/Models/QrSession.php`
- `backend/app/Services/QR/QrSessionService.php`
- `backend/app/Actions/QrSessions/OpenQrSession.php`
- `backend/app/Actions/QrSessions/ResolveQrSession.php`
- `backend/app/Http/Controllers/Api/QrSessions/QrSessionController.php`
- `backend/app/Http/Requests/QrSessions/ResolveQrSessionRequest.php`
- `backend/app/Http/Resources/QrSessions/QrSessionResource.php`
- `backend/config/qr.php`
- `backend/database/seeders/DiningTableSeeder.php`
- `backend/routes/customer.php`

**Jangan bina lagi sekarang**
- QR image generator
- QR print asset manager
- camera scanner frontend

---

## Riders

**Tujuan**
- rider master data
- self rider
- third-party placeholder
- delivery assignment
- tracking events
- ETA simulation support

**Perlu**
- model: ya
- service: ya
- action: ya
- request validation: ya
- policy: ya untuk admin later
- resource transformer: ya
- config: minimum sahaja
- seed data: ya
- routes: ya
- tests: ya

**Priority**
- backend-first priority now

**Anchor files awal**
- `backend/app/Models/Rider.php`
- `backend/app/Models/DeliveryAssignment.php`
- `backend/app/Models/RiderTrackingEvent.php`
- `backend/app/Services/Riders/RiderService.php`
- `backend/app/Actions/Riders/AssignRiderToOrder.php`
- `backend/app/Actions/Riders/AppendTrackingEvent.php`
- `backend/app/Http/Controllers/Api/Riders/RiderController.php`
- `backend/app/Http/Requests/Riders/AssignRiderRequest.php`
- `backend/app/Http/Resources/Riders/RiderAssignmentResource.php`
- `backend/database/seeders/RiderSeeder.php`
- `backend/routes/admin.php`
- `backend/routes/simulation.php`

**Jangan bina lagi sekarang**
- live GPS SDK
- real courier APIs
- dispatch marketplace logic

---

## Payments

**Tujuan**
- payment method
- payment provider
- branch payment capability
- payment intent
- payment attempt
- payment transaction
- payment abstraction layer
- demo-safe execution only

**Perlu**
- model: ya
- service: ya
- action: ya
- request validation: ya
- policy: ya
- resource transformer: ya
- config: ya
- seed data: ya
- routes: ya
- tests: ya

**Priority**
- backend-first priority now

**Anchor files awal**
- `backend/app/Models/PaymentProvider.php`
- `backend/app/Models/PaymentMethod.php`
- `backend/app/Models/BranchPaymentMethod.php`
- `backend/app/Models/PaymentIntent.php`
- `backend/app/Models/PaymentAttempt.php`
- `backend/app/Models/PaymentTransaction.php`
- `backend/app/Services/Payments/PaymentService.php`
- `backend/app/Actions/Payments/CreatePaymentIntent.php`
- `backend/app/Actions/Payments/ConfirmDemoPayment.php`
- `backend/app/Http/Controllers/Api/Payments/PaymentController.php`
- `backend/app/Http/Requests/Payments/CreatePaymentIntentRequest.php`
- `backend/app/Http/Resources/Payments/PaymentIntentResource.php`
- `backend/config/payments.php`
- `backend/routes/customer.php`
- `backend/routes/admin.php`

**Jangan bina lagi sekarang**
- live capture
- live payout
- real provider SDK
- saved card vault
- real Apple Pay / Google Pay merchant config

---

## Refunds

**Tujuan**
- refund request
- refund review
- full refund
- partial refund
- compensation flow
- store credit placeholder

**Perlu**
- model: ya
- service: ya
- action: ya
- request validation: ya
- policy: ya
- resource transformer: ya
- config: ya
- seed data: ya
- routes: ya
- tests: ya

**Priority**
- backend-first priority now

**Anchor files awal**
- `backend/app/Models/Refund.php`
- `backend/app/Models/RefundItem.php`
- `backend/app/Models/RefundEvent.php`
- `backend/app/Services/Refunds/RefundService.php`
- `backend/app/Actions/Refunds/RequestRefund.php`
- `backend/app/Actions/Refunds/ReviewRefund.php`
- `backend/app/Http/Controllers/Api/Refunds/RefundController.php`
- `backend/app/Http/Requests/Refunds/RefundRequest.php`
- `backend/app/Http/Resources/Refunds/RefundResource.php`
- `backend/config/refunds.php`
- `backend/routes/customer.php`
- `backend/routes/admin.php`

**Jangan bina lagi sekarang**
- chargeback handling
- payment provider reconciliation batch
- accounting ledger

---

## Webhooks

**Tujuan**
- simulated webhook receive
- webhook log
- verification placeholder
- processing flow
- replay-safe structure

**Perlu**
- model: ya
- service: ya
- action: ya
- request validation: ya
- policy: tidak perlu untuk inbound endpoint
- resource transformer: ya untuk admin viewer
- config: ya
- seed data: tidak perlu
- routes: ya
- tests: ya

**Priority**
- backend-first priority now

**Anchor files awal**
- `backend/app/Models/PaymentWebhookEvent.php`
- `backend/app/Services/Webhooks/WebhookService.php`
- `backend/app/Actions/Webhooks/ProcessPaymentWebhook.php`
- `backend/app/Http/Controllers/Api/Webhooks/WebhookController.php`
- `backend/app/Http/Requests/Webhooks/HandleDemoWebhookRequest.php`
- `backend/app/Http/Resources/Webhooks/WebhookEventResource.php`
- `backend/config/webhooks.php`
- `backend/routes/webhooks.php`
- `backend/routes/admin.php`

**Jangan bina lagi sekarang**
- public generic webhook gateway
- multi-provider signature suite penuh
- external queue pipeline

---

## Promos

**Tujuan**
- promo code
- coupon placeholder
- discount rule entry point
- pricing discount integration

**Perlu**
- model: ya
- service: ya
- action: ya
- request validation: ya
- policy: ya
- resource transformer: ya
- config: ya
- seed data: ya
- routes: ya
- tests: ya

**Priority**
- future-ready only for now

**Anchor files awal**
- `backend/app/Models/Promo.php`
- `backend/app/Models/Coupon.php`
- `backend/app/Services/Promos/PromoService.php`
- `backend/app/Actions/Promos/ApplyPromoToCart.php`
- `backend/app/Http/Controllers/Api/Promos/PromoController.php`
- `backend/app/Http/Requests/Promos/ApplyPromoRequest.php`
- `backend/app/Http/Resources/Promos/PromoResource.php`
- `backend/config/promos.php`
- `backend/routes/customer.php`
- `backend/routes/admin.php`

**Jangan bina lagi sekarang**
- loyalty engine
- referral engine
- campaign scheduler

---

## Support

**Tujuan**
- support ticket
- order issue intake
- support message thread
- admin resolution notes

**Perlu**
- model: ya
- service: ya
- action: ya
- request validation: ya
- policy: ya
- resource transformer: ya
- config: tidak perlu sekarang
- seed data: ya jika mahu sample
- routes: ya
- tests: ya

**Priority**
- future-ready only for now

**Anchor files awal**
- `backend/app/Models/SupportTicket.php`
- `backend/app/Models/SupportTicketMessage.php`
- `backend/app/Services/Support/SupportService.php`
- `backend/app/Actions/Support/OpenSupportTicket.php`
- `backend/app/Http/Controllers/Api/Support/SupportController.php`
- `backend/app/Http/Requests/Support/CreateSupportTicketRequest.php`
- `backend/app/Http/Resources/Support/SupportTicketResource.php`
- `backend/routes/customer.php`
- `backend/routes/admin.php`

**Jangan bina lagi sekarang**
- attachment upload
- SLA engine
- omnichannel inbox
- live chat

---

## Notifications

**Tujuan**
- internal notification dispatch
- database notification log
- order/payment/support alert records

**Perlu**
- model: ya
- service: ya
- action: belum perlu batch awal
- request validation: tidak perlu domain route sekarang
- policy: tidak perlu berasingan sekarang
- resource transformer: ya
- config: ya
- seed data: tidak perlu
- routes: belum perlu sekarang
- tests: ya

**Priority**
- future-ready only for now

**Anchor files awal**
- `backend/app/Models/NotificationLog.php`
- `backend/app/Services/Notifications/NotificationService.php`
- `backend/app/Http/Resources/Notifications/NotificationResource.php`
- `backend/config/notifications.php`

**Jangan bina lagi sekarang**
- SMS/email provider fanout
- push notification
- template builder

---

## Admin

**Tujuan**
- admin reference layer
- dashboard summary
- management endpoints for orders, payments, refunds, riders, webhooks, menu, settings

**Perlu**
- model: tidak perlu model khas sekarang
- service: ya
- action: ya
- request validation: ya
- policy: ya
- resource transformer: ya
- config: tidak perlu
- seed data: tidak perlu
- routes: ya
- tests: ya

**Priority**
- future-ready only for now

**Anchor files awal**
- `backend/app/Services/Admin/AdminDashboardService.php`
- `backend/app/Http/Controllers/Api/Admin/AdminDashboardController.php`
- `backend/app/Http/Controllers/Api/Admin/AdminOrderController.php`
- `backend/app/Http/Controllers/Api/Admin/AdminPaymentController.php`
- `backend/app/Http/Controllers/Api/Admin/AdminRefundController.php`
- `backend/app/Http/Controllers/Api/Admin/AdminWebhookController.php`
- `backend/routes/admin.php`

**Jangan bina lagi sekarang**
- heavy BI dashboard
- charting system besar
- bulk action engine
- admin design system khas

---

## Audit

**Tujuan**
- audit logs
- actor trace
- before/after snapshot
- admin action accountability
- simulation trace logging

**Perlu**
- model: ya
- service: ya
- action: belum wajib batch awal
- request validation: tidak perlu domain route khas
- policy: ya
- resource transformer: ya
- config: ya
- seed data: tidak perlu
- routes: admin viewer nanti
- tests: ya

**Priority**
- backend-first priority now

**Anchor files awal**
- `backend/app/Models/AuditLog.php`
- `backend/app/Services/Audit/AuditService.php`
- `backend/app/Http/Resources/Audit/AuditLogResource.php`
- `backend/config/audit.php`
- `backend/routes/admin.php`

**Jangan bina lagi sekarang**
- deep diff engine
- SIEM integration
- retention automation

---

## DemoSimulation

**Tujuan**
- demo guard
- scenario runner
- payment success/fail/pending simulation
- rider delay / fast simulation
- webhook simulation
- refund simulation

**Perlu**
- model: ya
- service: ya
- action: ya
- request validation: ya
- policy: ya
- resource transformer: ya
- config: ya
- seed data: ya
- routes: ya
- tests: ya

**Priority**
- backend-first priority now

**Anchor files awal**
- `backend/app/Models/DemoScenario.php`
- `backend/app/Models/SimulationRun.php`
- `backend/app/Services/Demo/SimulationService.php`
- `backend/app/Actions/Demo/RunPaymentScenario.php`
- `backend/app/Actions/Demo/RunRiderScenario.php`
- `backend/app/Actions/Demo/RunWebhookScenario.php`
- `backend/app/Http/Controllers/Api/Admin/DemoSimulationController.php`
- `backend/app/Http/Requests/Admin/RunSimulationRequest.php`
- `backend/app/Http/Resources/Demo/SimulationRunResource.php`
- `backend/config/demo.php`
- `backend/database/seeders/DemoScenarioSeeder.php`
- `backend/routes/simulation.php`
- `backend/routes/admin.php`

**Jangan bina lagi sekarang**
- visual scenario builder
- advanced orchestration engine
- chained simulation workflow UI

---

# [3] Frontend feature responsibility table

## catalog

**Tujuan**
- paparkan kategori
- paparkan item menu
- item detail ringkas
- modifier selection entry

**Perlu**
- page / route: ya
- components: ya
- hook / query: ya
- API client: ya
- store slice: tidak wajib besar
- types / schema: ya
- tests: ya

**Priority**
- frontend simulation first

**Anchor files awal**
- `frontend/src/features/catalog/api/catalogApi.ts`
- `frontend/src/features/catalog/hooks/useCatalog.ts`
- `frontend/src/features/catalog/components/CatalogGrid.tsx`
- `frontend/src/features/catalog/components/MenuItemCard.tsx`
- `frontend/src/features/catalog/types.ts`

**Jangan bina lagi sekarang**
- search berat
- complex filter panel
- fancy animation

---

## cart

**Tujuan**
- cart panel
- cart page
- update quantity
- remove item
- cart totals view

**Perlu**
- page / route: ya
- components: ya
- hook / query: ya
- API client: ya
- store slice: ya
- types / schema: ya
- tests: ya

**Priority**
- frontend simulation first

**Anchor files awal**
- `frontend/src/features/cart/api/cartApi.ts`
- `frontend/src/features/cart/hooks/useCart.ts`
- `frontend/src/features/cart/store/useCartStore.ts`
- `frontend/src/features/cart/components/CartPanel.tsx`
- `frontend/src/features/cart/components/CartSummary.tsx`
- `frontend/src/features/cart/types.ts`

**Jangan bina lagi sekarang**
- offline cart sync
- advanced persistence conflict handling

---

## checkout

**Tujuan**
- customer details
- address / pickup details
- payment method selection
- final review
- place order submit

**Perlu**
- page / route: ya
- components: ya
- hook / query: ya
- API client: ya
- store slice: ya
- types / schema: ya
- tests: ya

**Priority**
- frontend simulation first

**Anchor files awal**
- `frontend/src/features/checkout/api/checkoutApi.ts`
- `frontend/src/features/checkout/hooks/useCheckout.ts`
- `frontend/src/features/checkout/components/CheckoutForm.tsx`
- `frontend/src/features/checkout/components/CheckoutSummary.tsx`
- `frontend/src/features/checkout/schemas/checkoutSchema.ts`
- `frontend/src/features/checkout/types.ts`

**Jangan bina lagi sekarang**
- wizard flow yang terlalu kompleks
- saved address book
- account recovery flow

---

## fulfillment

**Tujuan**
- pilih delivery / pickup / dine_in
- collect delivery detail
- pickup summary
- dine-in handoff to QR/table flow

**Perlu**
- page / route: tidak semestinya page khas
- components: ya
- hook / query: ya
- API client: ya
- store slice: kongsi dengan checkout
- types / schema: ya
- tests: ya

**Priority**
- frontend simulation first

**Anchor files awal**
- `frontend/src/features/fulfillment/api/fulfillmentApi.ts`
- `frontend/src/features/fulfillment/hooks/useFulfillment.ts`
- `frontend/src/features/fulfillment/components/FulfillmentSelector.tsx`
- `frontend/src/features/fulfillment/components/DeliveryForm.tsx`
- `frontend/src/features/fulfillment/types.ts`

**Jangan bina lagi sekarang**
- map preview
- schedule slot picker berat

---

## orders

**Tujuan**
- order success
- order detail
- order timeline
- customer order state

**Perlu**
- page / route: ya
- components: ya
- hook / query: ya
- API client: ya
- store slice: tidak wajib
- types / schema: ya
- tests: ya

**Priority**
- backend-first priority now

**Anchor files awal**
- `frontend/src/features/orders/api/ordersApi.ts`
- `frontend/src/features/orders/hooks/useOrder.ts`
- `frontend/src/features/orders/components/OrderStatusTimeline.tsx`
- `frontend/src/features/orders/pages/OrderDetailPage.tsx`
- `frontend/src/features/orders/types.ts`

**Jangan bina lagi sekarang**
- complete order history dashboard
- reorder flow

---

## payments

**Tujuan**
- payment method list
- demo payment state
- payment intent status
- success/fail/pending presentation

**Perlu**
- page / route: tidak perlu page besar berasingan
- components: ya
- hook / query: ya
- API client: ya
- store slice: ringan
- types / schema: ya
- tests: ya

**Priority**
- backend-first priority now

**Anchor files awal**
- `frontend/src/features/payments/api/paymentsApi.ts`
- `frontend/src/features/payments/hooks/usePaymentIntent.ts`
- `frontend/src/features/payments/components/PaymentMethodSelector.tsx`
- `frontend/src/features/payments/components/PaymentDemoStateCard.tsx`
- `frontend/src/features/payments/types.ts`

**Jangan bina lagi sekarang**
- real wallet SDK
- PCI-like card form complexity
- saved card UI

---

## dine-in

**Tujuan**
- call waiter
- request bill
- split bill actions
- dine-in order actions

**Perlu**
- page / route: ya
- components: ya
- hook / query: ya
- API client: ya
- store slice: kemudian jika perlu
- types / schema: ya
- tests: ya

**Priority**
- future-ready only for now

**Anchor files awal**
- `frontend/src/features/dine-in/api/dineInApi.ts`
- `frontend/src/features/dine-in/hooks/useDineIn.ts`
- `frontend/src/features/dine-in/components/DineInActions.tsx`
- `frontend/src/features/dine-in/components/SplitBillPanel.tsx`
- `frontend/src/features/dine-in/types.ts`

**Jangan bina lagi sekarang**
- seat map
- waiter workflow UI berat

---

## qr-session

**Tujuan**
- resolve QR session
- join active table session
- open session-based ordering context

**Perlu**
- page / route: ya
- components: minimum
- hook / query: ya
- API client: ya
- store slice: tidak wajib
- types / schema: ya
- tests: ya

**Priority**
- backend-first priority now

**Anchor files awal**
- `frontend/src/features/qr-session/api/qrSessionApi.ts`
- `frontend/src/features/qr-session/hooks/useQrSession.ts`
- `frontend/src/features/qr-session/pages/QrSessionPage.tsx`
- `frontend/src/features/qr-session/types.ts`

**Jangan bina lagi sekarang**
- camera scanner
- QR generator UI

---

## tracking

**Tujuan**
- delivery tracking
- pickup ready state
- rider card
- ETA timeline

**Perlu**
- page / route: ya
- components: ya
- hook / query: ya
- API client: ya
- store slice: tidak wajib
- types / schema: ya
- tests: ya

**Priority**
- frontend simulation first

**Anchor files awal**
- `frontend/src/features/tracking/api/trackingApi.ts`
- `frontend/src/features/tracking/hooks/useTracking.ts`
- `frontend/src/features/tracking/components/TrackingTimeline.tsx`
- `frontend/src/features/tracking/components/RiderCard.tsx`
- `frontend/src/features/tracking/types.ts`

**Jangan bina lagi sekarang**
- live map
- realtime animation
- geolocation

---

## promos

**Tujuan**
- promo code input
- apply / remove promo
- discount feedback UI

**Perlu**
- page / route: tidak perlu khas
- components: ya
- hook / query: ya
- API client: ya
- store slice: ringan
- types / schema: ya
- tests: ya

**Priority**
- future-ready only for now

**Anchor files awal**
- `frontend/src/features/promos/api/promosApi.ts`
- `frontend/src/features/promos/hooks/usePromo.ts`
- `frontend/src/features/promos/components/PromoCodeField.tsx`
- `frontend/src/features/promos/types.ts`

**Jangan bina lagi sekarang**
- promo landing page
- campaign center

---

## support

**Tujuan**
- open support ticket
- issue form
- ticket thread view

**Perlu**
- page / route: ya
- components: ya
- hook / query: ya
- API client: ya
- store slice: tidak wajib
- types / schema: ya
- tests: ya

**Priority**
- future-ready only for now

**Anchor files awal**
- `frontend/src/features/support/api/supportApi.ts`
- `frontend/src/features/support/hooks/useSupport.ts`
- `frontend/src/features/support/components/SupportTicketForm.tsx`
- `frontend/src/features/support/pages/SupportPage.tsx`
- `frontend/src/features/support/types.ts`

**Jangan bina lagi sekarang**
- attachment upload
- live chat

---

## admin-reference

**Tujuan**
- admin reference UI
- dashboard summary
- orders
- payments
- refunds
- riders
- webhooks
- audit viewer

**Perlu**
- page / route: ya
- components: ya
- hook / query: ya
- API client: ya
- store slice: minimum
- types / schema: ya
- tests: ya

**Priority**
- future-ready only for now

**Anchor files awal**
- `frontend/src/features/admin-reference/api/adminApi.ts`
- `frontend/src/features/admin-reference/pages/AdminDashboardPage.tsx`
- `frontend/src/features/admin-reference/pages/AdminOrdersPage.tsx`
- `frontend/src/features/admin-reference/components/AdminStatCard.tsx`
- `frontend/src/features/admin-reference/types.ts`

**Jangan bina lagi sekarang**
- full admin design system
- heavy charts
- bulk actions

---

# [4] Anchor files yang patut dibuat dahulu

## A. Wajib lock dokumentasi domain dahulu

Jika folder domain backend sudah ada, isi `README.md` setiap domain dahulu.

- `backend/app/Domain/Auth/README.md`
- `backend/app/Domain/Restaurant/README.md`
- `backend/app/Domain/Catalog/README.md`
- `backend/app/Domain/Cart/README.md`
- `backend/app/Domain/Pricing/README.md`
- `backend/app/Domain/Orders/README.md`
- `backend/app/Domain/Fulfillment/README.md`
- `backend/app/Domain/DineIn/README.md`
- `backend/app/Domain/QrSessions/README.md`
- `backend/app/Domain/Riders/README.md`
- `backend/app/Domain/Payments/README.md`
- `backend/app/Domain/Refunds/README.md`
- `backend/app/Domain/Webhooks/README.md`
- `backend/app/Domain/Promos/README.md`
- `backend/app/Domain/Support/README.md`
- `backend/app/Domain/Notifications/README.md`
- `backend/app/Domain/Admin/README.md`
- `backend/app/Domain/Audit/README.md`
- `backend/app/Domain/DemoSimulation/README.md`

## B. Route anchors yang dikunci

Route structure semasa dikunci seperti ini:

- `backend/routes/auth.php`
- `backend/routes/customer.php`
- `backend/routes/admin.php`
- `backend/routes/simulation.php`
- `backend/routes/webhooks.php`
- `backend/routes/internal.php`

## C. Backend batch awal paling penting

### Core models
- `backend/app/Models/User.php`
- `backend/app/Models/Restaurant.php`
- `backend/app/Models/Branch.php`
- `backend/app/Models/MenuCategory.php`
- `backend/app/Models/MenuItem.php`
- `backend/app/Models/Cart.php`
- `backend/app/Models/CartItem.php`
- `backend/app/Models/Order.php`
- `backend/app/Models/OrderItem.php`
- `backend/app/Models/PaymentIntent.php`
- `backend/app/Models/PaymentTransaction.php`

### Core services
- `backend/app/Services/Auth/AuthService.php`
- `backend/app/Services/Restaurant/RestaurantService.php`
- `backend/app/Services/Catalog/CatalogService.php`
- `backend/app/Services/Pricing/PricingService.php`
- `backend/app/Services/Cart/CartService.php`
- `backend/app/Services/Orders/OrderService.php`
- `backend/app/Services/Payments/PaymentService.php`
- `backend/app/Services/Fulfillment/FulfillmentService.php`

### Core actions
- `backend/app/Actions/Cart/AddItemToCart.php`
- `backend/app/Actions/Orders/PlaceOrder.php`
- `backend/app/Actions/Orders/TransitionOrderStatus.php`
- `backend/app/Actions/Payments/CreatePaymentIntent.php`
- `backend/app/Actions/Payments/ConfirmDemoPayment.php`

### Core requests
- `backend/app/Http/Requests/Auth/LoginRequest.php`
- `backend/app/Http/Requests/Cart/AddCartItemRequest.php`
- `backend/app/Http/Requests/Orders/PlaceOrderRequest.php`
- `backend/app/Http/Requests/Payments/CreatePaymentIntentRequest.php`
- `backend/app/Http/Requests/Fulfillment/SetFulfillmentRequest.php`

### Core controllers
- `backend/app/Http/Controllers/Api/Auth/AuthController.php`
- `backend/app/Http/Controllers/Api/Catalog/CatalogController.php`
- `backend/app/Http/Controllers/Api/Cart/CartController.php`
- `backend/app/Http/Controllers/Api/Orders/OrderController.php`
- `backend/app/Http/Controllers/Api/Payments/PaymentController.php`

### Core resources
- `backend/app/Http/Resources/Auth/AuthUserResource.php`
- `backend/app/Http/Resources/Catalog/MenuItemResource.php`
- `backend/app/Http/Resources/Cart/CartResource.php`
- `backend/app/Http/Resources/Orders/OrderResource.php`
- `backend/app/Http/Resources/Payments/PaymentIntentResource.php`

### Core config
- `backend/config/demo.php`
- `backend/config/payments.php`
- `backend/config/pricing.php`
- `backend/config/fulfillment.php`
- `backend/config/dinein.php`
- `backend/config/qr.php`
- `backend/config/refunds.php`
- `backend/config/webhooks.php`

### Core seeders
- `backend/database/seeders/RoleSeeder.php`
- `backend/database/seeders/AdminUserSeeder.php`
- `backend/database/seeders/RestaurantSeeder.php`
- `backend/database/seeders/MenuSeeder.php`
- `backend/database/seeders/PricingSeeder.php`
- `backend/database/seeders/RiderSeeder.php`
- `backend/database/seeders/DemoScenarioSeeder.php`

## D. Frontend batch awal paling penting

### catalog
- `frontend/src/features/catalog/api/catalogApi.ts`
- `frontend/src/features/catalog/hooks/useCatalog.ts`
- `frontend/src/features/catalog/components/CatalogGrid.tsx`
- `frontend/src/features/catalog/types.ts`

### cart
- `frontend/src/features/cart/api/cartApi.ts`
- `frontend/src/features/cart/hooks/useCart.ts`
- `frontend/src/features/cart/store/useCartStore.ts`
- `frontend/src/features/cart/components/CartPanel.tsx`
- `frontend/src/features/cart/types.ts`

### checkout
- `frontend/src/features/checkout/api/checkoutApi.ts`
- `frontend/src/features/checkout/hooks/useCheckout.ts`
- `frontend/src/features/checkout/components/CheckoutForm.tsx`
- `frontend/src/features/checkout/schemas/checkoutSchema.ts`
- `frontend/src/features/checkout/types.ts`

### fulfillment
- `frontend/src/features/fulfillment/api/fulfillmentApi.ts`
- `frontend/src/features/fulfillment/components/FulfillmentSelector.tsx`
- `frontend/src/features/fulfillment/types.ts`

### payments
- `frontend/src/features/payments/api/paymentsApi.ts`
- `frontend/src/features/payments/hooks/usePaymentIntent.ts`
- `frontend/src/features/payments/components/PaymentMethodSelector.tsx`
- `frontend/src/features/payments/types.ts`

### orders
- `frontend/src/features/orders/api/ordersApi.ts`
- `frontend/src/features/orders/hooks/useOrder.ts`
- `frontend/src/features/orders/components/OrderStatusTimeline.tsx`
- `frontend/src/features/orders/types.ts`

### qr-session
- `frontend/src/features/qr-session/api/qrSessionApi.ts`
- `frontend/src/features/qr-session/hooks/useQrSession.ts`
- `frontend/src/features/qr-session/pages/QrSessionPage.tsx`
- `frontend/src/features/qr-session/types.ts`

---

# [5] Apa yang belum perlu dibuat sekarang

## Backend

Jangan bina ini dulu:
- repository layer untuk semua domain
- service provider khas setiap domain
- CQRS penuh
- event sourcing
- microservices
- real payment SDK integration
- live capture
- live payout
- live wallet config
- external courier integration
- advanced inventory module
- loyalty module
- referral module
- warehouse module
- reporting warehouse
- file upload pipeline besar
- realtime websocket tracking sebenar
- multi-restaurant tenancy
- terlalu banyak route abstraction

## Frontend

Jangan bina ini dulu:
- redesign UI
- heavy admin UI
- map tracking sebenar
- QR scanner camera flow
- animation berlebihan
- promo campaign pages
- support attachment upload
- offline-first complex flows
- large global state yang tidak perlu

## Kaedah kerja yang dikunci selepas Step 3

1. Step 3 hanya lock tanggungjawab
2. implementation besar belum dimulakan
3. selepas ini barulah masuk Step 4
4. Step 4 fokus kepada:
   - database plan lock
   - migration priority
   - naming convention final
   - table dependency order

---

# Step 3 completion note

Step 3 dianggap siap apabila:
- `docs/domain-map.md` siap
- README domain backend diisi sekurang-kurangnya secara ringkas
- route anchor structure dikunci
- batch awal anchor files dikenalpasti
- semua pihak jelas apa yang belum patut dibina lagi
