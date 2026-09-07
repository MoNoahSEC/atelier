نعم. لكن بدل Prompt ضخم يكرر كل شيء ويستهلك Tokens، هذا إصدار **Master Prompt واحد** يحدد المعمارية والقيود بوضوح، ويُلزم أداة التنفيذ بأن تفحص المشروع الحالي وتعدّل فقط ما يلزم. وهو مناسب كـ **Foundation Prompt** للمشروع كله.

Act as a Principal Software Architect, Cloud Architect, Laravel 11 Expert, Database Engineer, Security Engineer, and Performance Engineer.

Build the production-grade backend foundation for a global, high-scale dropshipping/e-commerce platform using Laravel 11.

The system must be secure, stateless, horizontally scalable, automation-ready, highly optimized, and designed for future expansion into a large global commerce platform.

## ABSOLUTE RULES

1. Inspect the existing repository/project before making changes.
2. Do not rewrite or regenerate working code that does not need modification.
3. Modify only files required for the requested architecture.
4. Never duplicate existing functionality.
5. Reuse Laravel 11 native capabilities whenever possible.
6. Do not install unnecessary packages.
7. Do not create abstractions without a real architectural need.
8. Do not use floating-point numbers for money.
9. Do not hardcode secrets, credentials, URLs, domains, or environment-specific values.
10. Use environment variables for infrastructure configuration.
11. Keep controllers thin and business logic in services.
12. Make automation operations idempotent.
13. Prevent race conditions and duplicate processing.
14. Optimize for low latency, high concurrency, and horizontal scaling.
15. Avoid N+1 queries and unnecessary database reads.
16. Never return unnecessary data from APIs.
17. Never expose internal implementation details or secrets.
18. Do not output unchanged files.
19. Do not output explanations, tutorials, or conversational text.
20. Output only created or modified files.
21. Do not output pseudocode, TODOs, placeholders, or incomplete implementations.
22. Before final output, verify imports, namespaces, route names, model relationships, migration dependencies, configuration keys, and Laravel 11 compatibility.
23. Prefer the simplest correct implementation over over-engineering.
24. Preserve existing project conventions unless they conflict with the requirements below.
25. Optimize the generated response itself for token efficiency.

## TARGET STACK

* PHP 8.3+
* Laravel 11
* MySQL 8+
* Redis
* Laravel Horizon
* Laravel Sanctum
* REST JSON APIs
* Queue-based asynchronous processing
* Horizontal scaling compatible architecture

Use PhpRedis when available.

## ARCHITECTURAL GOALS

Build a stateless application layer suitable for multiple application servers behind a load balancer.

Use:

* Redis for cache
* Redis for sessions
* Redis for queues
* Redis-compatible rate limiting
* MySQL for persistent transactional data
* Laravel Horizon for queue management
* Sanctum for authenticated API access

No business-critical state should depend on local application-server storage.

Use UTC internally.

Design the system so read replicas can be added later without rewriting application code.

## DATABASE CONFIGURATION

Update Laravel database configuration to support:

* primary/write database
* one or more read databases
* sticky connections when appropriate
* environment-driven hosts, ports, usernames, passwords, and database names

Use connection configuration compatible with Laravel 11.

Do not hardcode infrastructure values.

## REDIS CONFIGURATION

Configure Redis for:

* cache
* sessions
* queues
* rate limiting where appropriate

Use clear connection names and environment variables.

Configure queue and cache prefixes to avoid collisions between environments.

Ensure the application can operate correctly when multiple app instances share the same Redis infrastructure.

## HORIZON

Configure Laravel Horizon for production-oriented background processing.

Create queue priorities/workloads for:

* product synchronization
* order synchronization
* fulfillment
* webhook processing
* shipment updates
* notifications
* general background tasks

Configure:

* balancing
* worker counts
* retries
* timeout
* backoff
* failed jobs
* environment-specific settings

Keep values configurable.

Do not block HTTP requests with long-running external synchronization tasks.

## CORE DOMAIN

Create or modify only the required migrations and models for the following core entities.

### PRODUCTS

Table: products

Fields:

* id
* sku
* title
* slug
* description nullable
* cost_price_minor
* retail_price_minor
* currency
* inventory
* attributes_json nullable
* status
* supplier_id nullable
* timestamps

Requirements:

* unique sku
* unique slug
* index status
* index supplier_id
* appropriate inventory/status indexes
* JSON cast for attributes_json

Use integer minor units for money.

### PRODUCT VARIANTS

Table: product_variants

Fields:

* id
* product_id
* sku
* title
* attributes_json nullable
* cost_price_minor
* retail_price_minor
* inventory
* status
* timestamps

Requirements:

* foreign key product_id
* unique or appropriately constrained SKU strategy
* indexes for product_id and status

### SUPPLIERS

Table: suppliers

Fields:

* id
* name
* code
* external_id nullable
* status
* metadata_json nullable
* timestamps

Requirements:

* unique code
* useful index for external_id and status

Design this so future suppliers can include AliExpress, wholesalers, private suppliers, fulfillment providers, or custom sourcing systems.

### CUSTOMERS

Table: customers

Fields:

* id
* email
* first_name
* last_name
* phone nullable
* status
* timestamps

Requirements:

* efficient email lookup
* appropriate indexes
* normalize/canonicalize email handling where appropriate

Do not expose sensitive customer fields through automation APIs unless required.

### CUSTOMER ADDRESSES

Table: customer_addresses

Fields:

* id
* customer_id
* type
* first_name
* last_name
* company nullable
* address_line_1
* address_line_2 nullable
* city
* state nullable
* postal_code nullable
* country_code
* phone nullable
* timestamps

Add foreign-key and lookup indexes.

### ORDERS

Table: orders

Fields:

* id
* order_number
* customer_id nullable
* customer_email
* currency
* subtotal_minor
* shipping_minor
* tax_minor
* discount_minor
* total_amount_minor
* payment_status
* shipping_status
* fulfillment_status
* tracking_number nullable
* carrier nullable
* external_order_id nullable
* notes nullable
* metadata_json nullable
* timestamps

Requirements:

* unique order_number
* indexes on customer_id
* customer_email
* payment_status
* shipping_status
* fulfillment_status
* external_order_id
* timestamps where useful

Use stable state values.

Store order monetary snapshots rather than recalculating historical values from current product prices.

### ORDER ITEMS

Table: order_items

Fields:

* id
* order_id
* product_id nullable
* product_variant_id nullable
* sku
* product_title
* quantity
* unit_price_minor
* total_price_minor
* metadata_json nullable
* timestamps

Requirements:

* indexed foreign keys
* immutable product/SKU/price snapshots
* integer quantity with validation
* integer monetary values

### PAYMENTS

Table: payments

Fields:

* id
* order_id
* provider
* provider_transaction_id nullable
* amount_minor
* currency
* status
* metadata_json nullable
* timestamps

Indexes:

* order_id
* provider
* provider_transaction_id
* status

### SHIPMENTS

Table: shipments

Fields:

* id
* order_id
* carrier nullable
* tracking_number nullable
* status
* shipped_at nullable
* delivered_at nullable
* metadata_json nullable
* timestamps

Indexes:

* order_id
* tracking_number
* status

### INVENTORY MOVEMENTS

Table: inventory_movements

Fields:

* id
* product_id
* product_variant_id nullable
* type
* quantity
* reference_type nullable
* reference_id nullable
* metadata_json nullable
* timestamps

Use this as the stock audit trail.

Add indexes suitable for:

* product
* variant
* movement type
* reference lookup
* date-based reporting

### WEBHOOK EVENTS

Table: webhook_events

Fields:

* id
* source
* event_type
* idempotency_key
* payload_json
* processed_at nullable
* failed_at nullable
* attempts
* timestamps

Create a unique constraint that prevents the same source + idempotency key from being processed more than once.

### AUDIT LOGS

Table: audit_logs

Fields:

* id
* actor_type nullable
* actor_id nullable
* action
* entity_type
* entity_id
* changes_json nullable
* ip_address nullable
* user_agent nullable
* timestamps

Use appropriate polymorphic indexes.

## MODELS

Create or update models with:

* declare(strict_types=1)
* typed properties
* strict return types
* relationships
* casts
* guarded/fillable protection
* useful query scopes
* correct date handling
* JSON casts
* relationship return types

Required relationships:

Product:

* supplier
* variants
* inventory movements

Customer:

* orders
* addresses

Order:

* customer
* items
* payments
* shipments

OrderItem:

* order
* product
* variant

Variant:

* product

Use appropriate `belongsTo`, `hasMany`, and polymorphic relationships.

Prevent accidental lazy loading in development/test environments using Laravel-supported mechanisms.

Avoid globally enabling behavior that can break production unnecessarily.

## MONEY

Never use float/double for money.

Use integer minor units plus currency.

Examples:

* USD 1099 = $10.99
* EUR 2599 = €25.99

Keep this convention consistent across:

* products
* orders
* order items
* payments
* discounts
* shipping
* taxes

## INVENTORY SAFETY

Inventory changes must be concurrency-safe.

Use:

* database transactions
* row-level locking where needed
* atomic updates
* inventory movement records

Prevent overselling under concurrent requests.

Never update stock based only on an unlocked read.

When inventory is modified:

1. obtain safe lock
2. validate current stock
3. apply change atomically
4. create inventory movement
5. commit transaction

## ORDER SAFETY

Order updates must be transaction-safe.

Do not allow duplicate fulfillment.

Use stable order and fulfillment states.

Protect against concurrent fulfillment attempts.

Use idempotency for external automation.

## API ARCHITECTURE

Version all APIs:

`/api/v1`

Use:

* Laravel Sanctum
* route middleware
* authorization
* rate limiting
* strict validation
* API Resources
* consistent JSON responses

Do not return raw Eloquent models directly.

Create resources where required, including:

* ProductResource
* OrderResource
* PendingOrderResource

## AUTOMATION API

Create these endpoints:

### POST /api/v1/automated/products/sync

Purpose:

Synchronize or upsert product data from Make.com/Integromat or other automation infrastructure.

Requirements:

* authentication
* authorization
* strict validation
* idempotency
* transaction safety
* upsert behavior
* supplier/external ID handling
* safe handling of missing optional values
* do not unintentionally overwrite protected internal fields
* do not create duplicates

### POST /api/v1/automated/orders/fulfillment

Purpose:

Receive fulfillment updates from external automation or fulfillment providers.

Support:

* order identifier
* external order identifier
* fulfillment status
* shipping status
* carrier
* tracking number
* external event/idempotency key

Requirements:

* validate transitions
* prevent duplicate processing
* transaction safety
* audit logging
* update shipment data
* update order state
* idempotent execution

### GET /api/v1/automated/orders/pending

Purpose:

Return orders eligible for external fulfillment.

Requirements:

* authenticated automation client
* return only eligible orders
* stable ordering
* pagination
* avoid duplicate dispatch
* avoid loading unnecessary data
* return only required fields
* efficient query plan
* safe concurrent processing

Use cursor pagination when beneficial.

## SECURITY FOR AUTOMATION

Secure automation APIs using Sanctum and a strong automation authentication strategy.

Support:

* Sanctum
* scoped permissions where appropriate
* rate limiting
* HMAC verification for webhook-style requests
* timestamp validation
* replay protection
* idempotency
* request validation

Do not trust external status values blindly.

Do not expose secrets in API responses or logs.

Do not log authentication tokens.

## FORM REQUESTS

Create strict FormRequest classes where appropriate:

* SyncProductRequest
* FulfillmentRequest
* PendingOrdersRequest if query validation is useful

Validate:

* IDs
* SKUs
* quantities
* status values
* currency codes
* monetary values
* tracking numbers
* external IDs
* idempotency keys
* required relationships

Use Laravel validation conventions.

## CONTROLLERS

Create a lightweight:

`AutomationApiController`

Controllers must:

* authenticate
* validate
* delegate to services
* return API Resources / standardized responses

Do not place business logic, inventory logic, or complex database workflows directly inside controllers.

## SERVICE LAYER

Create focused services only where business logic exists.

Preferred services:

* ProductSyncService
* OrderService
* FulfillmentService
* InventoryService
* WebhookService

Services must be:

* dependency injectable
* transaction-aware
* testable
* focused
* idempotent where appropriate

Do not introduce unnecessary repository layers unless required by the existing project architecture.

## JOBS

Create queued jobs where operations can be asynchronous.

Examples:

* SyncProductFromAutomation
* ProcessFulfillmentWebhook
* DispatchPendingOrder
* UpdateShipmentStatus

Jobs must support:

* retries
* timeout
* backoff
* failure handling
* idempotency
* unique execution where appropriate

Use queue middleware if useful for uniqueness or throttling.

Long-running external API operations must not block HTTP requests.

## WEBHOOK PROCESSING

Webhook handling must:

1. authenticate/verify request
2. validate timestamp/signature where applicable
3. calculate/store idempotency key
4. reject replayed events
5. persist event
6. process asynchronously when appropriate
7. record success/failure
8. track attempts

Webhook processing must be safe to retry.

## API RESPONSE STANDARD

Successful:

{
"success": true,
"message": "...",
"data": {},
"meta": {}
}

Failure:

{
"success": false,
"message": "...",
"error_code": "...",
"request_id": "...",
"data": null
}

Do not expose stack traces or internal exception details in production responses.

Use consistent HTTP status codes.

## ERROR HANDLING

Use Laravel 11 exception handling conventions.

Handle:

* validation failures
* authorization failures
* authentication failures
* not found
* conflict/idempotency conflicts
* business rule violations
* external integration failures
* unexpected exceptions

Return predictable JSON for API requests.

## RATE LIMITING

Create configurable limits for:

* public endpoints
* authenticated customers
* automation endpoints
* webhook endpoints

Use Redis-compatible throttling.

Keep values configurable via `.env`.

## PERFORMANCE

Optimize for:

* low query count
* low response latency
* efficient indexes
* selective column retrieval
* eager loading only when required
* no N+1 queries
* cursor pagination where appropriate
* background processing for expensive tasks
* caching for appropriate read-heavy data
* atomic updates
* efficient bulk upserts
* chunking for large datasets

Do not cache highly volatile transactional data blindly.

Do not introduce premature caching where invalidation becomes dangerous.

## DATABASE INDEXING

At minimum optimize/index:

* products.sku
* products.slug
* products.status
* products.supplier_id
* product_variants.product_id
* product_variants.sku
* orders.order_number
* orders.customer_id
* orders.customer_email
* orders.payment_status
* orders.shipping_status
* orders.fulfillment_status
* orders.external_order_id
* order_items.order_id
* order_items.product_id
* shipments.order_id
* shipments.tracking_number
* payments.order_id
* inventory_movements.product_id
* inventory_movements.product_variant_id
* webhook_events source + idempotency_key

Choose composite indexes only when justified by actual query patterns.

Avoid excessive indexes that unnecessarily increase write cost.

## SECURITY

Follow Laravel 11 security best practices.

Protect against:

* mass assignment
* SQL injection
* unauthorized access
* broken authorization
* replay attacks
* duplicate webhook execution
* secrets exposure
* unsafe file handling
* unsafe serialization
* excessive API data exposure

Use:

* authorization middleware/policies where needed
* validation
* secure authentication
* CORS configuration
* trusted proxy handling when required
* secure configuration through environment variables

Do not expose internal database IDs if an opaque/public identifier is more appropriate for the external API.

## LOGGING & OBSERVABILITY

Implement structured logging for important events:

* product sync
* order creation/update
* fulfillment
* inventory changes
* webhook processing
* queue failures
* integration failures

Include request/correlation IDs where practical.

Never log:

* passwords
* access tokens
* secrets
* payment credentials
* sensitive authentication information

## CONFIGURATION

Any environment-specific behavior must use `.env` configuration.

Do not hardcode:

* Redis credentials
* DB credentials
* API keys
* domains
* external integration secrets
* queue sizes
* rate limits
* service URLs

Generate/update configuration files only when necessary.

## TESTABILITY

Create architecture that is straightforward to test.

When modifying business-critical logic, include focused tests for:

* product upsert idempotency
* duplicate webhook prevention
* fulfillment idempotency
* inventory concurrency rules
* order state transitions
* API validation
* authentication/authorization
* pending-order filtering

Do not generate broad test suites for unrelated functionality.

## DEPLOYMENT READINESS

The implementation must be suitable for:

* local development
* staging
* production
* multiple application servers
* Redis shared infrastructure
* MySQL primary + read replicas
* queue workers
* Laravel Horizon

Do not depend on local filesystem state for shared application state.

## API DESIGN RULES

Use predictable:

* route naming
* resource naming
* HTTP verbs
* status codes
* validation
* pagination
* error responses

Keep automation APIs stable and backward-compatible within `/v1`.

## CODE QUALITY

All PHP code must:

* use `declare(strict_types=1);`
* follow PSR-12
* use strict types
* use dependency injection
* use typed method signatures
* use Laravel 11 conventions
* include required imports
* avoid dead code
* avoid duplicate logic
* avoid unnecessary abstraction
* remain readable despite optimization

## FILE GENERATION RULES

Before writing files:

1. inspect the repository
2. identify existing equivalent files
3. reuse existing infrastructure
4. determine exact dependencies
5. calculate migration order
6. modify only necessary files

Output format:

FILE: exact/path/to/file.php

```php
complete runnable code
```

For non-PHP configuration files, output the correct syntax and exact path.

Only include newly created or modified files.

Never repeat unchanged files.

Never output the same file twice.

Do not include explanations outside files.

## FINAL VALIDATION

Before final output, internally verify:

* Laravel 11 compatibility
* migration execution order
* foreign key dependencies
* imports
* namespaces
* model relationships
* route middleware
* Sanctum compatibility
* Horizon compatibility
* Redis configuration
* database read/write configuration
* API validation
* idempotency
* transaction safety
* inventory concurrency
* indexing
* JSON casting
* strict types
* API response consistency
* no plaintext secrets
* no duplicate functionality
* no unnecessary files
* no unchanged files in output

The final implementation is the backend foundation of a global commerce platform. Favor correctness, security, scalability, maintainability, and performance while keeping the implementation minimal and token-efficient.
