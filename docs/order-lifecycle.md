# ORDERra Order Lifecycle

## Core Statuses
- cart_draft
- pending_payment
- payment_authorized
- placed
- confirmed
- preparing
- ready

## Delivery Branch
- awaiting_rider
- rider_assigned
- picked_up
- near_customer
- delivered

## Pickup Branch
- ready_for_pickup
- picked_up_by_customer

## Dine-In Branch
- served
- bill_requested
- paid_at_table

## Closure Statuses
- completed
- cancelled
- refund_pending
- refunded
- partially_refunded

## Rule
Not every fulfillment type uses every status.
Statuses must stay modular based on:
- delivery
- pickup
- dine_in
