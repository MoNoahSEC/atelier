Act as a Principal Software Architect, Cloud Architect, Product Architect, Security Engineer, Performance Engineer, UX Engineer, and Senior Full-Stack Engineer.

You are continuing an EXISTING Laravel 11 commerce backend that has already been implemented.

DO NOT rebuild the existing backend foundation.

Your task is to inspect the current repository and complete the production-grade global commerce platform around the existing architecture.

# 0. NON-NEGOTIABLE EXECUTION RULES

Before changing anything:

1. Inspect the repository structure.
2. Read existing architecture and implementation.
3. Identify what is already implemented.
4. Reuse existing services, models, migrations, middleware, jobs, resources, routes, configuration, and conventions.
5. Do not recreate existing functionality.
6. Do not rewrite working files unless modification is required.
7. Do not duplicate business logic.
8. Do not introduce unnecessary packages.
9. Do not create abstractions without a concrete future or current use case.
10. Do not modify unrelated files.
11. Never hardcode secrets.
12. Never hardcode production URLs, API keys, payment credentials, database credentials, or supplier credentials.
13. Never use floating-point arithmetic for money.
14. Never trust client-provided totals, inventory, payment states, or fulfillment states.
15. Never process external webhooks without idempotency.
16. Never perform critical inventory/payment/fulfillment mutations without proper transaction/concurrency protection.
17. Never expose private/internal data through public APIs.
18. Never return raw database models when a controlled API representation is appropriate.
19. Never sacrifice application performance for decorative effects.
20. Never add a frontend library when native/platform functionality is sufficient.
21. Prefer server rendering and caching over unnecessary client-side JavaScript.
22. Prefer CSS transforms/opacity for animation.
23. Avoid layout-triggering animations.
24. Never load all products, orders, reviews, or customers into memory unnecessarily.
25. Never use unbounded queries.
26. Never create an N+1 query.
27. Never create a synchronous external API dependency in a user-critical request when it can safely be asynchronous.
28. Never assume an external provider is always available.
29. Build graceful failure and retry behavior.
30. Do not remove existing tests.
31. Add focused tests only where new production-critical behavior is introduced.
32. Do not output unchanged files.
33. Do not output duplicate files.
34. Do not output explanations.
35. Do not output pseudocode.
36. Do not output TODOs.
37. Do not output placeholders.
38. Output only newly created or modified files.
39. Keep generated output itself token-efficient.
40. If an existing implementation is better than your proposed implementation, preserve it.

# 1. EXISTING BACKEND ASSUMPTION

Assume the existing Laravel 11 backend already contains the previously implemented foundation, including most or all of:

- Redis infrastructure
- Laravel Horizon
- database read/write readiness
- Products
- Product variants
- Suppliers
- Customers
- Customer addresses
- Orders
- Order items
- Payments foundation
- Shipments
- Inventory movements
- Webhook events
- Audit logs
- Sanctum
- HMAC verification
- Correlation IDs
- FormRequests
- API Resources
- Automation API
- Product synchronization
- Fulfillment logic
- Inventory locking
- Idempotency
- Queue jobs
- standardized API errors
- strict typing
- lazy-loading protection

VERIFY what actually exists.

Do not assume files exist merely because they are listed above.

Extend the actual repository state.

# 2. TARGET ARCHITECTURE

The target platform should be:

CUSTOMER
↓
PREMIUM WEB STOREFRONT
↓
API / APPLICATION LAYER
↓
LARAVEL 11
↓
DOMAIN SERVICES
↓
MYSQL + REDIS
↓
QUEUES / HORIZON
↓
SUPPLIERS / PAYMENT PROVIDERS / SHIPPING PROVIDERS / AUTOMATION

Frontend:

Next.js App Router
React
TypeScript
Server Components by default
Client Components only when interaction requires them

Backend:

Laravel 11
PHP 8.3+
MySQL 8+
Redis
Laravel Horizon
Laravel Sanctum
REST API

The architecture must support future horizontal scaling.

# 3. FRONTEND OBJECTIVE

Do NOT build a generic ecommerce template.

Build a premium, editorial, visually immersive accessories-commerce experience inspired by the QUALITY LEVEL and interaction philosophy of premium fashion/sports commerce.

Do NOT copy Nike:
- branding
- logos
- trademarks
- copyrighted assets
- exact layouts
- exact visual compositions
- proprietary copy

Create an ORIGINAL brand experience.

The website should feel:

- premium
- modern
- editorial
- fashion-oriented
- visually bold
- minimalist where useful
- highly interactive
- fast
- conversion-focused
- mobile-first

The visual experience must combine:

PREMIUM BRAND
+
EDITORIAL PRODUCT STORYTELLING
+
HIGH-END MOTION
+
E-COMMERCE CONVERSION
+
EXTREME PERFORMANCE

# 4. FRONTEND PERFORMANCE PRINCIPLE

Visual quality must NEVER justify poor performance.

Define a strict performance budget.

Prioritize:

- fast server response
- small client JavaScript
- Server Components
- streaming where useful
- optimized images
- responsive image sizes
- AVIF/WebP where supported
- lazy loading below the fold
- preload only critical assets
- font optimization
- code splitting
- route-level loading
- suspense boundaries where useful
- CDN-ready assets
- minimal third-party scripts
- minimal hydration
- GPU-friendly animations

Avoid:

- unnecessary animation libraries
- autoplaying large videos above the fold unless optimized
- huge image downloads
- excessive DOM nesting
- scroll listeners when CSS/IntersectionObserver can solve the problem
- layout thrashing
- synchronous analytics blocking rendering
- unnecessary client state

Target excellent Core Web Vitals.

# 5. DESIGN SYSTEM

Create one centralized design system.

Do NOT let individual pages invent their own styling.

Define reusable tokens for:

- colors
- typography
- spacing
- container widths
- breakpoints
- radii
- borders
- shadows
- buttons
- inputs
- cards
- badges
- navigation
- drawers
- modals
- product media
- price presentation
- status indicators

Create reusable primitives:

- Button
- Link
- IconButton
- Input
- Select
- Checkbox
- Badge
- Modal
- Drawer
- Tabs
- Toast
- Skeleton
- Spinner
- Breadcrumbs
- ProductCard
- ProductGrid
- ProductGallery
- Price
- Rating
- QuantitySelector
- AddToCart
- WishlistButton
- EmptyState
- ErrorState

Keep them accessible.

# 6. TYPOGRAPHY & VISUAL HIERARCHY

Use strong editorial typography.

Use typography to create hierarchy rather than adding unnecessary decorative elements.

Support:

- display headings
- section headings
- body text
- metadata
- price
- promotional text
- navigation
- product labels

Ensure:

- responsive type scale
- readable line lengths
- sufficient contrast
- predictable wrapping
- no CLS caused by fonts

# 7. MOTION SYSTEM

Build a centralized motion system.

Motion should include only useful interactions such as:

- page transitions
- hero reveals
- product image transitions
- hover interactions
- drawer opening/closing
- cart updates
- wishlist state
- filter interactions
- modal transitions
- scroll reveals
- image transitions
- collection transitions

Prefer:

- transform
- opacity
- scale
- clip-path only when performance is acceptable

Avoid:

- continuous expensive animations
- excessive parallax
- expensive blur/filter effects
- animations that block interaction
- motion on every element

Respect:

`prefers-reduced-motion`

Users who disable motion must receive a complete usable experience.

# 8. GLOBAL RESPONSIVE SYSTEM

Mobile-first.

Support:

- small phones
- large phones
- tablets
- laptops
- large desktop displays

The experience must not simply shrink desktop layouts.

Create intentional responsive layouts for:

- navigation
- hero
- product grid
- product page
- filters
- cart
- checkout
- account
- admin

# 9. GLOBAL STOREFRONT STRUCTURE

Implement the following customer-facing architecture.

HOME

- announcement bar
- premium navigation
- hero
- featured drop
- new arrivals
- best sellers
- editorial collection
- trending products
- shop by category
- bundles
- UGC/social proof
- promotional section
- newsletter
- footer

Avoid excessive sections.

Each section must have a conversion or storytelling purpose.

# 10. NAVIGATION

Desktop:

- logo
- primary categories
- search
- account
- wishlist
- cart

Mobile:

- menu
- search
- account
- wishlist
- cart

Navigation should feel premium and interactive but remain fast.

Support:

- sticky behavior where useful
- active category state
- accessible keyboard navigation
- touch-friendly targets

# 11. SEARCH

Implement a production-ready search experience.

Requirements:

- instant search
- debounced requests
- product suggestions
- category suggestions
- typo-tolerant architecture
- recent searches
- empty state
- no-results recommendations
- keyboard navigation
- mobile search
- analytics events

Search must not create excessive API requests.

Prepare the architecture for future Elasticsearch/Algolia/Meilisearch integration without forcing it into the initial system if not necessary.

# 12. CATEGORY / COLLECTION PAGES

Create flexible collection pages.

Support:

- title
- description
- hero media
- product grid
- sorting
- filters
- price filters
- availability
- category
- attributes
- variants
- pagination/cursor loading
- mobile filter drawer
- URL-synchronized filter state

Use server-driven filtering where appropriate.

Do not load the entire catalog into the browser.

# 13. PRODUCT PAGE

The product page is one of the most important conversion surfaces.

Include:

- large product gallery
- responsive media
- image zoom when useful
- variants
- price
- compare-at price when valid
- stock status
- quantity
- add to cart
- buy now where appropriate
- wishlist
- shipping information
- returns information
- trust signals
- product details
- materials/specifications
- reviews
- related products
- frequently bought together
- recently viewed
- recommendation section

Mobile:

Use sticky purchase controls when appropriate.

Do not make critical product information inaccessible.

# 14. PRODUCT MEDIA

Do not blindly serve supplier images.

Build a normalized media architecture.

Product media should support:

- image
- video
- ordering
- alt text
- variant mapping
- focal point
- responsive sizes

Prepare backend/API support for future media providers/CDN.

Never make the frontend dependent on a supplier's image URL permanently.

# 15. CART

Implement a high-conversion cart.

Support:

- add
- remove
- quantity update
- variant updates
- item validation
- stock validation
- price validation
- discount
- shipping threshold
- upsells
- bundles
- recommendations
- subtotal
- estimated shipping
- estimated tax where available

Use a cart drawer for fast shopping.

Cart state must remain reliable across refreshes and authenticated sessions.

# 16. CART MERGE

Implement safe guest-to-user cart merge.

Rules:

- preserve valid guest items
- merge duplicate product variants correctly
- validate current prices
- validate inventory
- remove invalid items safely
- never duplicate quantities incorrectly

Make merge transactional where persistent server-side state is involved.

# 17. CHECKOUT

Build checkout around correctness and reliability.

Stages:

1. Contact
2. Shipping address
3. Shipping method
4. Payment
5. Review
6. Order confirmation

Do not make checkout unnecessarily long.

Support mobile-first experience.

Never trust client-calculated totals.

Backend must calculate the authoritative final amount.

# 18. INTERNATIONAL COMMERCE

Prepare the platform for global customers.

Support:

- currency
- locale
- country
- language
- timezone
- country-specific address structures
- shipping zones
- tax rules abstraction
- payment methods
- localized formatting

Do not hardcode USD-only assumptions.

Represent currency explicitly.

Use ISO-style country/currency codes where appropriate.

Prepare i18n architecture from the beginning.

Initial language can be English, but architecture must support adding Arabic and other languages without rebuilding the frontend.

RTL support must be architecturally possible.

# 19. TAX ARCHITECTURE

Do not hardcode tax logic into checkout components.

Create a tax calculation abstraction.

Support future providers/rules.

The architecture should allow:

- tax-inclusive pricing
- tax-exclusive pricing
- country-specific rules
- region/state rules
- exemptions where applicable
- external tax providers

Do not claim tax compliance automatically.

Keep tax calculation server-authoritative.

# 20. SHIPPING ARCHITECTURE

Create a provider-agnostic shipping abstraction.

Do NOT tie the core domain directly to one shipping company.

Support concepts:

- ShippingProvider
- ShippingRate
- Shipment
- TrackingEvent
- FulfillmentProvider

Architecture must allow providers to be added later.

Support:

- rates
- labels where supported
- tracking
- shipment status
- delivery events
- cancellations
- returns

External shipping calls must be asynchronous where appropriate.

# 21. SUPPLIER ARCHITECTURE

This is critical for the dropshipping business.

Do NOT couple ProductSyncService directly to AliExpress.

Create a supplier/provider abstraction.

Example conceptual interfaces:

- SupplierCatalogProvider
- SupplierInventoryProvider
- SupplierOrderProvider
- SupplierShipmentProvider

Potential adapters:

- AliExpress
- wholesaler
- private supplier
- future supplier

Do not implement integrations that are not actually needed yet.

Build the abstraction so a new supplier can be added without rewriting product/order business logic.

# 22. PRODUCT SOURCING DATA

Separate:

CUSTOMER-FACING PRODUCT DATA

from

SUPPLIER-SOURCING DATA.

Supplier information must not leak into public product responses.

Store where appropriate:

- supplier product ID
- supplier SKU
- supplier cost
- supplier inventory
- supplier shipping estimate
- supplier URL
- synchronization timestamp
- supplier metadata

Keep internal sourcing information private.

# 23. PRODUCT SYNC

Product synchronization must:

- be idempotent
- support bulk operations
- use upsert carefully
- preserve controlled local fields
- handle discontinued products
- handle supplier stock changes
- handle price changes
- record sync errors
- support retry
- avoid duplicate products

Never let supplier data blindly overwrite manually curated brand content.

Separate:

supplier raw data

from:

internal normalized product data

when useful.

# 24. ORDER LIFECYCLE

Implement a clear domain state model.

Separate at minimum:

- payment state
- fulfillment state
- shipping state

Do not combine them into one overloaded status.

Validate state transitions.

Disallow impossible transitions.

Keep a history/event trail for important transitions.

# 25. PAYMENT ARCHITECTURE

Create a provider-agnostic payment layer.

Do not couple OrderService directly to a specific payment company.

Support:

- payment intent
- authorization
- capture
- payment success
- payment failure
- refund
- partial refund
- chargeback/dispute events where applicable
- webhook verification
- idempotency

Use a provider adapter architecture.

The first provider can be implemented later without changing order domain architecture.

Never store raw card data.

Never log payment secrets.

Never mark an order paid solely because the browser says payment succeeded.

The final payment state must be verified server-side.

# 26. PAYMENT WEBHOOKS

Payment webhooks must:

- verify signatures
- reject replay
- use idempotency
- persist event
- process safely
- support retry
- update payment state
- update order state only according to verified business rules

Do not create duplicate payments/orders from repeated provider events.

# 27. RETURNS / REFUNDS

Prepare domain architecture for:

- returns
- refund requests
- approved returns
- partial refunds
- full refunds
- return statuses
- refund statuses

Do not implement unnecessary UI if not needed for V1, but ensure the data/domain architecture is extendable.

# 28. WISHLIST

Implement:

- guest wishlist strategy
- authenticated wishlist
- add/remove
- duplicate prevention
- product availability handling

Do not require login just to browse.

# 29. REVIEWS

Prepare review architecture.

Support:

- rating
- title
- content
- customer identity
- verified purchase
- moderation state
- media
- timestamps

Reviews must never be blindly trusted as verified purchases.

Create moderation-ready states.

# 30. RECOMMENDATIONS

Prepare a recommendation architecture that can start simple.

Initial recommendation strategies can include:

- related products
- same collection
- frequently bought together
- popular products
- recently viewed

Create an abstraction so ML/AI recommendations can replace these later without rewriting the storefront.

# 31. CUSTOMER ACCOUNT

Create:

- profile
- addresses
- orders
- order details
- wishlist
- recently viewed
- preferences

Use secure authentication.

Do not expose sensitive internal fields.

# 32. EMAIL / NOTIFICATIONS

Prepare event-driven notification architecture for:

- order confirmation
- payment confirmation
- fulfillment
- shipment
- delivery
- refund
- abandoned cart
- account events

Do not block the HTTP request unnecessarily.

Dispatch notifications through queues.

Keep provider integrations replaceable.

# 33. ANALYTICS

Create a first-party analytics event architecture.

Track useful events:

- page_view
- product_view
- search
- add_to_cart
- remove_from_cart
- begin_checkout
- shipping_selected
- payment_started
- purchase
- refund
- wishlist_add
- recommendation_click
- coupon_applied

Do not send excessive events.

Do not block rendering on analytics.

Avoid exposing personally sensitive information unnecessarily.

Prepare events for both product analytics and future AI models.

# 34. COMMERCE METRICS

Admin analytics must eventually support:

- revenue
- net revenue
- orders
- AOV
- conversion rate
- gross margin
- product margin
- refund rate
- repeat purchase rate
- customer acquisition cost
- ROAS
- contribution margin
- top products
- weak products
- country performance
- supplier performance
- fulfillment performance

Do not calculate expensive analytics synchronously for every dashboard request.

Use aggregation strategies where appropriate.

# 35. ADMIN PANEL / COMMAND CENTER

Build an internal admin architecture, not a generic dashboard.

Sections:

Dashboard
Products
Variants
Collections
Orders
Customers
Suppliers
Inventory
Shipments
Payments
Refunds
Coupons
Reviews
Automation
Webhooks
Logs
Analytics
Settings

Admin functionality must have explicit authorization.

Use policies/permissions.

Never treat being authenticated as being an administrator.

# 36. ADMIN PRODUCT MANAGEMENT

Admin users should eventually be able to:

- create product
- edit product
- publish/unpublish
- manage price
- manage inventory
- manage variants
- manage media
- manage collections
- edit customer-facing descriptions
- view supplier source
- view sync state
- view margin

Keep supplier data and public data conceptually separate.

# 37. INVENTORY

Support:

- available
- reserved
- committed
- incoming
- damaged/adjustment if needed

Avoid simplistic single-number inventory logic if the business requirements later require reservation.

Do not introduce unnecessary complexity now.

Design so reservations can be added safely.

# 38. COUPONS & PROMOTIONS

Create extensible promotion architecture.

Support future:

- percentage discount
- fixed discount
- minimum cart total
- product-specific discount
- collection-specific discount
- free shipping
- buy-two/save
- bundles

Do not hardcode promotional logic into the cart UI.

Backend calculates authoritative discounts.

Prevent coupon abuse through validation, limits, expiry, and usage tracking where applicable.

# 39. SEO

Build production-grade SEO.

Support:

- metadata
- canonical URLs
- Open Graph
- Twitter cards
- product structured data
- breadcrumbs
- collection metadata
- sitemap architecture
- robots
- clean URLs
- localized SEO architecture

Avoid duplicate content.

Product URLs must use stable slugs.

Prepare redirects for slug changes.

# 40. ACCESSIBILITY

Target WCAG-oriented accessibility.

Require:

- semantic HTML
- keyboard navigation
- focus management
- accessible labels
- screen-reader-friendly states
- sufficient contrast
- reduced motion
- touch-friendly controls
- meaningful alt text

Do not use animation as the only indication of state.

# 41. SECURITY

Implement defense in depth.

Consider:

- authentication
- authorization
- CSRF where applicable
- CORS
- rate limits
- HMAC
- replay protection
- idempotency
- secure cookies
- secure headers
- validation
- mass assignment protection
- data exposure
- audit logs
- secret handling

Never expose:

- supplier credentials
- payment secrets
- access tokens
- HMAC secrets
- internal automation credentials
- private administrative data

# 42. OBSERVABILITY

Prepare for production operation.

Track:

- request ID
- correlation ID
- queue job ID
- webhook event ID
- order ID
- supplier operation ID

Use structured logs.

Support debugging of a complete request flow:

Customer Request
→ API
→ Service
→ Job
→ Supplier/Payment Provider
→ Webhook
→ Order State

Do not log secrets.

# 43. FAILURE HANDLING

Every external dependency must have failure handling.

Support:

- timeout
- retry
- exponential backoff
- dead-letter/failed-job strategy where appropriate
- idempotency
- partial failure handling
- user-safe errors
- operational logging

Do not retry non-idempotent operations blindly.

# 44. CACHING

Use caching selectively.

Good candidates:

- public product data
- collection metadata
- navigation
- configuration
- stable recommendations
- expensive read-heavy aggregates

Do not blindly cache:

- payment state
- inventory truth
- checkout totals
- fulfillment state

Define invalidation strategy before adding cache.

# 45. API PERFORMANCE

Use:

- pagination
- selective fields
- resource serialization
- eager loading
- query scopes
- indexes
- caching where appropriate
- bulk operations where appropriate

Do not return huge product payloads to collection pages.

Create separate lightweight API shapes for:
- list
- detail
- admin
- automation

# 46. API CONTRACT

Keep public API contracts stable.

Use:

`/api/v1`

Do not silently break existing consumers.

For breaking changes, prepare a new version.

Use explicit validation and error codes.

# 47. FRONTEND STATE MANAGEMENT

Do not introduce global state management by default.

Use:

- server state on the server when possible
- URL state for filters/search
- local state for UI interactions
- persistent cart state only where necessary

Only add a global client store if the actual application complexity proves it necessary.

# 48. DATA FETCHING

Prefer:

server-side fetching

over:

client-side fetching

for SEO-critical and initial content.

Use client fetching only for genuinely interactive data.

Avoid waterfall requests.

Parallelize independent requests.

Cache stable requests.

# 49. FRONTEND ERROR RESILIENCE

Every major route should support:

- loading state
- error state
- empty state
- retry behavior

Do not display raw API exceptions.

Gracefully handle:

- product unavailable
- price changed
- stock changed
- network failure
- payment failure
- session expiration

# 50. CHECKOUT CONSISTENCY

Before placing an order, backend must revalidate:

- product existence
- variant existence
- price
- inventory
- discounts
- shipping
- taxes
- currency
- payment state

Never rely on stale browser state.

# 51. ORDERS & IDEMPOTENCY

Every external order creation/update flow must support an idempotency key.

Repeated requests must produce the same logical result rather than duplicate records.

Use database-level constraints where possible.

Application-level checks alone are insufficient for concurrency safety.

# 52. DATABASE EVOLUTION

Migrations must:

- be reversible where practical
- respect foreign-key order
- avoid destructive changes without migration safety
- avoid accidental data loss
- use indexes intentionally

For large tables, avoid unsafe blocking schema operations where possible.

Design future migrations for production-scale datasets.

# 53. TESTING

Add focused tests for critical paths.

Minimum areas:

- product sync
- supplier isolation
- order creation
- price validation
- inventory concurrency
- duplicate webhooks
- fulfillment idempotency
- payment webhook verification
- authentication
- authorization
- cart merge
- checkout validation
- coupon rules
- API resources
- critical frontend interactions

Do not create meaningless snapshot tests.

Prefer tests that protect business correctness.

# 54. DEPLOYMENT

Architecture must support:

- local
- staging
- production

And multiple:

- web instances
- queue workers
- Horizon workers

Shared state must live in shared infrastructure.

Do not depend on local server memory or local filesystem for critical application state.

# 55. ENVIRONMENT SEPARATION

Never mix:

- development
- staging
- production

credentials/configuration.

Use environment variables.

Keep secrets out of source control.

# 56. FUTURE EXTENSIBILITY

The architecture must allow future addition of:

- mobile app
- marketplace
- multi-vendor sellers
- multiple suppliers
- AI recommendation engine
- AI customer support
- advanced search
- warehouse management
- subscription products
- loyalty
- referral system
- affiliate system
- multi-region deployment

Do not implement all these features now.

Only make current architecture capable of accepting them later.

# 57. MARKETPLACE READINESS

The current business is single-brand/single-store.

Do NOT implement multi-vendor marketplace complexity now.

However, avoid architectural decisions that would make future marketplace expansion impossible.

Keep product, supplier, seller, fulfillment, and order concepts sufficiently separated.

# 58. BRAND / UX DIRECTION

Create an original visual identity for the accessories brand.

Desired principles:

- premium
- confident
- modern
- editorial
- visually memorable
- product-focused
- international
- not generic dropshipping
- not copied from another brand

Use large visual moments strategically.

Use whitespace strategically.

Use bold typography strategically.

Use motion strategically.

Every visual effect must support:
- branding
- hierarchy
- discovery
- conversion

# 59. MOBILE EXPERIENCE

Mobile is a first-class product.

Optimize:

- touch targets
- navigation
- filters
- product gallery
- cart
- checkout
- sticky purchase actions
- performance
- bandwidth usage

Never treat mobile as a compressed desktop layout.

# 60. DESIGN-TO-CODE RULE

When implementing a design:

Do not hardcode visual values repeatedly.

Use reusable design tokens and components.

Do not create one-off markup for every product card.

Do not duplicate layout logic.

# 61. PACKAGE DISCIPLINE

Before installing any dependency:

Ask internally:

Can Laravel/React/Next.js/platform APIs solve this?

If yes, do not install a package.

If no, choose the smallest mature dependency.

Avoid dependency bloat.

# 62. THIRD-PARTY INTEGRATIONS

All integrations must be isolated behind adapters/services.

Never scatter provider-specific code across:

- controllers
- models
- frontend components
- order logic

Provider-specific code belongs in integration boundaries.

# 63. EXTERNAL API RESILIENCE

External APIs may:

- timeout
- rate-limit
- change responses
- return invalid data
- become unavailable
- send duplicate webhooks

Design accordingly.

Validate external responses.

Use timeouts.

Retry only safe operations.

Log provider failures safely.

# 64. DATA NORMALIZATION

Supplier data is untrusted external input.

Normalize:

- titles
- SKUs
- currency
- prices
- inventory
- country codes
- variant attributes

Validate before persistence.

Do not blindly trust supplier payload structure.

# 65. CUSTOMER PRIVACY

Minimize collected customer data.

Expose only necessary data.

Do not leak customer addresses/emails to public APIs.

Prepare architecture for deletion/export requirements.

Keep audit/security considerations separate from public customer-facing data.

# 66. ADMIN SECURITY

Administrative actions must be auditable.

For important mutations record:

- actor
- action
- target
- timestamp
- relevant before/after data where safe

Do not log secrets.

Use explicit admin authorization.

# 67. BACKWARD COMPATIBILITY

Existing working APIs and automation integrations must remain functional.

Before changing an existing endpoint:

- inspect consumers
- preserve response compatibility where practical
- avoid breaking field names
- version breaking changes

# 68. IMPLEMENTATION ORDER

Implement in dependency-safe order:

PHASE A
Repository inspection and architecture mapping.

PHASE B
Frontend foundation + design system.

PHASE C
API client/data layer.

PHASE D
Storefront home/navigation/search/collections/product pages.

PHASE E
Cart/wishlist/customer state.

PHASE F
Checkout/order integration.

PHASE G
Payments abstraction.

PHASE H
Shipping abstraction.

PHASE I
Supplier abstraction and synchronization.

PHASE J
Reviews/recommendations/promotions.

PHASE K
Admin command center.

PHASE L
Analytics/observability.

PHASE M
Security hardening/performance optimization/testing.

Do not pretend unfinished integrations are production-ready.

# 69. DEFINITION OF DONE

A feature is complete only when:

- implemented
- connected to existing architecture
- authenticated/authorized where required
- validated
- error-handled
- idempotent where appropriate
- tested where business-critical
- responsive
- accessible
- performant
- SEO-safe where public
- observable where operationally important

# 70. PERFORMANCE DEFINITION OF DONE

Do not claim "zero lag".

Instead optimize toward:

- minimal JS
- minimal hydration
- minimal API round trips
- minimal database queries
- optimized media
- fast TTFB
- excellent Core Web Vitals
- responsive interaction
- resilient slow-network behavior

# 71. OUTPUT DISCIPLINE

The most important token-saving instruction:

DO NOT regenerate the whole project.

DO NOT print existing code.

DO NOT print unchanged files.

DO NOT explain what you did.

DO NOT provide tutorials.

DO NOT repeat requirements.

ONLY output:

1. files created
2. files modified

For each:

FILE: exact/path

```language
complete file contents
```

If no code change is necessary for a requirement because the existing repository already satisfies it, do not create or output anything for that requirement.

# 72. FINAL SELF-AUDIT

Before returning output, verify internally:

- existing backend preserved
- no duplicated functionality
- migrations compatible
- relationships correct
- API compatibility preserved
- payment state cannot be spoofed
- inventory is concurrency-safe
- webhook processing is idempotent
- supplier data cannot leak publicly
- customer data cannot leak publicly
- frontend is responsive
- frontend is accessible
- SEO is implemented correctly
- images are optimized
- animations respect reduced motion
- no unnecessary JavaScript
- no unnecessary dependencies
- no N+1 queries
- no unbounded queries
- no floating money values
- no hardcoded secrets
- rate limiting exists where needed
- external integrations have timeouts/retries
- queues are used for expensive asynchronous work
- critical state changes are transactional
- existing functionality has not been unnecessarily rewritten

If something cannot safely be implemented without knowing an external provider's exact API contract, build the provider-agnostic boundary and configuration structure rather than inventing undocumented provider behavior.

The final product should behave as a serious global commerce platform, not as a generic dropshipping template.

Prioritize:

CORRECTNESS
>
SECURITY
>
DATA INTEGRITY
>
PERFORMANCE
>
SCALABILITY
>
MAINTAINABILITY
>
CONVERSION
>
VISUAL POLISH

while minimizing implementation complexity and token usage.