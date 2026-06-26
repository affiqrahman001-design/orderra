export const helpCenterCategories = [
  {
    key: 'getting_started',
    label: 'Getting Started',
    description: 'Basic questions about using ORDERra.',
  },
  {
    key: 'menu_customization',
    label: 'Menu & Customization',
    description: 'Menu items, modifiers, allergies, and notes.',
  },
  {
    key: 'cart_checkout',
    label: 'Cart & Checkout',
    description: 'Cart flow, order review, fees, and checkout steps.',
  },
  {
    key: 'dine_in_qr',
    label: 'Dine-in & QR',
    description: 'Table ordering, QR sessions, split bill, and waiter requests.',
  },
  {
    key: 'pickup',
    label: 'Pickup',
    description: 'Pickup timing, ready status, and verification demo.',
  },
  {
    key: 'delivery_tracking',
    label: 'Delivery & Tracking',
    description: 'Delivery flow, ETA, rider simulation, and tracking timeline.',
  },
  {
    key: 'payment_simulation',
    label: 'Payment Simulation',
    description: 'Demo-safe payment methods, receipts, and payment statuses.',
  },
  {
    key: 'refunds_issues',
    label: 'Refunds & Issues',
    description: 'Cancellation, missing items, wrong items, and refund simulation.',
  },
  {
    key: 'account_privacy',
    label: 'Account & Privacy',
    description: 'Demo account, storage, privacy, and data safety.',
  },
  {
    key: 'support_demo',
    label: 'Support Demo',
    description: 'How ORDERra demo support answers questions and handles human support requests.',
  },
] as const;

export type HelpCenterCategoryKey = (typeof helpCenterCategories)[number]['key'];

export interface HelpCenterFaq {
  id: string;
  category: HelpCenterCategoryKey;
  question: string;
  answer: string;
  tags: string[];
}

export const helpCenterFaqs: HelpCenterFaq[] = [
  {
    id: 'gs-01',
    category: 'getting_started',
    question: 'What is ORDERra?',
    answer:
      'ORDERra is a premium single-restaurant burger ordering platform demo. It is built for portfolio review and does not process real orders or payments.',
    tags: ['about', 'demo', 'platform'],
  },
  {
    id: 'gs-02',
    category: 'getting_started',
    question: 'Is ORDERra a real restaurant app?',
    answer:
      'ORDERra is designed to feel like a real restaurant ordering app, but it is currently a demo-safe portfolio system. No real restaurant fulfillment is triggered.',
    tags: ['real', 'restaurant', 'demo'],
  },
  {
    id: 'gs-03',
    category: 'getting_started',
    question: 'Can I place a real food order through ORDERra?',
    answer:
      'No. Orders created in ORDERra are demo orders only and are not sent to a real kitchen, rider, or restaurant team.',
    tags: ['order', 'real order', 'demo order'],
  },
  {
    id: 'gs-04',
    category: 'getting_started',
    question: 'What can I test in ORDERra?',
    answer:
      'You can test menu browsing, cart, checkout, dine-in QR flow, pickup, delivery tracking, payment simulation, refund simulation, and admin-style demo references.',
    tags: ['test', 'features', 'demo'],
  },
  {
    id: 'gs-05',
    category: 'getting_started',
    question: 'Do I need an account to browse the menu?',
    answer:
      'No. The menu can be browsed without signing in. Some demo flows may use sample account behavior to show a more realistic product experience.',
    tags: ['account', 'menu', 'browse'],
  },
  {
    id: 'gs-06',
    category: 'getting_started',
    question: 'Why does ORDERra look like a production app?',
    answer:
      'ORDERra is built as a polished portfolio system to show realistic ordering flow, clean product design, and safe demo behavior. Real business actions remain disabled.',
    tags: ['production', 'portfolio', 'design'],
  },
  {
    id: 'gs-07',
    category: 'getting_started',
    question: 'Is ORDERra only for burgers?',
    answer:
      'The current demo focuses on a premium burger restaurant. It can still be used as a reference for other food ordering systems later.',
    tags: ['burger', 'restaurant', 'menu'],
  },
  {
    id: 'gs-08',
    category: 'getting_started',
    question: 'Can ORDERra support delivery, pickup, and dine-in?',
    answer:
      'Yes. ORDERra demonstrates delivery, pickup, and dine-in flows as separate fulfillment modes, while keeping all real-world operations simulated.',
    tags: ['delivery', 'pickup', 'dine-in'],
  },
  {
    id: 'gs-09',
    category: 'getting_started',
    question: 'Why are some actions marked as demo or simulation?',
    answer:
      'These labels protect the demo boundary. They make it clear that payment, rider, refund, webhook, and support actions are not real production operations.',
    tags: ['simulation', 'safe', 'demo'],
  },
  {
    id: 'gs-10',
    category: 'getting_started',
    question: 'Can ORDERra be upgraded into a real system later?',
    answer:
      'Yes, the structure is designed to be upgrade-ready. Real payment, dispatch, support, compliance, and production security would need to be added separately.',
    tags: ['upgrade', 'production', 'future'],
  },

  {
    id: 'menu-01',
    category: 'menu_customization',
    question: 'How do I browse the menu?',
    answer:
      'Use the menu sections or category filters to browse burgers, combos, sides, desserts, and drinks. You can also use search to find a specific item faster.',
    tags: ['menu', 'browse', 'category'],
  },
  {
    id: 'menu-02',
    category: 'menu_customization',
    question: 'Can I search for a specific food item?',
    answer:
      'Yes. Use the search bar to filter menu items by name, description, or related keywords.',
    tags: ['search', 'menu item', 'food'],
  },
  {
    id: 'menu-03',
    category: 'menu_customization',
    question: 'Can I customize a burger?',
    answer:
      'Yes. The demo supports item customization such as variants, add-ons, modifiers, and special notes where available.',
    tags: ['customize', 'burger', 'modifier'],
  },
  {
    id: 'menu-04',
    category: 'menu_customization',
    question: 'Can I remove ingredients?',
    answer:
      'Where the item supports notes or modifiers, you can request changes such as removing an ingredient. This is shown as a demo note and not sent to a real kitchen.',
    tags: ['remove', 'ingredient', 'notes'],
  },
  {
    id: 'menu-05',
    category: 'menu_customization',
    question: 'Can I leave an allergy note?',
    answer:
      'Yes, allergy or ingredient notes can be entered where the demo form allows it. Do not enter sensitive medical information in this portfolio demo.',
    tags: ['allergy', 'ingredient', 'safety'],
  },
  {
    id: 'menu-06',
    category: 'menu_customization',
    question: 'Are combo meals supported?',
    answer:
      'Yes. ORDERra includes combo-style menu items to demonstrate bundled food ordering and real-world restaurant menu structure.',
    tags: ['combo', 'meal', 'bundle'],
  },
  {
    id: 'menu-07',
    category: 'menu_customization',
    question: 'Can I add sides and drinks?',
    answer:
      'Yes. Sides and drinks can be added like normal menu items, and selected items appear in the cart before checkout.',
    tags: ['sides', 'drinks', 'cart'],
  },
  {
    id: 'menu-08',
    category: 'menu_customization',
    question: 'Why are drinks listed separately?',
    answer:
      'Drinks are separated to keep the menu clean and easy to browse. This also reflects how many food ordering apps organize menu categories.',
    tags: ['drinks', 'category', 'menu layout'],
  },
  {
    id: 'menu-09',
    category: 'menu_customization',
    question: 'Do prices change when I customize an item?',
    answer:
      'Some modifiers or options may affect the displayed demo price. Final pricing is shown in the cart and checkout summary.',
    tags: ['price', 'modifier', 'checkout'],
  },
  {
    id: 'menu-10',
    category: 'menu_customization',
    question: 'Can I order multiple quantities of the same item?',
    answer:
      'Yes. You can increase or decrease item quantity from the cart or item controls where available.',
    tags: ['quantity', 'cart', 'item'],
  },
  {
    id: 'menu-11',
    category: 'menu_customization',
    question: 'Are menu images real product photos?',
    answer:
      'Menu images are used for portfolio presentation and may be demo assets. They help show how the final product experience could look.',
    tags: ['images', 'assets', 'portfolio'],
  },
  {
    id: 'menu-12',
    category: 'menu_customization',
    question: 'Can unavailable items be hidden later?',
    answer:
      'Yes. The system can be extended to support availability, sold-out states, branch-specific menus, and scheduled menu visibility.',
    tags: ['availability', 'sold out', 'future'],
  },

  {
    id: 'cart-01',
    category: 'cart_checkout',
    question: 'How do I add items to the cart?',
    answer:
      'Open a menu item, choose any available options, then add it to the cart. The cart summary updates with the selected quantity and price.',
    tags: ['cart', 'add item', 'menu'],
  },
  {
    id: 'cart-02',
    category: 'cart_checkout',
    question: 'Can I edit my cart before checkout?',
    answer:
      'Yes. You can review quantities, remove items, and update selections before placing the demo order.',
    tags: ['edit cart', 'quantity', 'checkout'],
  },
  {
    id: 'cart-03',
    category: 'cart_checkout',
    question: 'Can I clear the cart?',
    answer:
      'Yes. You can remove items manually from the cart. Clearing browser storage may also remove locally saved demo cart data.',
    tags: ['clear cart', 'remove', 'storage'],
  },
  {
    id: 'cart-04',
    category: 'cart_checkout',
    question: 'Why does the cart show a total?',
    answer:
      'The total helps demonstrate a realistic checkout summary. It may include item subtotal, demo fees, tips, taxes, or discounts depending on the flow.',
    tags: ['total', 'fees', 'summary'],
  },
  {
    id: 'cart-05',
    category: 'cart_checkout',
    question: 'Are taxes and fees real?',
    answer:
      'No. Taxes and fees in ORDERra are demo calculations or placeholders. They do not represent a final legal tax calculation for a real transaction.',
    tags: ['tax', 'fees', 'demo'],
  },
  {
    id: 'cart-06',
    category: 'cart_checkout',
    question: 'Can I use a promo code?',
    answer:
      'Promo code behavior may be shown as part of the demo checkout flow. Any discount is simulated and does not apply to a real purchase.',
    tags: ['promo', 'coupon', 'discount'],
  },
  {
    id: 'cart-07',
    category: 'cart_checkout',
    question: 'Can I choose delivery, pickup, or dine-in at checkout?',
    answer:
      'Yes. ORDERra demonstrates different fulfillment modes so the checkout experience can change based on your selected order type.',
    tags: ['fulfillment', 'delivery', 'pickup', 'dine-in'],
  },
  {
    id: 'cart-08',
    category: 'cart_checkout',
    question: 'Can I add a tip?',
    answer:
      'Tip options may appear as part of the demo checkout experience. Tips are simulated and are not paid to any real staff or rider.',
    tags: ['tip', 'checkout', 'simulation'],
  },
  {
    id: 'cart-09',
    category: 'cart_checkout',
    question: 'Can I review the order before placing it?',
    answer:
      'Yes. The checkout summary lets you review selected items, fulfillment mode, price details, and demo payment method before confirming.',
    tags: ['review', 'summary', 'place order'],
  },
  {
    id: 'cart-10',
    category: 'cart_checkout',
    question: 'What happens after I place an order?',
    answer:
      'ORDERra creates a demo confirmation state and may show simulated order progress. No real kitchen, rider, or payment provider receives the order.',
    tags: ['after order', 'confirmation', 'status'],
  },
  {
    id: 'cart-11',
    category: 'cart_checkout',
    question: 'Why did my cart reset?',
    answer:
      'The cart may reset if browser storage is cleared, the demo is refreshed in a certain state, or the local session changes. This is normal for a portfolio demo.',
    tags: ['cart reset', 'storage', 'session'],
  },
  {
    id: 'cart-12',
    category: 'cart_checkout',
    question: 'Can checkout be made real later?',
    answer:
      'Yes. The checkout flow can be upgraded later, but real payment, restaurant fulfillment, and security checks must be reviewed first.',
    tags: ['checkout', 'real order', 'production'],
  },

  {
    id: 'qr-01',
    category: 'dine_in_qr',
    question: 'What is dine-in QR ordering?',
    answer:
      'Dine-in QR ordering lets a guest join a table session and place a demo order from the table. In ORDERra, this flow is simulated for portfolio review.',
    tags: ['qr', 'dine-in', 'table'],
  },
  {
    id: 'qr-02',
    category: 'dine_in_qr',
    question: 'Can I join a table session?',
    answer:
      'Yes. A demo QR session can open a table-specific ordering page, depending on the route or session code used.',
    tags: ['join', 'session', 'table'],
  },
  {
    id: 'qr-03',
    category: 'dine_in_qr',
    question: 'Does scanning the QR code contact a real restaurant?',
    answer: 'No. The QR flow is demo-safe and does not notify a real restaurant or staff member.',
    tags: ['qr scan', 'restaurant', 'demo'],
  },
  {
    id: 'qr-04',
    category: 'dine_in_qr',
    question: 'Can I add more items to the same table order?',
    answer:
      'Yes, ORDERra can demonstrate that idea in the dine-in flow. A real restaurant version would need proper table rules and staff controls.',
    tags: ['add more', 'table order', 'session'],
  },
  {
    id: 'qr-05',
    category: 'dine_in_qr',
    question: 'Can multiple guests order from the same table?',
    answer:
      'Yes, ORDERra can demonstrate that concept. A real restaurant version would need clear guest, table, and order rules.',
    tags: ['multiple guests', 'table', 'guest order'],
  },
  {
    id: 'qr-06',
    category: 'dine_in_qr',
    question: 'Can I request the bill from the table?',
    answer:
      'A bill request can be demonstrated as a dine-in action. It does not alert a real waiter in the current demo.',
    tags: ['bill', 'waiter', 'dine-in'],
  },
  {
    id: 'qr-07',
    category: 'dine_in_qr',
    question: 'Can I call a waiter?',
    answer:
      'The call waiter flow may be shown as a demo action. It does not contact a real waiter or service team.',
    tags: ['waiter', 'call waiter', 'support'],
  },
  {
    id: 'qr-08',
    category: 'dine_in_qr',
    question: 'Can I split the bill?',
    answer:
      'Yes. ORDERra demonstrates split bill concepts such as equal split and item-based split, especially for dine-in ordering.',
    tags: ['split bill', 'equal split', 'item split'],
  },
  {
    id: 'qr-09',
    category: 'dine_in_qr',
    question: 'Is pay-at-table real?',
    answer:
      'No. Pay-at-table is a placeholder in this demo and does not run a live payment transaction.',
    tags: ['pay at table', 'payment', 'placeholder'],
  },
  {
    id: 'qr-10',
    category: 'dine_in_qr',
    question: 'Can table sessions expire?',
    answer:
      'Yes. A real restaurant version could close table sessions after a set time. The demo may show the idea without enforcing every live rule.',
    tags: ['expire', 'session', 'table'],
  },
  {
    id: 'qr-11',
    category: 'dine_in_qr',
    question: 'Can staff manage QR sessions?',
    answer:
      'The admin reference area can demonstrate QR session management. Real staff actions would require proper authentication and permissions.',
    tags: ['staff', 'admin', 'qr session'],
  },
  {
    id: 'qr-12',
    category: 'dine_in_qr',
    question: 'Can QR ordering be reused for other restaurants?',
    answer:
      'Yes. The flow is useful as a reference for restaurant table ordering, cafe ordering, food courts, and event-based ordering.',
    tags: ['reuse', 'restaurant', 'future'],
  },

  {
    id: 'pickup-01',
    category: 'pickup',
    question: 'Can I choose pickup?',
    answer: 'Yes. Pickup is one of the demo fulfillment modes in ORDERra.',
    tags: ['pickup', 'fulfillment', 'checkout'],
  },
  {
    id: 'pickup-02',
    category: 'pickup',
    question: 'Does pickup create a real restaurant order?',
    answer: 'No. Pickup orders are simulated and are not sent to a real restaurant kitchen.',
    tags: ['pickup order', 'demo', 'kitchen'],
  },
  {
    id: 'pickup-03',
    category: 'pickup',
    question: 'Can I choose a pickup time?',
    answer:
      'The demo may show pickup timing or estimated ready time. Production pickup scheduling would need branch hours and kitchen capacity rules.',
    tags: ['pickup time', 'ready time', 'schedule'],
  },
  {
    id: 'pickup-04',
    category: 'pickup',
    question: 'What does ready for pickup mean?',
    answer:
      'It means the demo order has reached a simulated ready state. No real food is prepared.',
    tags: ['ready', 'pickup status', 'simulation'],
  },
  {
    id: 'pickup-05',
    category: 'pickup',
    question: 'Can I cancel a pickup order?',
    answer:
      'Cancellation can be simulated based on order status. Real cancellation rules would depend on restaurant policy and production payment status.',
    tags: ['cancel', 'pickup', 'refund'],
  },
  {
    id: 'pickup-06',
    category: 'pickup',
    question: 'Can pickup include split bill?',
    answer:
      'Split bill can be demonstrated as a reference flow, but pickup usually works best with one primary payer in production.',
    tags: ['split bill', 'pickup', 'payer'],
  },
  {
    id: 'pickup-07',
    category: 'pickup',
    question: 'Is pickup verification supported?',
    answer:
      'ORDERra can show a pickup verification placeholder. A production system would need staff validation or an order pickup code.',
    tags: ['verification', 'pickup code', 'staff'],
  },
  {
    id: 'pickup-08',
    category: 'pickup',
    question: 'Can pickup fees be configured?',
    answer:
      'Yes. Pickup usually has fewer fees than delivery, and fee behavior can be configuration-driven in a backend version.',
    tags: ['fees', 'pickup', 'config'],
  },
  {
    id: 'pickup-09',
    category: 'pickup',
    question: 'Can pickup be disabled outside business hours?',
    answer:
      'Yes. A production version can disable pickup based on branch hours, holidays, kitchen load, or temporary closures.',
    tags: ['business hours', 'pickup', 'availability'],
  },

  {
    id: 'delivery-01',
    category: 'delivery_tracking',
    question: 'Can I choose delivery?',
    answer:
      'Yes. Delivery is supported as a demo fulfillment mode with simulated delivery fee, ETA, and tracking states.',
    tags: ['delivery', 'fulfillment', 'eta'],
  },
  {
    id: 'delivery-02',
    category: 'delivery_tracking',
    question: 'Does ORDERra send a real rider?',
    answer:
      'No. Rider assignment and movement are simulated only. No real rider or dispatch provider is contacted.',
    tags: ['rider', 'dispatch', 'simulation'],
  },
  {
    id: 'delivery-03',
    category: 'delivery_tracking',
    question: 'What is the delivery ETA?',
    answer:
      'The ETA is a demo estimate used to show how tracking could work. It is not calculated from a real courier network.',
    tags: ['eta', 'tracking', 'delivery'],
  },
  {
    id: 'delivery-04',
    category: 'delivery_tracking',
    question: 'Can I track the rider?',
    answer:
      'ORDERra may show a simulated tracking timeline. It does not show a real map location or live rider GPS.',
    tags: ['tracking', 'gps', 'rider'],
  },
  {
    id: 'delivery-05',
    category: 'delivery_tracking',
    question: 'What does rider assigned mean?',
    answer:
      'It means the demo order has moved into a simulated rider assignment state. No real person is assigned.',
    tags: ['rider assigned', 'status', 'demo'],
  },
  {
    id: 'delivery-06',
    category: 'delivery_tracking',
    question: 'What does picked up mean?',
    answer:
      'It means the simulated rider timeline has reached the pickup stage. It does not represent a real food pickup.',
    tags: ['picked up', 'delivery status', 'timeline'],
  },
  {
    id: 'delivery-07',
    category: 'delivery_tracking',
    question: 'What does near customer mean?',
    answer:
      'It is a demo tracking state used to show late-stage delivery progress. It is not based on real GPS data.',
    tags: ['near customer', 'tracking', 'gps'],
  },
  {
    id: 'delivery-08',
    category: 'delivery_tracking',
    question: 'Can delivery fees change?',
    answer:
      'Yes. A real version could adjust delivery fees by zone, distance, rider type, peak hours, or minimum order rules.',
    tags: ['delivery fee', 'zone', 'fee rules'],
  },
  {
    id: 'delivery-09',
    category: 'delivery_tracking',
    question: 'Does ORDERra support self-rider and third-party rider?',
    answer:
      'ORDERra can demonstrate both own-rider and third-party rider options. No real courier service is contacted.',
    tags: ['self rider', 'third party', 'courier'],
  },
  {
    id: 'delivery-10',
    category: 'delivery_tracking',
    question: 'Can I change the delivery address after checkout?',
    answer:
      'The demo may not support address changes after order placement. A production system would need status-based rules and staff approval.',
    tags: ['address', 'change', 'delivery'],
  },
  {
    id: 'delivery-11',
    category: 'delivery_tracking',
    question: 'Can delivery be restricted by zone?',
    answer:
      'Yes. Zone-based delivery is a realistic backend feature and can be configured later for production use.',
    tags: ['zone', 'delivery area', 'coverage'],
  },
  {
    id: 'delivery-12',
    category: 'delivery_tracking',
    question: 'Can ORDERra integrate with real delivery services later?',
    answer:
      'Yes, but real dispatch integration must be added carefully with provider credentials, webhook verification, error handling, and production monitoring.',
    tags: ['integration', 'dispatch', 'future'],
  },

  {
    id: 'pay-01',
    category: 'payment_simulation',
    question: 'Does ORDERra process real payments?',
    answer:
      'No. ORDERra does not process real payments, charge cards, capture funds, or send money to a restaurant.',
    tags: ['payment', 'real charge', 'demo'],
  },
  {
    id: 'pay-02',
    category: 'payment_simulation',
    question: 'Can I enter real card details?',
    answer:
      'No. Do not enter real card numbers, CVV codes, bank credentials, or wallet credentials into the demo.',
    tags: ['card', 'card data', 'security'],
  },
  {
    id: 'pay-03',
    category: 'payment_simulation',
    question: 'What payment methods are shown?',
    answer:
      'ORDERra may show demo methods such as card, Apple Pay placeholder, Google Pay placeholder, ACH placeholder, PayPal placeholder, and cash.',
    tags: ['payment methods', 'card', 'wallet'],
  },
  {
    id: 'pay-04',
    category: 'payment_simulation',
    question: 'Are Apple Pay and Google Pay real in this demo?',
    answer:
      'No. They are placeholders to show how wallet options could appear in a real ordering product.',
    tags: ['apple pay', 'google pay', 'placeholder'],
  },
  {
    id: 'pay-05',
    category: 'payment_simulation',
    question: 'Is ACH bank payment real?',
    answer:
      'No. ACH is only shown as a demo payment option. It does not connect to a real bank account.',
    tags: ['ach', 'bank', 'placeholder'],
  },
  {
    id: 'pay-06',
    category: 'payment_simulation',
    question: 'Is cash payment real?',
    answer:
      'Cash may appear as a demo payment option, but it does not create a real cash collection workflow.',
    tags: ['cash', 'payment', 'demo'],
  },
  {
    id: 'pay-07',
    category: 'payment_simulation',
    question: 'What is payment success simulation?',
    answer:
      'Payment success simulation shows what the app might display after a successful payment. It does not confirm any real financial transaction.',
    tags: ['success', 'simulation', 'payment status'],
  },
  {
    id: 'pay-08',
    category: 'payment_simulation',
    question: 'Can payment fail in the demo?',
    answer:
      'Yes. The demo can show failed or pending payment states to demonstrate realistic checkout handling.',
    tags: ['failed payment', 'pending', 'status'],
  },
  {
    id: 'pay-09',
    category: 'payment_simulation',
    question: 'Are receipts real?',
    answer:
      'Receipts are demo records only. They do not represent a legally issued receipt from a real restaurant or payment processor.',
    tags: ['receipt', 'record', 'demo'],
  },
  {
    id: 'pay-10',
    category: 'payment_simulation',
    question: 'Does ORDERra store card data?',
    answer: 'No. ORDERra does not store real card numbers, CVV codes, or live payment credentials.',
    tags: ['store card', 'card data', 'privacy'],
  },
  {
    id: 'pay-11',
    category: 'payment_simulation',
    question: 'What are webhook events in ORDERra?',
    answer:
      'Webhook events are demo logs that show how outside payment or delivery services might send updates. No real outside service is connected.',
    tags: ['webhook', 'demo log', 'simulation'],
  },
  {
    id: 'pay-12',
    category: 'payment_simulation',
    question: 'Can real payment be added later?',
    answer:
      'Yes, but only after proper payment testing, security review, compliance checks, and strong live-mode safety controls.',
    tags: ['real payment', 'future', 'security'],
  },
  {
    id: 'issue-01',
    category: 'refunds_issues',
    question: 'Can I cancel an order?',
    answer:
      'Cancellation can be demonstrated based on the order status. It does not cancel a real restaurant order.',
    tags: ['cancel', 'order', 'status'],
  },
  {
    id: 'issue-02',
    category: 'refunds_issues',
    question: 'Can I get a real refund?',
    answer: 'No. Refunds in ORDERra are simulated only and no real money is returned.',
    tags: ['refund', 'real money', 'simulation'],
  },
  {
    id: 'issue-03',
    category: 'refunds_issues',
    question: 'What is refund pending?',
    answer:
      'Refund pending means the demo order has entered a simulated review or refund state. It does not involve a real payment provider.',
    tags: ['refund pending', 'status', 'payment'],
  },
  {
    id: 'issue-04',
    category: 'refunds_issues',
    question: 'Can partial refunds be simulated?',
    answer:
      'Yes. ORDERra can demonstrate partial refund logic for cases such as missing items, wrong items, or compensation examples.',
    tags: ['partial refund', 'missing item', 'compensation'],
  },
  {
    id: 'issue-05',
    category: 'refunds_issues',
    question: 'What happens if an item is missing?',
    answer:
      'The demo can show a missing item support or refund scenario. No real restaurant investigation is opened.',
    tags: ['missing item', 'support', 'refund'],
  },
  {
    id: 'issue-06',
    category: 'refunds_issues',
    question: 'What happens if I receive the wrong item?',
    answer:
      'ORDERra can simulate a wrong item resolution flow, such as refund review or compensation. This is for product demonstration only.',
    tags: ['wrong item', 'issue', 'resolution'],
  },
  {
    id: 'issue-07',
    category: 'refunds_issues',
    question: 'What happens if delivery is late?',
    answer:
      'Late delivery compensation can be simulated. Since delivery is not real, no actual courier claim is created.',
    tags: ['late delivery', 'compensation', 'rider'],
  },
  {
    id: 'issue-08',
    category: 'refunds_issues',
    question: 'Can I report a payment problem?',
    answer:
      'You can view or simulate payment issue flows. No real financial dispute or provider case is created in the demo.',
    tags: ['payment problem', 'dispute', 'support'],
  },
  {
    id: 'issue-09',
    category: 'refunds_issues',
    question: 'Can store credit be issued?',
    answer:
      'Store credit may be shown as a placeholder resolution option. It has no real value in the demo.',
    tags: ['store credit', 'resolution', 'placeholder'],
  },
  {
    id: 'issue-10',
    category: 'refunds_issues',
    question: 'Why do refund rules depend on order status?',
    answer:
      'Status-based refund rules reflect realistic restaurant operations. For example, cancellation is easier before kitchen preparation begins.',
    tags: ['refund rules', 'status', 'kitchen'],
  },
  {
    id: 'issue-11',
    category: 'refunds_issues',
    question: 'Can staff approve refunds?',
    answer:
      'The admin reference area may simulate staff review. Real refund approval would require secure backend permissions and payment provider integration.',
    tags: ['staff', 'admin', 'refund approval'],
  },
  {
    id: 'issue-12',
    category: 'refunds_issues',
    question: 'Are issue reports saved permanently?',
    answer:
      'Demo issue records should be treated as temporary sample data. Do not enter sensitive information into issue forms.',
    tags: ['issue report', 'data', 'privacy'],
  },

  {
    id: 'privacy-01',
    category: 'account_privacy',
    question: 'Does ORDERra collect personal data?',
    answer:
      'ORDERra may use sample or demo-entered information to show ordering flows. It is not intended to collect sensitive personal data.',
    tags: ['personal data', 'privacy', 'demo'],
  },
  {
    id: 'privacy-02',
    category: 'account_privacy',
    question: 'Does ORDERra use browser storage?',
    answer:
      'Yes. ORDERra may use browser storage such as localStorage to remember demo cart state, UI state, and support widget messages.',
    tags: ['localStorage', 'browser storage', 'cart'],
  },
  {
    id: 'privacy-03',
    category: 'account_privacy',
    question: 'How can I clear demo data?',
    answer:
      'You can clear your browser storage or use another browser/device. This may remove locally saved cart, session, or support demo state.',
    tags: ['clear data', 'storage', 'browser'],
  },
  {
    id: 'privacy-04',
    category: 'account_privacy',
    question: 'Is my login real?',
    answer:
      'Login behavior in the demo may use sample accounts or demo sessions. It should not be treated as a real customer account system.',
    tags: ['login', 'account', 'session'],
  },
  {
    id: 'privacy-05',
    category: 'account_privacy',
    question: 'Can I use a real password?',
    answer: 'Do not use a real personal password in demo fields. Use only safe test information.',
    tags: ['password', 'security', 'demo'],
  },
  {
    id: 'privacy-06',
    category: 'account_privacy',
    question: 'Does ORDERra use analytics?',
    answer:
      'The demo may include analytics placeholders or logs for product explanation. No advertising analytics system is required for this portfolio demo.',
    tags: ['analytics', 'logs', 'placeholder'],
  },
  {
    id: 'privacy-07',
    category: 'account_privacy',
    question: 'Where can I read the Privacy Policy?',
    answer:
      'Use the Privacy Policy link in the footer to read the demo-safe data and privacy notice.',
    tags: ['privacy policy', 'footer', 'legal'],
  },
  {
    id: 'privacy-08',
    category: 'account_privacy',
    question: 'Can ORDERra be made production-compliant?',
    answer:
      'Yes, but production use requires a full privacy, security, payment, logging, data retention, and compliance review.',
    tags: ['compliance', 'production', 'security'],
  },
  {
    id: 'support-01',
    category: 'support_demo',
    question: 'What is ORDERra Support Agent?',
    answer:
      'ORDERra Support Agent is a demo helper that answers common questions about ordering, payment demo, delivery, pickup, dine-in, privacy, and support.',
    tags: ['support agent', 'assistant', 'help topics'],
  },
  {
    id: 'support-02',
    category: 'support_demo',
    question: 'Is the support agent a real AI service?',
    answer: 'No. It uses prepared demo answers only, so no outside AI service is contacted.',
    tags: ['ai', 'demo answers', 'external service'],
  },
  {
    id: 'support-03',
    category: 'support_demo',
    question: 'Can I talk to a real human agent?',
    answer:
      'The “Talk to human agent” flow is demo-safe. It can show a request card, but it does not contact a real support person.',
    tags: ['human agent', 'live chat', 'demo'],
  },
  {
    id: 'support-04',
    category: 'support_demo',
    question: 'What happens if I ask something outside ORDERra support topics?',
    answer:
      'The support agent will politely guide you back to ORDERra topics such as ordering, delivery, pickup, dine-in, payment demo, refunds, privacy, and support.',
    tags: ['unsupported question', 'help topics', 'support'],
  },
];

export const HELP_CENTER_FAQ_COUNT = helpCenterFaqs.length;
