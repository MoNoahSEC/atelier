<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AutomationApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1/automated')->middleware(['auth:sanctum', 'throttle:automation'])->group(function () {
    
    // Product Sync (Upsert)
    Route::post('/products/sync', [AutomationApiController::class, 'syncProduct'])
        ->name('api.v1.automated.products.sync');

    // Fulfillment Webhook (Async processing)
    Route::post('/orders/fulfillment', [AutomationApiController::class, 'fulfillment'])
        ->name('api.v1.automated.orders.fulfillment')
        // Using a specific middleware for idempotency/replay protection (optional if handled in Job, but good practice)
        ->middleware('throttle:webhooks'); 

    // Pending Orders for Fulfillment Pull
    Route::get('/orders/pending', [AutomationApiController::class, 'pendingOrders'])
        ->name('api.v1.automated.orders.pending');

});

/*
|--------------------------------------------------------------------------
| Public Storefront & Webhook API Routes
|--------------------------------------------------------------------------
*/

Route::post('/telegram/webhook', [\App\Http\Controllers\TelegramWebhookController::class, 'handle'])
    ->name('api.telegram.webhook');

// Webhook routes (no auth, no CSRF)
Route::prefix('v1/webhooks')->group(function () {
    Route::post('/stripe', [\App\Http\Controllers\Api\Webhooks\StripeWebhookController::class, 'handle'])
        ->name('api.v1.webhooks.stripe');
    Route::post('/paymob', [\App\Http\Controllers\Api\Webhooks\PaymobWebhookController::class, 'handle'])
        ->name('api.v1.webhooks.paymob');
});

// Payment methods (public)
Route::get('v1/public/payment-methods', [\App\Http\Controllers\Api\Public\PaymentMethodController::class, 'index'])
    ->name('api.v1.public.payment-methods');


Route::prefix('v1/public')->group(function () {
    // Collections
    Route::get('/collections', [\App\Http\Controllers\Api\Public\CollectionController::class, 'index'])
        ->name('api.v1.public.collections.index');
    Route::get('/collections/{slug}', [\App\Http\Controllers\Api\Public\CollectionController::class, 'show'])
        ->name('api.v1.public.collections.show');

    // Catalog
    Route::get('/catalog', [\App\Http\Controllers\Api\Public\CatalogController::class, 'index'])
        ->name('api.v1.public.catalog.index');
    Route::get('/catalog/{slug}', [\App\Http\Controllers\Api\Public\CatalogController::class, 'show'])
        ->name('api.v1.public.catalog.show');

    // Cart (session-based, no auth required)
    Route::get('/cart', [\App\Http\Controllers\Api\Public\CartController::class, 'show'])
        ->name('api.v1.public.cart.show');
    Route::post('/cart/items', [\App\Http\Controllers\Api\Public\CartController::class, 'addItem'])
        ->name('api.v1.public.cart.add');
    Route::patch('/cart/items/{item}', [\App\Http\Controllers\Api\Public\CartController::class, 'updateItem'])
        ->name('api.v1.public.cart.update');

    // Checkout
    Route::post('/checkout', [\App\Http\Controllers\Api\Public\CheckoutController::class, 'place'])
        ->name('api.v1.public.checkout.place');

    // CMS Pages
    Route::get('/pages/{slug}', [\App\Http\Controllers\Api\Public\PageController::class, 'show'])
        ->name('api.v1.public.pages.show');

    // Public order tracking (guest lookup)
    Route::post('/orders/track', [\App\Http\Controllers\Api\Public\CustomerOrderController::class, 'trackGuestOrder'])
        ->name('api.v1.public.orders.track');

    // Paymob test confirmation (simulation mode)
    Route::post('/paymob/confirm-test-payment', [\App\Http\Controllers\Api\Public\CustomerOrderController::class, 'confirmPayment'])
        ->name('api.v1.public.paymob.confirm');

    // Public store settings (read-only, no auth)
    Route::get('/settings', [\App\Http\Controllers\Api\Admin\AdminSettingsController::class, 'index'])
        ->name('api.v1.public.settings.index');

    // Product Reviews (public)
    Route::get('/catalog/{slug}/reviews', [\App\Http\Controllers\Api\Public\ReviewController::class, 'index'])->name('api.v1.public.reviews.index');

    // Validate Coupon (public)
    Route::post('/coupons/validate', [\App\Http\Controllers\Api\Public\CouponController::class, 'validate'])->name('api.v1.public.coupons.validate');
});

Route::prefix('v1/public')->middleware('auth:sanctum')->group(function () {
    Route::post('/cart/merge', [\App\Http\Controllers\Api\Public\CartController::class, 'merge'])
        ->name('api.v1.public.cart.merge');
    Route::post('/auth/logout', [\App\Http\Controllers\Api\Public\CustomerAuthController::class, 'logout'])
        ->name('api.v1.public.auth.logout');
    Route::get('/auth/me', [\App\Http\Controllers\Api\Public\CustomerAuthController::class, 'me'])
        ->name('api.v1.public.auth.me');
        
    // Customer orders
    Route::get('/customer/orders', [\App\Http\Controllers\Api\Public\CustomerOrderController::class, 'index'])
        ->name('api.v1.public.customer.orders');
    Route::get('/customer/orders/{id}', [\App\Http\Controllers\Api\Public\CustomerOrderController::class, 'show'])
        ->name('api.v1.public.customer.orders.show');

    // Customer profile
    Route::put('/auth/profile', [\App\Http\Controllers\Api\Public\CustomerAuthController::class, 'updateProfile'])
        ->name('api.v1.public.auth.profile');
    Route::put('/auth/password', [\App\Http\Controllers\Api\Public\CustomerAuthController::class, 'changePassword'])
        ->name('api.v1.public.auth.password');

    // Wishlist
    Route::get('/wishlist', [\App\Http\Controllers\Api\Public\WishlistController::class, 'index'])->name('api.v1.public.wishlist.index');
    Route::post('/wishlist', [\App\Http\Controllers\Api\Public\WishlistController::class, 'store'])->name('api.v1.public.wishlist.store');
    Route::delete('/wishlist/{productId}', [\App\Http\Controllers\Api\Public\WishlistController::class, 'destroy'])->name('api.v1.public.wishlist.destroy');

    // Submit Review
    Route::post('/catalog/{slug}/reviews', [\App\Http\Controllers\Api\Public\ReviewController::class, 'store'])->name('api.v1.public.reviews.store');
});

// Customer auth (public — no token required)
Route::prefix('v1/public/auth')->middleware('throttle:6,1')->group(function () {
    Route::post('/register', [\App\Http\Controllers\Api\Public\CustomerAuthController::class, 'register'])
        ->name('api.v1.public.auth.register');
    Route::post('/login', [\App\Http\Controllers\Api\Public\CustomerAuthController::class, 'login'])
        ->name('api.v1.public.auth.login');
});

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Api\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminProductController;
use App\Http\Controllers\Api\Admin\AdminOrderController;
use App\Http\Controllers\Api\Admin\AdminCustomerController;
use App\Http\Controllers\Api\Admin\AdminMediaController;
use App\Http\Controllers\Api\Admin\AdminPageController;
use App\Http\Controllers\Api\Admin\AdminSettingsController;
use App\Http\Controllers\Api\Admin\AdminNotificationController;
use App\Http\Controllers\Api\Admin\AdminReviewController;
use App\Http\Controllers\Api\Admin\AdminCouponController;
use App\Http\Controllers\Api\Admin\AdminAuditLogController;

// Admin login (public — no auth required)
Route::prefix('v1/admin')->group(function () {
    Route::post('/auth/login', [AdminAuthController::class, 'login'])->name('api.v1.admin.login');
});

// All admin routes automatically authenticate with admin privileges
Route::prefix('v1/admin')->middleware('admin.auto')->group(function () {

    // Auth
    Route::post('/auth/logout', [AdminAuthController::class, 'logout'])->name('api.v1.admin.logout');
    Route::get('/auth/me', [AdminAuthController::class, 'me'])->name('api.v1.admin.me');

    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('api.v1.admin.dashboard');
    Route::get('/dashboard/calendar', [AdminDashboardController::class, 'calendar'])->name('api.v1.admin.dashboard.calendar');

    // Notifications
    Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('api.v1.admin.notifications.index');
    Route::get('/notifications/unread-count', [AdminNotificationController::class, 'unreadCount'])->name('api.v1.admin.notifications.unread-count');
    Route::patch('/notifications/{id}/read', [AdminNotificationController::class, 'markAsRead'])->name('api.v1.admin.notifications.read');
    Route::post('/notifications/read-all', [AdminNotificationController::class, 'markAllAsRead'])->name('api.v1.admin.notifications.read-all');

    // Reviews
    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('api.v1.admin.reviews.index');
    Route::patch('/reviews/{id}/approve', [AdminReviewController::class, 'approve'])->name('api.v1.admin.reviews.approve');
    Route::patch('/reviews/{id}/reject', [AdminReviewController::class, 'reject'])->name('api.v1.admin.reviews.reject');
    Route::delete('/reviews/{id}', [AdminReviewController::class, 'destroy'])->name('api.v1.admin.reviews.destroy');

    // Coupons
    Route::get('/coupons', [AdminCouponController::class, 'index'])->name('api.v1.admin.coupons.index');
    Route::post('/coupons', [AdminCouponController::class, 'store'])->name('api.v1.admin.coupons.store');
    Route::get('/coupons/{id}', [AdminCouponController::class, 'show'])->name('api.v1.admin.coupons.show');
    Route::put('/coupons/{id}', [AdminCouponController::class, 'update'])->name('api.v1.admin.coupons.update');
    Route::delete('/coupons/{id}', [AdminCouponController::class, 'destroy'])->name('api.v1.admin.coupons.destroy');

    // Activity Log
    Route::get('/activity', [AdminAuditLogController::class, 'index'])->name('api.v1.admin.activity.index');

    // Products
    Route::get('/products', [AdminProductController::class, 'index'])->name('api.v1.admin.products.index');
    Route::post('/products', [AdminProductController::class, 'store'])->name('api.v1.admin.products.store');
    Route::get('/products/{id}', [AdminProductController::class, 'show'])->name('api.v1.admin.products.show');
    Route::put('/products/{id}', [AdminProductController::class, 'update'])->name('api.v1.admin.products.update');
    Route::delete('/products/{id}', [AdminProductController::class, 'destroy'])->name('api.v1.admin.products.destroy');
    Route::patch('/products/{id}/inventory', [AdminProductController::class, 'adjustInventory'])->name('api.v1.admin.products.inventory');
    Route::post('/products/{id}/media', [AdminProductController::class, 'attachMedia'])->name('api.v1.admin.products.media.attach');
    Route::delete('/products/{id}/media/{assetId}', [AdminProductController::class, 'detachMedia'])->name('api.v1.admin.products.media.detach');
    Route::patch('/products/{id}/media/reorder', [AdminProductController::class, 'reorderMedia'])->name('api.v1.admin.products.media.reorder');

    // Media
    Route::get('/media', [AdminMediaController::class, 'index'])->name('api.v1.admin.media.index');
    Route::post('/media/upload', [AdminMediaController::class, 'upload'])->name('api.v1.admin.media.upload');
    Route::patch('/media/{filename}/focal-point', [AdminMediaController::class, 'updateFocalPoint'])->name('api.v1.admin.media.focal-point');
    Route::patch('/media/{filename}/crop-mode', [AdminMediaController::class, 'updateCropMode'])->name('api.v1.admin.media.crop-mode');
    Route::delete('/media/{filename}', [AdminMediaController::class, 'destroy'])->name('api.v1.admin.media.destroy');

    // Orders
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('api.v1.admin.orders.index');
    Route::get('/orders/stats', [AdminOrderController::class, 'stats'])->name('api.v1.admin.orders.stats');
    Route::get('/orders/{id}', [AdminOrderController::class, 'show'])->name('api.v1.admin.orders.show');
    Route::patch('/orders/{id}/status', [AdminOrderController::class, 'updateStatus'])->name('api.v1.admin.orders.status');

    // Customers
    Route::get('/customers', [AdminCustomerController::class, 'index'])->name('api.v1.admin.customers.index');
    Route::get('/customers/export', [AdminCustomerController::class, 'export'])->name('api.v1.admin.customers.export');
    Route::get('/customers/{id}', [AdminCustomerController::class, 'show'])->name('api.v1.admin.customers.show');
    Route::patch('/customers/{id}/ban', [AdminCustomerController::class, 'ban'])->name('api.v1.admin.customers.ban');
    Route::patch('/customers/{id}/unban', [AdminCustomerController::class, 'unban'])->name('api.v1.admin.customers.unban');

    // Collections
    Route::get('/collections', [\App\Http\Controllers\Api\Admin\AdminCollectionController::class, 'index'])->name('api.v1.admin.collections.index');
    Route::post('/collections', [\App\Http\Controllers\Api\Admin\AdminCollectionController::class, 'store'])->name('api.v1.admin.collections.store');
    Route::get('/collections/{id}', [\App\Http\Controllers\Api\Admin\AdminCollectionController::class, 'show'])->name('api.v1.admin.collections.show');
    Route::put('/collections/{id}', [\App\Http\Controllers\Api\Admin\AdminCollectionController::class, 'update'])->name('api.v1.admin.collections.update');
    Route::delete('/collections/{id}', [\App\Http\Controllers\Api\Admin\AdminCollectionController::class, 'destroy'])->name('api.v1.admin.collections.destroy');

    // CMS Pages
    Route::get('/pages', [AdminPageController::class, 'index'])->name('api.v1.admin.pages.index');
    Route::post('/pages', [AdminPageController::class, 'store'])->name('api.v1.admin.pages.store');
    Route::get('/pages/{id}', [AdminPageController::class, 'show'])->name('api.v1.admin.pages.show');
    Route::put('/pages/{id}', [AdminPageController::class, 'update'])->name('api.v1.admin.pages.update');
    Route::delete('/pages/{id}', [AdminPageController::class, 'destroy'])->name('api.v1.admin.pages.destroy');

    // CMS Page Sections
    Route::post('/pages/{pageId}/sections', [AdminPageController::class, 'storeSection'])->name('api.v1.admin.pages.sections.store');
    Route::put('/pages/{pageId}/sections/{sectionId}', [AdminPageController::class, 'updateSection'])->name('api.v1.admin.pages.sections.update');
    Route::delete('/pages/{pageId}/sections/{sectionId}', [AdminPageController::class, 'destroySection'])->name('api.v1.admin.pages.sections.destroy');

    // Settings
    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('api.v1.admin.settings.index');
    Route::put('/settings', [AdminSettingsController::class, 'update'])->name('api.v1.admin.settings.update');
});
