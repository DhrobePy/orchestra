# Orchestra ERP
### *A Schema-as-Data Enterprise Resource Planning System*

> **Built by Dhrobe Islam** · Laravel 13 · Filament 5 · PHP 8.4 · MySQL · Livewire 4 · Alpine.js

---

## Table of Contents

- [The Vision](#the-vision)
- [Core Concept: Schema-as-Data](#core-concept-schema-as-data)
- [Tech Stack](#tech-stack)
- [Installation Guide](#installation-guide)
- [System Architecture](#system-architecture)
- [Modules Overview](#modules-overview)
  - [Sales Module](#sales-module)
  - [Purchasing Module](#purchasing-module)
  - [Products Module](#products-module)
  - [Dynamic Module System](#dynamic-module-system)
  - [Reports Module](#reports-module)
  - [Settings & Configuration](#settings--configuration)
- [Dynamic Module Builder — Visual Guide](#dynamic-module-builder--visual-guide)
  - [Step 1: Create a Module](#step-1-create-a-module)
  - [Step 2: Define Entities](#step-2-define-entities)
  - [Step 3: Define Fields](#step-3-define-fields)
  - [Step 4: Use the Module](#step-4-use-the-module)
  - [Field Types Reference](#field-types-reference)
  - [Smart Foreign Key Resolution](#smart-foreign-key-resolution)
  - [Special Entity Behaviours](#special-entity-behaviours)
- [Schema Builder Reference](#schema-builder-reference)
- [RBAC System](#rbac-system)
- [Print & Document System](#print--document-system)
- [Backup System](#backup-system)
- [Notification System](#notification-system)
- [Bulk Price Update](#bulk-price-update)
- [Custom Report Builder](#custom-report-builder)
- [File Structure](#file-structure)
- [Changelog](#changelog)

---

## The Vision

Most ERP systems are rigid. You buy a system that does 80% of what you need, then spend years customizing the other 20% — paying consultants, writing migrations, rebuilding screens. When your business changes, the software doesn't.

**Orchestra is different.**

Orchestra is built on a single radical idea: **the database schema itself is data**. Instead of hardcoding tables and forms, the admin defines entities, fields, and relationships through a visual interface. The system generates the database tables, Eloquent models, and Filament UI at runtime — dynamically, without writing a single line of code.

This means:

- A **flour mill** and a **SaaS company** can run on the same codebase
- Adding a new "Shipment Tracking" module takes minutes, not months
- Relationships between modules can be toggled on or off per client
- Every entity, field, form, role, permission, and notification is configurable — not hardcoded

This is **enterprise ERP with the flexibility of a no-code platform**.

---

## Core Concept: Schema-as-Data

```
Traditional ERP                    Orchestra
────────────────────────────────   ─────────────────────────────────────────
Developer writes migration     →   Admin creates Entity in UI
Developer writes Eloquent model→   DynamicModelGenerator creates it at runtime
Developer writes Filament form →   DynamicRecordResource reads fields from DB
Developer deploys to server    →   Admin saves → table is live immediately
```

The three services that make this work:

| Service | What It Does |
|---|---|
| `DynamicModelGenerator` | Creates named Eloquent model classes at runtime using `eval()` |
| `DynamicMigrationService` | Runs `Schema::table()` to add/modify/drop columns when Field definitions are saved |
| `DynamicRecordResource` | A single Filament resource that renders any dynamic table based on URL context |

---

## Tech Stack

| Layer | Technology | Version |
|---|---|---|
| Framework | Laravel | 13.x |
| Admin UI | Filament | 5.x |
| Reactive components | Livewire | 4.x |
| Frontend JS | Alpine.js | 3.x |
| CSS | Tailwind CSS | 4.x (Vite) |
| Database | MySQL | 8.x |
| Language | PHP | 8.4 |
| Permissions | Spatie Laravel Permission | — |
| Authorization | Filament Shield | — |
| Excel Export | Maatwebsite Excel | — |
| Cloud Storage | Google Drive API | — |

---

## Installation Guide

### Requirements

- PHP 8.4+
- MySQL 8.0+
- Composer 2.x
- Node.js 20+ and npm
- A Google Drive service account JSON (optional, for backups)

---

### 1. Clone the Repository

```bash
git clone https://github.com/your-org/orchestra.git
cd orchestra
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node Dependencies & Build Assets

```bash
npm install
npm run build
```

### 4. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure:

```dotenv
APP_NAME="Orchestra ERP"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=orchestra_erp
DB_USERNAME=root
DB_PASSWORD=

# Mail (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS=noreply@yourcompany.com

# Queue (use 'database' for simple setups, 'redis' for production)
QUEUE_CONNECTION=database

# Google Drive Backup (optional)
GOOGLE_DRIVE_CLIENT_ID=
GOOGLE_DRIVE_CLIENT_SECRET=
GOOGLE_DRIVE_REFRESH_TOKEN=
GOOGLE_DRIVE_FOLDER_ID=
```

### 5. Database Setup

```bash
# Create the MySQL database first, then run:
php artisan migrate
php artisan db:seed
```

### 6. Create a Super Admin User

```bash
php artisan shield:super-admin
# Follow the prompts
```

Or via Tinker:

```bash
php artisan tinker
>>> \App\Models\User::create([
...   'name' => 'Admin',
...   'email' => 'admin@admin.com',
...   'password' => bcrypt('password')
... ])->assignRole('super_admin');
```

### 7. Generate Shield Permissions

```bash
php artisan shield:generate --all
```

This creates all Filament Shield permissions for every Resource and Page in the system.

### 8. Start the Development Server

```bash
# Laravel dev server
php artisan serve

# In a separate terminal — Vite hot reload
npm run dev
```

Visit: `http://localhost:8000/admin`

### 9. Queue Worker (for Backups & Notifications)

```bash
php artisan queue:work
```

### 10. Scheduler (for Automated Backups)

Add to your server crontab:

```
* * * * * cd /path/to/orchestra && php artisan schedule:run >> /dev/null 2>&1
```

---

## System Architecture

```
┌──────────────────────────────────────────────────────────────────┐
│                       Filament 5 Admin Panel                      │
│                                                                    │
│  ┌───────────────┐  ┌────────────────┐  ┌─────────────────────┐  │
│  │ Static         │  │ Dynamic         │  │ Schema Builder       │  │
│  │ Resources      │  │ Records         │  │ Resources            │  │
│  │ (Sales,        │  │ (any table      │  │ (Module, Entity,     │  │
│  │  Purchasing,   │  │  defined in DB) │  │  Field, Relationship)│  │
│  │  Products)     │  │                 │  │                      │  │
│  └──────┬─────────┘  └──────┬──────────┘  └──────────┬──────────┘  │
└─────────┼───────────────────┼────────────────────────┼─────────────┘
          │                   │                         │
          ▼                   ▼                         ▼
 ┌────────────────┐  ┌──────────────────────┐  ┌───────────────────┐
 │ Static Models  │  │ DynamicRecordResource │  │ DynamicMigration  │
 │ (Eloquent ORM) │  │  getModel() → eval() │  │ Service           │
 │                │  │  makeField()          │  │ Schema::table()   │
 │                │  │  makeTableColumn()    │  │                   │
 │                │  │  Smart FK mapping     │  │ DynamicModel      │
 │                │  │                       │  │ Generator         │
 └────────┬───────┘  └──────────┬────────────┘  └────────┬──────────┘
          │                     │                         │
          └─────────────────────┴─────────────────────────┘
                                        │
                                        ▼
                               ┌─────────────────┐
                               │     MySQL        │
                               │ Static tables +  │
                               │ Dynamic tables   │
                               │ (created on field│
                               │  save — no CLI)  │
                               └─────────────────┘
```

**Core Services:**

| Service | Responsibility |
|---|---|
| `RolePermissionService` | 3-dimensional RBAC: boolean actions + numeric limits + field-level visibility |
| `CreditOrderWorkflowService` | Order state machine: 11 states, approval/escalation paths |
| `ReportQueryBuilderService` | Dynamic SQL builder for custom reports with Excel export |
| `CustomerPaymentService` | Payment allocation and customer ledger management |
| `ProcurementService` | Purchase order lifecycle and GRN receipt matching |
| `NotificationDispatcher` | Event-to-channel routing for all domain events |
| `GoogleDriveService` | Backup upload, file retention, and auto-pruning |
| `DatabaseMaintenanceService` | SQL statement parsing, classification, and safe execution |
| `SchemaCache` | 5-minute TTL cache for entity/field schema reads |
| `DashboardWidgetService` | Role-aware widget visibility configuration |

---

## Modules Overview

### Sales Module

> Navigation group: **Sales**

| Resource | Purpose | Key Features |
|---|---|---|
| **Customers** | Customer master data | Credit limit, balance tracking, ledger entries, photo |
| **Credit Orders** | Order management | 11-state workflow, approval/escalation, priority levels |
| **Customer Payments** | Payment collection | Multi-method, bank account selection, ledger posting |

**Credit Order Workflow:**

```
          ┌─────────┐
          │  draft  │
          └────┬────┘
               │ submit
               ▼
     ┌──────────────────┐
     │ pending_approval │
     └────────┬─────────┘
              │                     ┌────────────┐
              │ escalate            │  escalated │
              ├─────────────────────►            │
              │                     └─────┬──────┘
              │ approve                   │ approve
              ▼                           ▼
          ┌──────────┐          ┌──────────────────┐
          │ approved │◄─────────│                  │
          └────┬─────┘          └──────────────────┘
               │
               ▼
        ┌──────────────┐    ┌────────────────┐    ┌─────────┐    ┌───────────┐
        │ in_production│───►│ ready_to_ship  │───►│ shipped │───►│ delivered │
        └──────────────┘    └────────────────┘    └─────────┘    └───────────┘
               │                    │                  │               │
               └────────────────────┴──────────────────┴───────────────┘
                                         │ cancel (from any state)
                                         ▼
                                    ┌───────────┐
                                    │ cancelled │
                                    └───────────┘
```

**Print Documents:**
- Credit order invoice (A4, company-branded, customizable template)
- Customer account statement (date-range filtered ledger)
- Payment receipt

---

### Purchasing Module

> Navigation group: **Purchasing**

| Resource | Purpose | Key Features |
|---|---|---|
| **Suppliers** | Supplier master data | Payment terms, credit limit, supplier ledger |
| **Purchase Orders** | PO management | Approval workflow, payment basis, credit days |
| **Goods Received Notes** | Inventory receipt | Qty received/accepted/rejected, weight variance |
| **Purchase Payments** | Supplier payments | Allocation against purchase orders |

**Weight Variance Tracking on GRNs:**

GRNs track three quantities: `ordered_quantity`, `received_quantity`, and `accepted_quantity`. Discrepancies are classified (shortage, excess, damage) and stored with remarks for audit.

---

### Products Module

> Navigation group: **Products**

| Resource | Purpose | Key Features |
|---|---|---|
| **Products** | Product master | Name, SKU, unit, category, description, image |
| **Product Variants** | SKU variants per product | Weight (kg), grade, branch, active price, stock, price history |
| **Bulk Price Update** | Mass pricing tool | Manual and formula-based pricing modes |

**Price History:** Every price change via Bulk Price Update is logged to `product_prices` with effective date and variant/branch context.

---

### Dynamic Module System

This is the architectural core of Orchestra. Any new ERP module — with its own database tables, list views, and create/edit forms — is built entirely through the admin UI with zero developer intervention.

**Built-in dynamic modules (pre-configured):**

| Module | Entities | Notes |
|---|---|---|
| **Expenses** | Expense Categories, Expense Subcategories, Bank Accounts, Expense Vouchers | Auto voucher numbers, payment method conditional display, print action |

See the [Dynamic Module Builder — Visual Guide](#dynamic-module-builder--visual-guide) for step-by-step instructions.

---

### Reports Module

> Navigation group: **Reports**

| Feature | Description |
|---|---|
| **Report Builder** | Define reports: data source, columns, filters, sort, group |
| **Run Report** | Execute any saved report with interactive filter form and pagination |
| **Excel Export** | One-click `.xlsx` download of full result set |

**Supported Data Sources:**

| Source | Joins Available |
|---|---|
| Credit Orders | Customers |
| Customers | — |
| Customer Payments | Customers |
| Suppliers | — |
| Purchase Orders | Suppliers |

---

### Settings & Configuration

| Page | Purpose |
|---|---|
| **Company Settings** | Name, tagline, address, logo, tax ID, website |
| **Landing Page Settings** | Public landing page URL and branding customization |
| **Dashboard Settings** | Enable/disable widgets per role |
| **Invoice Templates** | PDF layout, colors, branding, watermark (20+ options) |
| **Backup Settings** | Schedule, Google Drive credentials, retention policy |
| **Database Maintenance** | Safe SQL execution with statement preview and classification |
| **User Management** | Admin users and role assignment |
| **Roles (Shield)** | Role/permission management via Filament Shield |

---

## Dynamic Module Builder — Visual Guide

The schema builder lives under the **Schema Builder** navigation group. It has four resources: **Modules**, **Entities**, **Fields**, and **Relationships**.

---

### Step 1: Create a Module

> **Schema Builder → Modules → New Module**

A Module becomes a navigation group in the admin sidebar and a logical container for related entities.

```
┌──────────────────────────────────────┐
│  New Module                          │
├──────────────────────────────────────┤
│  Name       [ Inventory            ] │
│  Slug       [ inventory            ] │  ← auto-generated, URL-safe
│  Icon       [ heroicon-o-archive   ] │  ← any Heroicons name
│  Is Active  [ ✓ ]                    │
└──────────────────────────────────────┘
                   ↓ Save
  Sidebar now shows "Inventory" group
```

> The `icon` field accepts any Heroicons v2 name (e.g. `heroicon-o-truck`, `heroicon-o-cube`, `heroicon-o-banknotes`). Browse available icons at [heroicons.com](https://heroicons.com).

---

### Step 2: Define Entities

> **Schema Builder → Entities → New Entity**

An Entity becomes a database table and a menu item inside its parent module. It gets a full list view and create/edit form.

```
┌──────────────────────────────────────────┐
│  New Entity                              │
├──────────────────────────────────────────┤
│  Module      [ Inventory           ▾ ]   │
│  Name        [ Warehouse Items       ]   │
│  Slug/Table  [ warehouse_items       ]   │  ← becomes the DB table name
│  Title Field [ item_name             ]   │  ← used in dropdowns and breadcrumbs
│  Is Active   [ ✓ ]                       │
└──────────────────────────────────────────┘
                     ↓ Save
   DB table "warehouse_items" is created.
   "Warehouse Items" appears in the Inventory nav group.
```

> **Title Field** should match the `name` of the field you want used as the record label in foreign key dropdowns and breadcrumb titles. Set this to the field that most uniquely identifies a record (e.g. `item_name`, `voucher_number`, `invoice_ref`).

---

### Step 3: Define Fields

> **Schema Builder → Fields → New Field**

Each Field adds one column to the entity's database table and one input to its create/edit form.

```
┌────────────────────────────────────────────────────────┐
│  New Field                                             │
├────────────────────────────────────────────────────────┤
│  Entity         [ Warehouse Items         ▾ ]          │
│  Field Name     [ item_name                 ]  DB col  │
│  Label          [ Item Name                 ]  UI text │
│  Type           [ text                    ▾ ]          │
│  Is Required    [ ✓ ]                                  │
│  Show in Table  [ ✓ ]       Editable  [ ✓ ]           │
│  Sort Order     [ 1  ]                                 │
│  Options        [                           ]  select  │
│  Validation     [ required|max:255          ]          │
└────────────────────────────────────────────────────────┘
                      ↓ Save
   Column "item_name VARCHAR(255)" added to warehouse_items.
   Input appears in the Warehouse Items form immediately.
```

> **When you save a Field, the `DynamicMigrationService` immediately adds or modifies the column in the database table. No `php artisan migrate` command is needed.**

**Reordering fields:** The `Sort Order` integer controls the position of inputs in the form (ascending) and columns in the table.

**Hiding from table vs. form:** `Show in Table` controls the list view column. `Editable` controls whether the input appears in the form. A field can be form-only (Show in Table off) or table-only display.

---

### Step 4: Use the Module

Once an Entity and its Fields are saved, navigate to the module in the admin sidebar. The URL follows the pattern:

```
/admin/dynamic/{table_slug}
```

For example: `warehouse_items` → `/admin/dynamic/warehouse_items`

What you get automatically:

```
┌──────────────────────────────────────────────────┐
│  Warehouse Items                    [+ New Item]  │
├──────────┬──────────────┬──────────┬─────────────┤
│ Item Name│ Category     │ Quantity │ Actions      │
├──────────┼──────────────┼──────────┼─────────────┤
│ Flour 50 │ Raw Material │ 240      │ Edit Delete  │
│ Sugar 25 │ Raw Material │  80      │ Edit Delete  │
└──────────┴──────────────┴──────────┴─────────────┘
     ↑ All is_listed=true fields become sortable columns
```

The **Create** and **Edit** forms are built automatically from your field definitions, with:
- Correct input types (text, number, date, select, toggle, etc.)
- Server-side validation from your `validation_rules`
- Foreign key dropdowns auto-resolved from `_id` suffixed fields
- RBAC-aware hidden/readonly fields per role

---

### Field Types Reference

| Type | DB Column Type | Filament Component | Use For |
|---|---|---|---|
| `text` | `VARCHAR(255)` | `TextInput` | Names, codes, short strings |
| `textarea` | `TEXT` | `Textarea` | Descriptions, notes, remarks |
| `number` | `DECIMAL(15,2)` | `TextInput` (numeric) | Prices, weights, amounts |
| `integer` | `BIGINT` | `TextInput` (integer) | Quantities, counts, sort order |
| `boolean` | `TINYINT(1)` | `Toggle` | Active flags, yes/no fields |
| `date` | `DATE` | `DatePicker` | Dates without time |
| `datetime` | `DATETIME` | `DateTimePicker` | Timestamps with time |
| `select` | `VARCHAR(100)` | `Select` | Enumerated options |
| `json` | `JSON` | `KeyValue` | Arbitrary key-value data |
| `media` | `VARCHAR(255)` | `FileUpload` | Images, documents (stores path) |

**Defining `select` Options:**

Enter options in the `Options` textarea as `key:value` pairs, one per line:

```
pending:Pending
processing:Processing
completed:Completed
cancelled:Cancelled
```

Or as a raw JSON object: `{"pending":"Pending","processing":"Processing"}`

---

### Smart Foreign Key Resolution

Any field whose name ends in `_id` is automatically detected as a foreign key and rendered as a searchable `Select` dropdown instead of a plain text input.

**Automatic resolution by stem pluralization:**

| Field Name | Resolved Table | Displayed As |
|---|---|---|
| `product_id` | `products` | Product name |
| `supplier_id` | `suppliers` | Supplier name |
| `customer_id` | `customers` | Customer name |
| `branch_id` | `branches` | Branch name |
| `category_id` | `categories` | Category name |
| `employee_id` | `employees` | Employee name |

**Context-aware overrides (built-in for Expense entities):**

| Entity Table | Field | Resolved Table | Why |
|---|---|---|---|
| `expense_subcategories` | `category_id` | `expense_categories` | Avoids generic `categories` table |
| `expense_vouchers` | `category_id` | `expense_categories` | Same |
| `expense_vouchers` | `subcategory_id` | `expense_subcategories` | Correct target table |
| `expense_vouchers` | `bank_account_id` | `bank_accounts` | Correct target table |

**Bank Account special formatting:**

`bank_account_id` dropdowns show richly formatted options:
```
Sonali Bank — Operating Account (001-123-456)
BRAC Bank — Payroll Account (012-987-654)
```

**Adding your own FK overrides:**

In `app/Filament/Resources/DynamicRecords/DynamicRecordResource.php`, add entries to `$contextFkOverrides`:

```php
private static array $contextFkOverrides = [
    // 'table_name.field_name' => 'target_table'
    'your_entity.warehouse_id' => 'warehouses',
    'your_entity.zone_id'      => 'delivery_zones',
];
```

---

### Special Entity Behaviours

These behaviours are hardcoded for specific entities in `DynamicRecordResource`:

| Entity / Field | Behaviour |
|---|---|
| `expense_vouchers` → `voucher_number` | Auto-generated as `EXP-{YEAR}-{NNNNN}` on create. Read-only in form. |
| `expense_vouchers` → `payment_method` | `live()` — triggers Livewire re-render on change |
| `expense_vouchers` → `bank_account_id` | Hidden unless `payment_method` is `bank_transfer` |
| `expense_vouchers` → `subcategory_id` | Cascades from `category_id` — only shows subcategories belonging to selected category |
| Any `expense_vouchers` row | Row action **Print Voucher** → opens `/print/expense-voucher/{id}` |

---

## Schema Builder Reference

The four underlying database tables that power the dynamic system:

### `modules` table
```
id          — primary key
name        — display name (e.g. "Expenses")
slug        — URL-safe identifier (e.g. "expenses")
icon        — heroicon name (e.g. "heroicon-o-receipt-percent")
is_active   — whether it appears in navigation
created_at / updated_at
```

### `entities` table
```
id            — primary key
module_id     — FK → modules
name          — display name (e.g. "Expense Vouchers")
slug          — becomes the actual DB table name (e.g. "expense_vouchers")
title_field   — field name used as record label (e.g. "voucher_number")
is_active
deleted_at    — soft deletes
created_at / updated_at
```

### `fields` table
```
id               — primary key
entity_id        — FK → entities
name             — DB column name (snake_case, e.g. "amount")
label            — UI display label (e.g. "Amount (৳)")
type             — one of: text, textarea, number, integer, boolean,
                           date, datetime, select, json, media
options          — JSON (for select type: {"key":"Label",...})
validation_rules — pipe-separated Laravel rules (e.g. "required|numeric|min:0")
is_required      — shortcut for required validation
is_listed        — show as table column
is_editable      — show in create/edit form
sort_order       — position in form and table (ascending)
deleted_at       — soft deletes
created_at / updated_at
```

### `relationships` table
```
id                  — primary key
source_entity_id    — FK → entities (the "owner" side)
target_entity_id    — FK → entities (the "related" side)
type                — hasMany | belongsTo | belongsToMany
foreign_key         — the FK column name
created_at / updated_at
```

---

## RBAC System

Orchestra uses a **three-dimensional** RBAC system built on Spatie Laravel Permission + Filament Shield + a custom `RolePermissionService`.

### Dimension 1 — Boolean Actions

Per role, per module or entity:

| Permission | Controls |
|---|---|
| `can_view` | Access to list and view records |
| `can_create` | New record creation |
| `can_edit` | Editing existing records |
| `can_delete` | Record deletion |
| `can_bulk_action` | Bulk operations (delete, export) |

### Dimension 2 — Numeric Limits

Per role, per module:

| Limit | Description |
|---|---|
| `approval_limit` | Maximum order/transaction value this role can approve |
| `discount_limit_pct` | Maximum discount percentage this role can apply |

### Dimension 3 — Field-Level Visibility

Per role, per entity:

| Setting | Description |
|---|---|
| `hidden_fields` | Array of field names completely hidden from this role |
| `readonly_fields` | Array of field names shown but not editable by this role |
| `own_records_only` | Role can only see records they personally created |

### Resolution Order

```
Entity-level rule (highest priority)
           ↓
Module-level rule
           ↓
Default: deny
           ↑
Super Admin: bypass_all_restrictions = true (skips all checks)
```

### Super Admin

Users with the `super_admin` role bypass all Filament authorization checks. This is enabled via Filament Shield's `define_via_gate = true` setting, which registers a `Gate::before()` callback at boot.

Config: `config/filament-shield.php`

```php
'super_admin' => [
    'enabled'         => true,
    'name'            => 'super_admin',
    'define_via_gate' => true,      // ← enables Gate::before bypass
    'intercept_gate'  => 'before',
],
```

### Staff Panel (`/app`)

A second Filament panel at `/app` is available for staff users. Resources loaded in this panel use the `ChecksStaffPanel` trait, which automatically disables edit, delete, and bulk-delete actions — making it a read-only view for non-admin staff.

---

## Print & Document System

### Available Print Views

All print views are browser-printable A4 layouts with CSS `@media print` optimization.

| Document | Route | Data Included |
|---|---|---|
| Credit Order Invoice | `GET /print/credit-order-invoice/{id}` | Order, customer, line items, totals, payment status |
| Customer Statement | `GET /print/customer-statement/{customerId}` | Ledger entries, running balance, date range |
| Payment Receipt | `GET /print/payment-receipt/{id}` | Payment details, customer, reference |
| Expense Voucher | `GET /print/expense-voucher/{id}` | Voucher number, amount, category, subcategory, payment method, bank details, signature row |

All routes are registered in `routes/web.php` pointing to `PrintController`.

### Invoice Template System

`InvoiceTemplate` records store a `config` JSON with layout/branding options per document type. Each document type can have one default template.

Key template options:

| Option | Type | Default | Description |
|---|---|---|---|
| `paper_size` | string | `A4` | Paper format |
| `orientation` | string | `portrait` | portrait / landscape |
| `header_bg` | hex | `#1e293b` | Header background color |
| `accent_color` | hex | `#f59e0b` | Accent / highlight color |
| `show_logo` | bool | `true` | Display company logo in header |
| `show_page_numbers` | bool | `false` | Footer page numbers |
| `watermark_text` | string | — | Diagonal background watermark |
| `footer_text` | string | — | Custom footer line |

Templates generate CSS variables via `toCssVars()`, injected into the print view's `<style>` block.

---

## Backup System

> **Admin panel → Settings → Backup Settings**

### How It Works

1. A scheduled `DatabaseBackupJob` fires on your configured schedule
2. It generates a full MySQL dump via PHP PDO
3. The dump is zipped as `orchestra_backup_{timestamp}.zip`
4. The zip is uploaded to your Google Drive folder
5. Files older than `retention_days` are automatically deleted from Drive
6. The result (success/failure, filename, size) is stored in `backup_configurations`

### Configuration

| Setting | Description |
|---|---|
| **Schedule** | `hourly`, `daily`, or `weekly` |
| **Retention Days** | Backups older than N days are auto-deleted |
| **Drive Folder ID** | ID from your Google Drive folder URL |
| **Google Credentials** | Paste your service account JSON |

### Manual Trigger

```bash
php artisan backup:run
```

---

## Notification System

> **Admin panel → Settings → Notification Channels**
> **Admin panel → Settings → Notification Event Rules**

### Channels

Define where notifications are delivered:

| Type | Configuration |
|---|---|
| Email | Recipient email address |
| SMS | API endpoint + phone number |
| Webhook | URL + optional custom headers |

### Event Rules

Link domain events to delivery channels:

| Example Event | When It Fires |
|---|---|
| `order.created` | New credit order placed |
| `order.approved` | Order status changes to approved |
| `order.shipped` | Order status changes to shipped |
| `payment.received` | Customer payment recorded |
| `grn.posted` | GRN verified and inventory posted |

Create a rule: choose an event, select one or more channels, and save. The `NotificationDispatcher` service handles routing at runtime.

---

## Bulk Price Update

> **Admin panel → Products → Bulk Price Update**

A custom Filament page for mass-updating product variant prices. Select any product to see all its active variants in a table, then update prices individually or derive them all from a formula.

### Manual Mode

Each variant row shows:
- Variant label (product + weight + grade + branch)
- Price input (numeric, right-aligned with ৳ prefix)
- Effective date picker

Click **Save All Prices** to write all changes in a single transaction.

### Formula Mode

Configure parameters and click **Recalculate All** to derive every variant's price automatically:

```
price(variant) = round(
                   (base_price ÷ base_weight) × variant_weight,
                   weight_rounding
                 ) + weight_premium

Non-base branches:  + branch_premium
```

**Formula Parameters:**

| Parameter | Description |
|---|---|
| **Base Branch** | The reference branch (lowest cost) |
| **Base Weight** | Reference weight in kg (e.g. 50) |
| **Base Price** | Price for base_weight at base_branch |
| **Weight Rounding** | Round to nearest N BDT (e.g. 5 → nearest ৳5) |
| **Weight Premium** | Fixed BDT added after proportional calculation |
| **Branch Premium** | Fixed BDT added for all non-base branches |

After recalculation, review the computed prices and click **Save All Prices** to persist them. Each saved change is logged to `product_prices` for history.

---

## Custom Report Builder

> **Admin panel → Reports → Report Builder**

### Creating a Report

1. Go to **Reports → Report Builder → New Report**
2. Enter a name and description
3. Choose a **Data Source** (e.g. Credit Orders)
4. Select which **Columns** to display from the available field list
5. Add **Filters** (date ranges, status, dropdown fields)
6. Optionally configure **Sort By** and **Group By**
7. Save

### Running a Report

1. Open the saved report → click **Run Report**
2. The filter form appears — fill in any date ranges, status selects, etc.
3. Results display in a paginated table (50 rows/page)
4. Click **Export to Excel** to download the full unfiltered or filtered dataset as `.xlsx`

### Available Filter Types

Filters are generated from the report definition and the data source's available fields:
- **Date range** — from/to date pickers
- **Status** — dropdown of available states
- **Customer / Supplier** — searchable select
- **Amount range** — min/max numeric inputs

---

## File Structure

```
app/
├── Filament/
│   ├── Concerns/
│   │   └── ChecksStaffPanel.php         ← Trait: makes resources read-only in /app panel
│   ├── Pages/                           ← 9 custom Filament pages
│   │   ├── Dashboard.php
│   │   ├── BulkPriceUpdate.php          ← Manual + formula pricing
│   │   ├── CompanySettings.php
│   │   ├── LandingPageSettings.php
│   │   ├── ManageDashboardSettings.php
│   │   ├── ManageFieldOptions.php
│   │   ├── RunReportPage.php            ← Report execution + Excel export
│   │   ├── BackupSettingsPage.php
│   │   └── DatabaseMaintenancePage.php
│   ├── Resources/
│   │   ├── Products/
│   │   │   ├── ProductResource.php
│   │   │   └── ProductVariantResource.php
│   │   ├── Sales/
│   │   │   ├── CustomerResource.php
│   │   │   ├── CreditOrderResource.php
│   │   │   └── CustomerPaymentResource.php
│   │   ├── Purchasing/
│   │   │   ├── SupplierResource.php
│   │   │   ├── PurchaseOrderResource.php
│   │   │   ├── GoodsReceivedNoteResource.php
│   │   │   └── PurchasePaymentResource.php
│   │   ├── DynamicRecords/
│   │   │   └── DynamicRecordResource.php   ← Core of the dynamic system
│   │   │       Pages/
│   │   │       ├── ListDynamicRecords.php
│   │   │       ├── CreateDynamicRecord.php  ← Auto voucher number generation
│   │   │       └── EditDynamicRecord.php
│   │   ├── Modules/                     ← Schema Builder: Modules
│   │   ├── Entities/                    ← Schema Builder: Entities
│   │   ├── Fields/                      ← Schema Builder: Fields
│   │   ├── Relationships/               ← Schema Builder: Relationships
│   │   ├── Notifications/
│   │   │   ├── NotificationChannelResource.php
│   │   │   └── NotificationEventRuleResource.php
│   │   ├── CustomReportResource.php     ← Report Builder
│   │   ├── InvoiceTemplateResource.php
│   │   └── UserResource.php
│   └── Widgets/
├── Http/Controllers/
│   └── PrintController.php              ← All print document routes
├── Jobs/
│   └── DatabaseBackupJob.php
├── Models/                              ← 41 models total
├── Providers/Filament/
│   ├── AdminPanelProvider.php           ← Main admin panel (/admin)
│   └── AppPanelProvider.php             ← Staff read-only panel (/app)
└── Services/
    ├── DynamicMigrationService.php      ← Schema::table() on field save
    ├── DynamicModelGenerator.php        ← eval() model class at runtime
    ├── RolePermissionService.php        ← 3-dimensional RBAC engine
    ├── CreditOrderWorkflowService.php   ← 11-state order state machine
    ├── CustomerPaymentService.php       ← Payment allocation + ledger
    ├── ProcurementService.php           ← PO lifecycle + GRN matching
    ├── ReportQueryBuilderService.php    ← Dynamic SQL + Excel export
    ├── NotificationDispatcher.php       ← Event-to-channel routing
    ├── GoogleDriveService.php           ← Backup upload + pruning
    ├── DatabaseMaintenanceService.php   ← SQL parser + safe execution
    ├── DashboardWidgetService.php       ← Role-aware widget visibility
    └── SchemaCache.php                  ← 5-min TTL entity/field cache

database/
└── migrations/                          ← 45 migrations

resources/
├── css/
│   ├── app.css                          ← Tailwind v4 entry point
│   └── filament/admin/theme.css         ← Filament custom theme
│                                           (scans app/Filament + resources/views/filament)
├── js/
│   └── app.js
└── views/
    ├── filament/pages/
    │   ├── bulk-price-update.blade.php  ← Alpine DOM collection, @assets CSS
    │   ├── backup-settings.blade.php
    │   ├── landing-page-settings.blade.php
    │   └── run-report.blade.php
    └── print/
        ├── credit-order-invoice.blade.php
        ├── customer-statement.blade.php
        ├── payment-receipt.blade.php
        └── expense-voucher.blade.php    ← Purple scheme, bank/cash layout

config/
└── filament-shield.php                  ← define_via_gate = true (super_admin bypass)
```

---

## Changelog

### April 2026

#### Dynamic Module — Expense Module Enhancements
- **FK dropdown preloading** — `expense_categories`, `expense_subcategories`, `bank_accounts` now use `->options()` callbacks with soft-delete awareness instead of raw search
- **Cascading subcategory** — `expense_vouchers.subcategory_id` live-filters to only show subcategories of the selected `category_id`
- **Auto voucher numbers** — `EXP-{YEAR}-{NNNNN}` auto-generated on create via `mutateFormDataBeforeCreate()`, read-only in form
- **Conditional bank field** — `bank_account_id` field is hidden unless `payment_method = bank_transfer`
- **Expense print action** — Print row action on every expense voucher row → `/print/expense-voucher/{id}`
- **Expense voucher print view** — Professional A4 layout with amount hero, payment section (cash vs. bank), and signature row

#### Dynamic Module — Core Fixes
- **`normalizeOptions()` helper** — Fixes `SelectFilter` crash caused by double-encoded JSON in `fields.options` column; checks `is_array()` first, falls back to `json_decode()` if string
- **Filament 5 namespace fix** — Corrected `use Filament\Forms\Get` → `use Filament\Schemas\Components\Utilities\Get` in `DynamicRecordResource`
- **Context-aware FK overrides** — `$contextFkOverrides` map added to `DynamicRecordResource` for `expense_*` entities
- **Suppliers duplicate module deleted** — Dynamic `Suppliers` module (id=30) removed from DB; it duplicated the static `SupplierResource`
- **Suppliers SelectFilter fix** — Replaced `->relationship()` (removed in Filament 5) with `->options()` on all `SelectFilter` instances

#### Products
- **ProductVariant 404 fix** — Root cause: route collision between `products/{record}` wildcard and `products/product-variants`; fixed by adding explicit `protected static ?string $slug = 'product-variants'` to `ProductVariantResource`
- **`$shouldSkipAuthorization = true`** — Added to `ProductVariantResource` to bypass Filament's policy-existence check (no `ProductVariantPolicy` exists; admin panel auth handled by middleware)
- **Bulk Price Update CSS fix** — Wrapped `<style>` block in `@assets`/`@endassets` so styles survive Livewire DOM morphing across re-renders
- **Price saving fix** — Replaced unreliable `wire:model.defer` on nested array inputs with Alpine DOM collection; `saveWithPrices(array $prices)` collects input values at click time from DOM
- **Post-save refresh fix** — Added `$saveCounter` property incremented on each save; included in `wire:key="price-table-{{ $productId }}-{{ $saveCounter }}"` to force full Alpine re-initialization with fresh DB values

#### Authorization
- **Filament Shield config published** — `config/filament-shield.php` published and `define_via_gate` set to `true` to register `Gate::before()` for `super_admin` role bypass

#### New Features Added
- **Custom Report Builder** — `CustomReportResource`, `RunReportPage`, `ReportQueryBuilderService` with dynamic SQL and Excel export
- **Invoice Template System** — `InvoiceTemplateResource` + `InvoiceTemplateSeeder` with 20+ layout/branding config options
- **Backup System** — `BackupSettingsPage`, `DatabaseBackupJob`, `GoogleDriveService`, `BackupConfiguration` model
- **Landing Page Settings** — `LandingPageSettings` page + `landing_page` column migration on `company_settings`
- **Expense Voucher print view** — `resources/views/print/expense-voucher.blade.php`
- **Payment fields on expense vouchers** — Migration `2026_04_13_000005_add_payment_fields_to_expense_vouchers.php`

#### Frontend / Theme
- **Filament custom theme generated** — `resources/css/filament/admin/theme.css` compiles all Tailwind classes from custom blade files
- **Tailwind v4 compatible setup** — Uses `@source` directives instead of v3's `content[]` array in `tailwind.config.js`
- **`viteTheme()` registered** — `AdminPanelProvider` loads `resources/css/filament/admin/theme.css` so all custom utility classes (e.g. `text-[10px]`, `bg-amber-900/30`, `tracking-widest`) are available

---

*Orchestra ERP — Built by Dhrobe Islam*
