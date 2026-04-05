# Orchestra ERP
### *A Schema-as-Data Enterprise Resource Planning System*

> **Built by Dhrobe Islam** · Laravel 13 · Filament 5 · PHP 8.4 · MySQL

---

## The Vision

Most ERP systems are rigid. You buy a system that does 80% of what you need, then spend years customizing the other 20% — paying consultants, writing migrations, rebuilding screens. When your business changes, the software doesn't.

**Orchestra is different.**

Orchestra is built on a single radical idea: **the database schema itself is data**. Instead of hardcoding tables and forms, the admin defines entities, fields, and relationships through a visual interface. The system generates the database tables, Eloquent models, and Filament UI at runtime — dynamically, without writing a single line of code.

This means:

- A **flour mill** and a **SaaS company** can run on the same codebase
- Adding a new "Shipment Tracking" module takes minutes, not months
- Relationships between modules can be toggled on or off per client
- Every entity, field, form, role, permission, and notification channel is configurable by the admin — not the developer

This is **enterprise ERP with the flexibility of a no-code platform**.

---

## Core Concept: Schema-as-Data

```
Traditional ERP                    Orchestra
────────────────                   ─────────────────────────────────
Developer writes migration    →    Admin creates Entity in UI
Developer writes model        →    DynamicModelGenerator creates it at runtime
Developer writes Filament form →   DynamicRecordResource reads fields from DB
Developer deploys             →    Admin saves Field Options → table is created
                                   Done. Live immediately.
```

The three services that make this work:

| Service | What It Does |
|---|---|
| `DynamicModelGenerator` | Creates named Eloquent model classes at runtime using `eval()` |
| `DynamicMigrationService` | Runs `Schema::table()` to add/modify/drop columns when Field Options are saved |
| `SchemaCache` | Caches entity + field definitions so the DB isn't queried on every page render |

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13.3 |
| Admin Panel | Filament 5.4 |
| Reactive UI | Livewire 4.2 |
| Language | PHP 8.4 |
| Database | MySQL (`orchestra_erp`) |
| Cache | File (Redis-ready) |
| Auth / RBAC | Filament Shield v4.2 + Spatie Permission |
| Media | Spatie Media Library v11 |
| Modules | nwidart/laravel-modules v11 |
| Dev OS | macOS / zsh |

---

## Architecture

```
orchestra/
├── app/
│   ├── Filament/
│   │   ├── Pages/
│   │   │   ├── ManageFieldOptions.php     # Visual field editor → creates DB columns
│   │   │   ├── BulkPriceUpdate.php        # Bulk price adjustment by category/brand
│   │   │   ├── ManageRoles.php            # [PLANNED] Visual role + permission matrix
│   │   │   └── ManageNotifications.php    # [PLANNED] Notification channel configurator
│   │   └── Resources/
│   │       ├── DynamicRecords/            # ONE resource that serves ALL entities
│   │       │   └── DynamicRecordResource.php
│   │       ├── Entities/                  # Schema Builder: define tables
│   │       ├── Fields/                    # Schema Builder: view field metadata
│   │       ├── Modules/                   # Schema Builder: group entities
│   │       └── Relationships/             # Schema Builder: define + toggle relations
│   ├── Models/
│   │   ├── Entity.php
│   │   ├── Field.php
│   │   ├── Module.php
│   │   ├── Relationship.php
│   │   ├── RoleConfiguration.php          # [PLANNED] Extended role config + limits
│   │   ├── RoleModuleAccess.php           # [PLANNED] Per-module action toggles
│   │   ├── RoleEntityAccess.php           # [PLANNED] Per-entity field-level control
│   │   └── NotificationChannel.php        # [PLANNED] Configured delivery channels
│   ├── Providers/
│   │   └── AppServiceProvider.php         # Dynamically builds sidebar nav from DB
│   └── Services/
│       ├── DynamicModelGenerator.php
│       ├── DynamicMigrationService.php
│       ├── SchemaCache.php
│       ├── RolePermissionService.php      # [PLANNED] Runtime permission + limit checks
│       └── NotificationDispatcher.php     # [PLANNED] Route notifications to channels
└── database/migrations/
    ├── *_create_modules_table.php
    ├── *_create_entities_table.php
    ├── *_create_fields_table.php
    ├── *_create_relationships_table.php
    ├── *_create_role_configurations_table.php   # [PLANNED]
    ├── *_create_role_module_access_table.php     # [PLANNED]
    ├── *_create_role_entity_access_table.php     # [PLANNED]
    ├── *_create_notification_channels_table.php  # [PLANNED]
    ├── *_create_notification_templates_table.php # [PLANNED]
    └── *_create_notification_rules_table.php     # [PLANNED]
```

---

## Modules Planned

| Module | Status | Key Entities |
|---|---|---|
| **Products** | ✅ Schema defined | Product, Variant, Brand, Category, Product Price |
| **Inventory** | ✅ Table exists | Inventory, Warehouse, Inventory Transaction |
| **Suppliers** | ✅ Schema defined | Supplier, Supplier Ledger |
| **Purchasing** | 🔄 In progress | Purchase Order, PO Item, GRN, GRN Item, Purchase Invoice, Purchase Return, Supplier Payment, Supplier Payment Allocation |
| **Sales** | 🔄 Schema defined | Customer, Sales Order, SO Item, Invoice, Invoice Item, Customer Payment, Payment Allocation, Customer Ledger |
| **Branches** | 📋 Planned | Branch, Petty Cash Account, Petty Cash Transaction |
| **Fleet** | 📋 Planned | Vehicle, Driver, Trip Assignment, Trip Order, Fuel Log, Transport Expense, Vehicle Document, Vehicle Rental |
| **Production** | 📋 Planned | Production Schedule |
| **Accounting** | 📋 Planned | Chart of Accounts, Journal Entry, Transaction Line, Debit Voucher |
| **Expenses** | 📋 Planned | Expense Category, Expense Subcategory, Expense Voucher |
| **HR** | 📋 Planned | Department, Employee, Attendance, Leave Request |
| **Banking** | 📋 Planned | Bank Account, Bank Transaction, Bank Transfer |
| **Cash Management** | 📋 Planned | Petty Cash Account, Cash Verification Log, EOD Summary |
| **Pricing** | 📋 Planned | Pricing Rule, Rule Condition, Rule Action |
| **Returns** | 📋 Planned | Purchase Return, Purchase Return Item |
| **Commodity** | 📋 Planned | Commodity, Shipment, Shipment Position |
| **Subscriptions** | 📋 Planned | Subscription Plan, Subscription |
| **Reports** | 📋 Planned | Report Template |

---

## The Admin Workflow

### Setting Up a New Entity (e.g. "Product")

1. **Schema Builder → Modules → Create**
   Define the department: `Products`, icon, slug.

2. **Schema Builder → Entities → Create**
   Define the table: `Product`, table name: `products`, title field: `name`.

3. **Schema Builder → Field Options**
   Select `Product`. Add fields visually:
   ```
   name        | Text    | Required
   sku         | Text    | Required
   price       | Number  | Required
   description | Textarea
   is_active   | Boolean
   ```
   Click **Save Fields** → `DynamicMigrationService` runs `ALTER TABLE` → columns appear in MySQL immediately.

4. **Schema Builder → Relationships → Create**
   ```
   Product  hasMany   Product Variant  (foreign: product_id)
   Product  belongsTo Category         (foreign: category_id)
   ```
   Each relationship has an **is_active toggle** — disable without deleting.

5. **Sidebar auto-updates**
   `AppServiceProvider` reads active modules + entities from DB and registers Filament nav items. No deploy. No code change.

6. **Navigate to Products → Create Record**
   `DynamicRecordResource` reads the entity's fields, renders the form, handles CRUD — all dynamically.

---

## The Relationship Toggle System

```
Schema Builder → Relationships

From            Type        To                  Active
────────────    ────────    ────────────────    ──────
Product         hasMany     Product Variant     ✅ ON
Product         belongsTo   Brand               ✅ ON
Sales Order     hasOne      Production Schedule ❌ OFF  ← client doesn't use production
Trip Assignment hasMany     Trip Order          ✅ ON
Vehicle         hasMany     Fuel Log            ❌ OFF  ← client doesn't track fuel
```

---

## Feature: Role & Permission Management System

### Vision

Standard role systems treat permissions as flat strings like `view_product`. This works for basic access but fails for enterprise needs where the question isn't just *can they do this?* but *how much?* and *under what conditions?*

Orchestra treats permissions the same way it treats schema — **as data, not code**. Admins configure everything through a visual matrix. No permission strings are hardcoded. Every action, every limit, every restriction is a database row that can be toggled in real time.

### Three Dimensions of Access Control

**Dimension 1 — Boolean Action Toggles (per module)**

For each module, the admin toggles each action on or off:

| Action | Description |
|---|---|
| Can View | See records listed in this module |
| Can Create | Create new records |
| Can Edit | Edit existing records |
| Can Delete | Soft-delete records |
| Can Export | Export to Excel / PDF |
| Can Import | Bulk import from file |
| Can Print | Generate printable documents |
| Can Approve | Approve pending workflows |
| Can Reject | Reject submitted records |
| Can Bulk Action | Perform operations on multiple records at once |

**Dimension 2 — Numeric Limits (negotiated thresholds)**

Some permissions aren't boolean — they carry a number the admin sets per role:

| Limit | Example Use |
|---|---|
| `approval_limit` | Sales Manager can approve orders up to $10,000 |
| `discount_limit_pct` | Sales Rep can give max 5% discount |
| `max_order_value` | Field agent cannot create orders above $5,000 |
| `max_discount_amount` | Cannot discount more than $500 per order |
| `daily_create_limit` | Warehouse staff can only create 20 GRNs per day |
| `daily_delete_limit` | Manager can delete max 5 records per day |
| `credit_limit` | How much credit this role can extend to customers |
| `max_items_per_order` | Cap line items on a single order |
| `approval_escalation_hours` | Auto-escalate if not approved within N hours |

**Dimension 3 — Scope Restrictions**

| Restriction | Meaning |
|---|---|
| Own Records Only | User sees/edits only records they created |
| Branch Records Only | User sees/edits only records from their assigned branch |
| Requires Approval | All actions from this role need a higher role to approve |
| Requires Second Approval | Critical actions need two independent approvers |
| Hidden Fields | Specific fields (e.g. `cost_price`) invisible to this role |
| Readonly Fields | Fields this role can see but not change |

### Example Role Configurations

```
Role: CEO
────────────────────────────────────────────────
All modules:         FULL ACCESS
Approval Limit:      Unlimited
Discount Limit:      Unlimited
Scope:               All branches, all records
Requires Approval:   NO

Role: Regional Sales Manager
────────────────────────────────────────────────
Sales module:        View, Create, Edit, Approve, Export
Purchasing module:   View only
Accounting module:   NO ACCESS
Approval Limit:      $25,000
Discount Limit:      10%
Scope:               Branch records only (their region)
Requires Approval:   YES — for orders above $25,000

Role: Sales Representative
────────────────────────────────────────────────
Sales module:        View (own), Create (own)
Can Edit:            Own records within 24 hours of creation only
Can Delete:          NO
Can Approve:         NO
Max Order Value:     $5,000
Discount Limit:      3%
Daily Create Limit:  10 orders/day
Hidden Fields:       cost_price, margin, supplier_cost
Scope:               Own records only
Requires Approval:   YES — all orders

Role: Warehouse Staff
────────────────────────────────────────────────
Inventory module:    View, Create, Edit
Purchasing module:   View GRN, Create GRN only
Can Delete:          NO
Can Export:          NO
Can Approve:         NO
Daily Create Limit:  30 GRNs/day
Scope:               Their warehouse only

Role: Accountant
────────────────────────────────────────────────
Accounting module:   Full Access
Banking module:      View, Create, Edit
Sales module:        View invoices only
Can Delete:          NO
Can Approve:         Journal Entries (up to $100,000)
Hidden Fields:       Internal notes on customer records
```

### Entity-Level Overrides

Beyond module-level settings, the admin can drill into specific entities to:
- Hide specific fields from a role (e.g. Sales Rep cannot see `cost_price` on any product)
- Set fields as readonly (e.g. Warehouse can see price but not change it)
- Override approval limit specifically for this entity
- Override daily limits specifically for this entity

### Integration with Schema Builder

Because modules and entities are database records, the role matrix is always in sync. When a new module is created, it automatically appears in the role permission matrix with all toggles defaulting to **OFF** — forcing the admin to explicitly grant access rather than accidentally opening it up.

```
Admin creates "Commodity" module in Schema Builder
       ↓
New row per role in role_module_access — all toggles = false
       ↓
ManageRoles page shows Commodity column for each role
       ↓
Admin configures which roles get access and sets limits
       ↓
Enforced immediately. No deploy.
```

### Planned Implementation Tasks

```
Phase 1 — Database Layer
  [ ] Migration: role_configurations
        role_id, is_active, description, color, max_users, dashboard_widgets JSON
  [ ] Migration: role_module_access
        role_id, module_id
        action toggles: can_view, can_create, can_edit, can_delete, can_export,
                        can_import, can_print, can_approve, can_reject, can_bulk_action
        numeric limits: approval_limit, discount_limit_pct, max_order_value,
                        daily_create_limit, daily_delete_limit, credit_limit,
                        max_items_per_order, approval_escalation_hours
        scope flags:    own_records_only, branch_records_only,
                        requires_approval, requires_second_approval
  [ ] Migration: role_entity_access
        role_id, entity_id
        same action toggles + hidden_fields JSON + readonly_fields JSON
        entity-level limit overrides
  [ ] Model: RoleConfiguration
  [ ] Model: RoleModuleAccess
  [ ] Model: RoleEntityAccess

Phase 2 — Service Layer
  [ ] RolePermissionService::canDo(user, action, module) → bool
  [ ] RolePermissionService::getLimit(user, limitType, module) → float|null
  [ ] RolePermissionService::isWithinLimit(user, limitType, value, module) → bool
  [ ] RolePermissionService::getHiddenFields(user, entity) → array
  [ ] RolePermissionService::getReadonlyFields(user, entity) → array
  [ ] Cache role config per user session (invalidate on role change)

Phase 3 — Filament UI (ManageRoles page)
  [ ] Left panel: role list with color badge, active toggle, user count
  [ ] Create / edit / clone / deactivate role
  [ ] Right panel: tabbed by module
  [ ] Each module tab: action toggle matrix + numeric limit inputs
  [ ] Entity overrides: collapsible sub-section per entity
  [ ] Field visibility: multi-select for hidden + readonly fields
  [ ] Role comparison view: two roles side by side

Phase 4 — Runtime Enforcement
  [ ] Middleware: block nav access to modules the role cannot view
  [ ] DynamicRecordResource: hide form fields per role hidden_fields
  [ ] DynamicRecordResource: make fields readonly per role readonly_fields
  [ ] DynamicRecordResource: show/hide action buttons per role toggles
  [ ] Intercept saves when requires_approval = true → route to approval queue
  [ ] Reject creates/edits when daily limits exceeded
  [ ] Reject values exceeding numeric thresholds (max_order_value, discount_limit)

Phase 5 — Audit & Reporting
  [ ] Log every permission denial (who, what, when, why)
  [ ] Role activity report: create/edit/approve counts per role per day
  [ ] Permission change audit: who changed which role config and when
  [ ] Limit breach attempts report: how often users hit their limits
```

---

## Feature: Notification Management System

### Vision

Every business needs to know when something important happens. But every business uses different channels. A Bangladesh FMCG company relies on WhatsApp. A European SaaS uses email. A local retail chain uses SMS. A tech-forward business uses Telegram bots.

Orchestra's notification system lets the admin **visually configure which channels exist, how they're connected, which events trigger notifications, who receives them, and from which module** — all without touching code.

The notification system has four layers:
1. **Channels** — how messages are delivered (Email, SMS, WhatsApp, Telegram, Webhook)
2. **Templates** — what each notification says, with `{variable}` substitution
3. **Rules** — which events trigger which template on which channel
4. **Recipients** — who gets notified (by role, by user, or from the record itself)

### Supported Channels

| Channel | Provider Options | Best For |
|---|---|---|
| **Email** | SMTP, Mailgun, SES, Postmark, Resend | Invoices, reports, formal alerts |
| **SMS** | Twilio, Vonage, BulkSMS, local BD gateways | Delivery alerts, OTPs, urgent notices |
| **WhatsApp** | WATI, Meta Business API, Twilio WhatsApp | Customer order updates, payment receipts |
| **Telegram** | Telegram Bot API | Internal team alerts, approval requests |
| **In-App** | Filament Notifications (built-in) | Admin panel alerts, action-required notices |
| **Webhook** | Any HTTP endpoint | Slack, Discord, custom integrations |
| **Push** | FCM / APNS | Mobile app (future) |

### What the Admin Configures

**Step 1 — Channel Setup**
```
Settings → Notifications → Channels → Add Channel

Channel: WhatsApp via WATI
  API Endpoint:  https://live-mt-server.wati.io/api/
  API Token:     ••••••••••••••••
  From Number:   +880XXXXXXXXXX
  Status:        Active ✅
  [Send Test Message]
```

**Step 2 — Subscribable Events per Module**
```
Purchasing:
  Purchase Order Created / Approved / Sent to Supplier
  GRN Created / Rejected
  Supplier Payment Made
  Invoice Overdue

Sales:
  New Order Created / Approved / Rejected
  Order Shipped / Delivered
  Payment Received / Overdue
  Customer Credit Limit Approaching (80%)
  Customer Credit Limit Exceeded

Inventory:
  Stock Below Reorder Point
  Stock Out
  Batch Expiring (30 days)

HR:
  Leave Request Submitted / Approved / Rejected
  Attendance Anomaly

System:
  New User Created
  Login from New Device
  Role Changed
  Permission Denied (audit)
  Daily EOD Summary
```

**Step 3 — Notification Rules**
The admin maps Event → Channel → Template → Recipient:

```
Event: Order Shipped
  ├── WhatsApp → Customer (from order.customer.phone)
  │     "Dear {customer_name}, your order {order_number} has been
  │      shipped. Expected delivery: {delivery_date}."
  │     Active: ✅
  │
  ├── Telegram → Role: Logistics Manager, Accounts
  │     "🚚 Order {order_number} shipped by {shipped_by}.
  │      Customer: {customer_name}. Value: {order_total}."
  │     Active: ✅
  │
  └── Email → Customer (from order.customer.email)
        CC: Role: Sales Manager
        Active: ❌ (customer prefers WhatsApp)

Event: Payment Overdue > 7 days
  ├── WhatsApp → Customer
  │     "Hi {customer_name}, invoice {invoice_number} for {amount}
  │      was due on {due_date}. Please arrange payment."
  │     Frequency: Daily until paid
  │     Active: ✅
  │
  └── In-App → Role: Accounts, Sales Manager
        "⚠️ {customer_name} is {days_overdue} days overdue for {amount}."
        Active: ✅
```

**Step 4 — Recipient Options**
```
Who receives this notification?

○ Specific Users       → pick from user list
○ Role-based           → all users with selected role(s)
○ Record Owner         → the user who created the triggering record
○ Assigned User        → user assigned to the record (e.g. sales rep)
○ Record Relationship  → a phone/email field FROM the record itself
                         (e.g. Customer's phone for order events)
○ Branch Manager       → manager of the branch the record belongs to
○ Webhook URL          → POST payload to external system
```

**Step 5 — Frequency & Conditions**
- Send once vs. repeat (e.g. daily reminder until resolved)
- Condition filters (e.g. only notify if `order.total > $1,000`)
- Quiet hours (don't send WhatsApp between 10pm–8am)
- Batch digest (group 10 low-stock alerts into one message)
- Escalation (if not acknowledged in N hours, notify next role up)

### Template Editor Features
- Subject line (email only)
- Message body with `{variable}` placeholders that auto-complete from the event's available fields
- Rich text / HTML for email (with logo and branding)
- Plain text with character counter for SMS/WhatsApp
- Preview with dummy data before saving
- Send test button (fires to admin's own contact)

### Integration with Schema Builder

Because modules are dynamic, the event registry is also dynamic. When the admin creates a new entity, default events are auto-generated:

```
Admin creates Entity "Shipment" in Module "Commodity"
       ↓
System auto-generates events:
  commodity.shipment.created
  commodity.shipment.updated
  commodity.shipment.status_changed
  commodity.shipment.deleted
       ↓
These events appear in Notification Rules configurator immediately
       ↓
Admin sets up alerts for shipment events with no code written
```

### Real-World Example: Bangladesh Distributor

```
Channels active: WhatsApp (WATI), SMS (BulkSMS), Telegram, In-App

Rule 1: New credit order created
  → Telegram: Sales Director + Branch Manager

Rule 2: Order approved
  → WhatsApp: Customer "Your order is confirmed"

Rule 3: Order shipped
  → WhatsApp: Customer with delivery ETA
  → SMS: Customer (fallback if WhatsApp undelivered)
  → Telegram: Accounts team with order value

Rule 4: Payment received
  → WhatsApp: Receipt to customer
  → Telegram: Accounts + Sales Manager

Rule 5: Stock below reorder point
  → Telegram: Purchasing Manager (immediate)
  → In-App: Dashboard alert

Rule 6: EOD Summary (daily 11:59pm)
  → Telegram: Directors
    "Branch A: Sales 12 | Collections ৳450,000 | Expenses ৳12,000"
```

### Planned Implementation Tasks

```
Phase 1 — Database Layer
  [ ] Migration: notification_channels
        type (email|sms|whatsapp|telegram|inapp|webhook)
        name, config JSON (credentials, endpoints), is_active, last_tested_at
  [ ] Migration: notification_templates
        channel_id, event_key, subject, body, variables JSON, is_active
  [ ] Migration: notification_rules
        event_key, module_id, channel_id, template_id
        recipient_type, recipient_config JSON
        conditions JSON, frequency, quiet_hours JSON, is_active
  [ ] Migration: notification_logs
        rule_id, recipient, channel, payload JSON, status, sent_at, error_message
  [ ] Models: NotificationChannel, NotificationTemplate, NotificationRule, NotificationLog

Phase 2 — Channel Drivers
  [ ] Interface: NotificationChannelDriver (send, test, getStatus)
  [ ] EmailDriver (Laravel Mail — SMTP / Mailgun / SES / Resend)
  [ ] SmsDriver (configurable gateway — Twilio / BulkSMS / local)
  [ ] WhatsAppDriver (WATI / Meta Business API / Twilio)
  [ ] TelegramDriver (Telegram Bot API)
  [ ] InAppDriver (Filament Notifications)
  [ ] WebhookDriver (HTTP POST with configurable payload)
  [ ] DriverFactory: resolves correct driver from channel type + config

Phase 3 — Event System
  [ ] EventRegistry: list of all subscribable events, auto-built from active modules
  [ ] Each event carries a typed payload (the triggering record + metadata)
  [ ] New entity creation auto-generates default CRUD events
  [ ] NotificationDispatcher: listens on Laravel events, evaluates rules,
      resolves recipients, renders template variables, dispatches via driver
  [ ] Queue-based delivery (never block user-facing requests)

Phase 4 — Filament UI
  [ ] Settings → Notifications → Channels
        Card per channel type; configure credentials; test connection; toggle
  [ ] Settings → Notifications → Templates
        Select event + channel; write body with variable autocomplete; preview; test send
  [ ] Settings → Notifications → Rules
        Matrix: Event rows × Channel columns
        Toggle = rule active; click = configure recipients + conditions + frequency
  [ ] Settings → Notifications → Logs
        Every notification sent: when, to whom, channel, status, error
        Retry failed; filter by module/channel/date

Phase 5 — Advanced Features
  [ ] Quiet hours enforcement per channel per rule
  [ ] Batch digest: collect N alerts → send as one grouped message
  [ ] Escalation chains: if not acknowledged → escalate to next role
  [ ] User notification preferences: opt-out of specific event types
  [ ] Two-way Telegram: bot accepts /approve and /details commands
  [ ] WhatsApp template pre-registration flow (Meta approval requirement)
  [ ] SMS fallback: auto-retry via SMS if WhatsApp delivery fails
  [ ] Notification analytics: delivery rate, open rate, response time
```

---

## Real-World Example: TechMart Electronics

### Day 1 — Setup
```
Create Modules: Products, Inventory, Suppliers, Sales, Purchasing
Create Entities + Fields → DB tables auto-created
Define Relationships → Sidebar populates automatically
Configure Roles: CEO, Sales Manager, Sales Rep, Warehouse Staff, Accountant
Set up Channels: WhatsApp (WATI), Telegram, Email (Mailgun)
```

### Day 3 — First Purchase Order
```
Purchasing → Purchase Orders → Create
  Items: iPhone 16 Pro 128GB × 100 @ $720 = $72,000
  Status: Draft → Sent

[Telegram fires to Purchasing Director]
"📦 PO-00001 sent to Apple Distributor for $72,000 by [user]"

[Stock arrives → Status: Received]
→ Inventory updated, GRN created, ledger entry recorded
[WhatsApp fires to Supplier]
"Payment of $72,000 processed. Reference: SP-00001"
```

---

## Inspired By

The schema for Orchestra was informed by a real-world Bangladesh flour mill distribution ERP with:
- Multi-branch operations with branch-specific pricing
- Credit order workflow with 11 status stages
- Customer credit limits with payment allocation system
- Weight-based product variants (grade + bag size + UOM)
- Fleet management with trip consolidation AI
- EOD cash verification per branch
- Dynamic pricing rules engine
- Bank account reconciliation
- Petty cash management per branch

All of these patterns are expressible through Orchestra's Schema Builder — making Orchestra capable of running a flour mill, a retail chain, a SaaS business, an electronics distributor, or a construction firm without changing a line of application code.

---

## Key Technical Decisions

**Why eval() for model creation?**
Eloquent requires models to be instantiatable with zero arguments for event dispatching. Anonymous PHP classes cannot satisfy this. Named classes via `eval()` behave identically to handwritten models.

**Why no statePath('data') in Field Options?**
Filament 5's statePath puts all state under `$this->data[...]`. Since `visible()` closures and `save()` both need to read `selectedEntityId` directly, keeping state on public properties is cleaner and more reliable.

**Why Cache::remember() not Cache::tags()?**
`Cache::tags()` requires Redis. Simple keyed cache works with any driver and upgrades to tagged Redis automatically when the driver changes.

**Why is_active on Relationships?**
Hard-deleting removes institutional knowledge. Soft-disabling preserves the relationship definition while letting the admin control what's active per client deployment.

**Why database-driven permissions not just Spatie strings?**
Spatie strings (`view_product`) are boolean. A Sales Manager approving orders up to $10,000 and a CEO with unlimited approval are both "can_approve" — that single boolean hides a critical business rule. Numeric limits in `role_module_access` make thresholds queryable, auditable, and configurable without code changes.

**Why a driver pattern for notifications?**
Different clients use different channels. Hardcoding email sends couples business logic to one delivery method. A driver pattern means the dispatcher calls `$driver->send($recipient, $message)` regardless of channel. Adding a new gateway means adding one driver class — not rewriting notification logic.

---

## Current State (April 2026)

### Completed ✅
- Laravel 13 + Filament 5 + Livewire 4 bootstrapped
- Filament Shield v4.2 + Spatie Permission installed
- nwidart/laravel-modules v11 with wikimedia/composer-merge-plugin
- Core meta-tables: `modules`, `entities`, `fields`, `relationships`
- `DynamicModelGenerator`, `DynamicMigrationService`, `SchemaCache`
- `ManageFieldOptions` — visual field editor that creates DB columns on save
- `DynamicRecordResource` — one resource serving all entities dynamically
- `AppServiceProvider` — auto-builds sidebar nav from DB
- Schema Builder: Modules, Entities, Fields, Relationships (with is_active toggle)
- 4 Modules, 7 Entities, 31+ Fields, 6 Relationships in DB
- Dynamic tables created: brands, categories, products, product_variants, suppliers, warehouses
- `BulkPriceUpdate` standalone page

### In Progress 🔄
- Purchasing module field entry (GRN, Purchase Invoice, Supplier Payment)
- Sales module field entry (Customer Payment, Allocation, Ledger)
- Remaining module definitions (Banking, Fleet, Branches, Accounting)

### Planned 📋
- Role & Permission Management System (full plan above)
- Notification Management System (full plan above)
- InventoryService (reserve, release, transfer, immutable ledger)
- Customer credit limit enforcement + payment allocation engine
- Fleet trip planning + driver assignment
- EOD reconciliation workflow
- Pricing rules engine
- REST API (Laravel Sanctum)
- Customer-facing portal (Livewire, separate auth guard)
- Configurable dashboard KPI widgets per role
- Report engine with Excel/PDF export

---

## Running Locally

```bash
git clone <repo-url> orchestra && cd orchestra
composer install && npm install
cp .env.example .env && php artisan key:generate
# configure DB in .env
php artisan migrate
php artisan make:filament-user
php artisan serve
# in another terminal:
npm run dev
```

Visit `http://127.0.0.1:8000/admin`

---

## Verified Package Versions (April 2026)

| Package | Version |
|---|---|
| laravel/laravel | ^13.0 |
| filament/filament | ^5.0 (v5.4.x) |
| livewire/livewire | ^4.2 |
| bezhansalleh/filament-shield | ^4.2 |
| nwidart/laravel-modules | ^11.1 |
| spatie/laravel-permission | ^6.10 |
| spatie/laravel-medialibrary | ^11.21 |
| spatie/laravel-activitylog | ^5.0 |
| predis/predis | ^2.3 |

> ⚠️ `filament-shield` v5 does not exist — always use `^4.2`
> ⚠️ `activitylog` v5 has no `Traits/` folder — do not use `LogsActivity` directly
> ⚠️ Filament 5: navigation group icon + item icons cannot coexist
> ⚠️ Filament 5: `$view` on Pages is non-static
> ⚠️ Filament 5: use `Filament\Schemas\Schema` and `Filament\Schemas\Components\Grid`

---

## The Bigger Picture

Three pillars. One system. Zero hardcoding.

```
SCHEMA AS DATA          PERMISSIONS AS DATA         NOTIFICATIONS AS DATA
──────────────          ───────────────────         ─────────────────────
Entities → Tables       Roles → Module Access        Events → Channels
Fields → Columns        Limits → Numeric Rules       Templates → Messages
Relations → Toggles     Scopes → Restrictions        Rules → Recipients
Dynamic Forms           Runtime Enforcement           Driver Pattern
Dynamic Nav             Audit Trail                  Delivery Logs
```

The goal is a system where a non-technical business owner can:
- Add a new module on Monday morning → visible in the sidebar by afternoon
- Configure which roles access it by Tuesday
- Set up notification rules for it by Wednesday

No developer involved at any point. That is the Orchestra vision: **an ERP that conducts itself**.

---

## Author

**Dhrobe Islam**
Building Orchestra as a universal ERP platform for businesses of any type and size.

*Stack: Laravel · Filament · Livewire · PHP · MySQL · macOS*

---

> *"Most software forces your business to fit the software. Orchestra fits the software to your business."*