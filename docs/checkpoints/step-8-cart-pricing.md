# Step 8 Checkpoint — Cart + Pricing

Status: backend checkpoint complete

Source of truth:

- backend_fresh

Completed:

- carts table
- cart_items table
- tax_rules table
- fee_rules table
- Cart model
- CartItem model
- PricingService
- CartService
- CartController
- cart routes
- catalog + cart api under /api/v1
- config-driven pricing foundation

Notes:

- pricing currently uses config fallback first
- tax_rules and fee_rules tables are ready for expansion
- frontend cart integration can continue later
