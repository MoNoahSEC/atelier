<?php

$base = "c:/Users/hp/Downloads/AI/mt new wp";

// 1. CheckoutController.php
$checkoutContent = <<<EOT
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\InventoryMovement;
use App\Contracts\TaxCalculator;
use App\Contracts\ShippingProvider;
use App\Services\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly TaxCalculator \$tax,
        private readonly ShippingProvider \$shippingProvider,
        private readonly PaymentGatewayManager \$paymentGatewayManager
    ) {}

    public function place(Request \$request): JsonResponse
    {
        \$validated = \$request->validate([
            'cart_session_id'          => 'required|string|exists:carts,session_id',
            'email'                    => 'required|email|max:255',
            'phone'                    => 'nullable|string|max:20',
            'shipping_address.name'    => 'required|string|max:255',
            'shipping_address.line1'   => 'required|string|max:255',
            'shipping_address.city'    => 'required|string|max:100',
            'shipping_address.country' => 'required|string|size:2',
            'shipping_address.postal'  => 'required|string|max:20',
            'payment_token'            => 'nullable|string',
            'payment_method'           => 'required|in:stripe,paymob,cod',
        ]);

        \$sessionId = \$validated['cart_session_id'];

        \$lock = \Illuminate\Support\Facades\Cache::lock('checkout_' . \$sessionId, 30);
        if (!\$lock->get()) {
            abort(409, 'Checkout already in progress for this cart.');
        }

        try {
            \$cart = Cart::where('session_id', \$sessionId)
                ->with(['items.product', 'items.variant'])
                ->firstOrFail();

            abort_if(\$cart->items->isEmpty(), 422, 'Cart is empty.');

            \$orderData = DB::transaction(function () use (\$cart, \$validated) {
                // ── 1. Re-validate all items and deduct inventory ───────────
                \$subtotalMinor = 0;
                \$lineItems = [];

                foreach (\$cart->items as \$item) {
                    \$product = \$item->product;
                    \$variant = \$item->variant;

                    abort_if(\$product->status !== 'active', 422, 'Product "' . \$product->title . '" is no longer available.');

                    \$inventoryModel = \$variant
                        ? \App\Models\ProductVariant::where('id', \$variant->id)->lockForUpdate()->first()
                        : \App\Models\Product::where('id', \$product->id)->lockForUpdate()->first();

                    \$available = \$inventoryModel->inventory;
                    \$buffer = config('commerce.inventory.oversell_buffer', 0);

                    abort_if(
                        \$available < (\$item->quantity - \$buffer),
                        422,
                        'Insufficient stock for "' . \$product->title . '".',
                    );

                    // Deduct inventory
                    \$inventoryModel->inventory -= \$item->quantity;
                    \$inventoryModel->save();

                    \$unitPrice = \$variant ? \$variant->retail_price_minor : \$product->retail_price_minor;
                    \$lineTotal = \$unitPrice * \$item->quantity;
                    \$subtotalMinor += \$lineTotal;

                    \$lineItems[] = [
                        'product_id'         => \$product->id,
                        'product_variant_id' => \$item->product_variant_id,
                        'sku'                => \$product->sku,
                        'product_title'      => \$product->title . (\$variant ? ' – ' . \$variant->title : ''),
                        'quantity'           => \$item->quantity,
                        'unit_price_minor'   => \$unitPrice,
                        'total_price_minor'  => \$lineTotal,
                    ];
                }

                // ── 2. Calculate Tax & Shipping ─────────────────────────────
                \$addr = \$validated['shipping_address'];
                \$taxResult = \$this->tax->calculate(\$subtotalMinor, 'EGP', [
                    'country_code' => \$addr['country'],
                    'postal_code'  => \$addr['postal'],
                ], \$lineItems);

                \$taxMinor = \$taxResult['tax_minor'];
                \$rates = \$this->shippingProvider->getRates([], \$addr, []);
                \$shippingMinor = \$rates[0]['amount_minor'] ?? 0;
                \$totalMinor = \$subtotalMinor + \$taxMinor + \$shippingMinor;

                // ── 3. Resolve customer and create pending order ────────────
                \$nameParts = explode(' ', \$addr['name'], 2);
                \$firstName = \$nameParts[0];
                \$lastName = \$nameParts[1] ?? '';

                \$customer = Customer::firstOrCreate(
                    ['email' => mb_strtolower(trim(\$validated['email']))],
                    [
                        'first_name' => \$firstName, 
                        'last_name' => \$lastName,
                        'phone' => \$validated['phone'] ?? null
                    ]
                );

                \$order = Order::create([
                    'order_number'       => app(\App\Services\OrderService::class)->generateOrderNumber(),
                    'customer_id'        => \$customer->id,
                    'customer_email'     => \$customer->email,
                    'currency'           => 'EGP',
                    'subtotal_minor'     => \$subtotalMinor,
                    'shipping_minor'     => \$shippingMinor,
                    'tax_minor'          => \$taxMinor,
                    'discount_minor'     => 0,
                    'total_amount_minor' => \$totalMinor,
                    'payment_status'     => 'pending',
                    'payment_method'     => \$validated['payment_method'],
                    'fulfillment_status' => 'unfulfilled',
                    'shipping_status'    => 'pending',
                ]);

                foreach (\$lineItems as \$line) {
                    OrderItem::create(array_merge(\$line, ['order_id' => \$order->id]));
                    
                    InventoryMovement::create([
                        'product_id'         => \$line['product_id'],
                        'product_variant_id' => \$line['product_variant_id'],
                        'type'               => 'decrement',
                        'quantity'           => \$line['quantity'],
                        'reference_type'     => 'order',
                        'reference_id'       => \$order->id,
                        'metadata_json'      => ['reason' => 'checkout'],
                    ]);
                }

                CustomerAddress::updateOrCreate(
                    ['customer_id' => \$customer->id, 'type' => 'shipping'],
                    [
                        'first_name'      => \$firstName,
                        'last_name'       => \$lastName,
                        'address_line_1'  => \$addr['line1'],
                        'address_line_2'  => \$addr['line2'] ?? null,
                        'city'            => \$addr['city'],
                        'country_code'    => \$addr['country'],
                        'postal_code'     => \$addr['postal'],
                    ]
                );

                \$idempotencyKey = Str::uuid()->toString();

                \$payment = Payment::create([
                    'order_id'                => \$order->id,
                    'provider'                => \$validated['payment_method'],
                    'amount_minor'            => \$order->total_amount_minor,
                    'currency'                => \$order->currency,
                    'status'                  => 'pending',
                    'idempotency_key'         => \$idempotencyKey,
                ]);

                \$cart->items()->delete();
                \$cart->delete();

                return [\$order, \$payment];
            }); // End DB Transaction

            \$order = \$orderData[0];
            \$payment = \$orderData[1];
            \$paymentMethod = \$validated['payment_method'];

            if (\$paymentMethod === 'cod') {
                \$order->update(['payment_status' => 'cod_pending']);
                return response()->json([
                    'order_number'       => \$order->order_number,
                    'total_minor'        => \$order->total_amount_minor,
                    'currency'           => \$order->currency,
                    'payment_status'     => \$order->payment_status,
                    'fulfillment_status' => \$order->fulfillment_status,
                ], 201);
            }

            if (\$paymentMethod === 'paymob') {
                \$provider = \$this->paymentGatewayManager->resolve('paymob');
                \$intent = \$provider->createIntent(\$order->total_amount_minor, \$order->currency, \$order->order_number);
                \$order->update(['provider_ref' => \$intent['id'] ?? null]);
                
                return response()->json([
                    'order_number'       => \$order->order_number,
                    'checkout_url'       => \$intent['checkout_url'] ?? '',
                ], 201);
            }

            if (\$paymentMethod === 'stripe') {
                \$provider = \$this->paymentGatewayManager->resolve('stripe');
                \$intent = \$provider->createIntent(\$order->total_amount_minor, \$order->currency, \$order->order_number);
                \$order->update(['provider_ref' => \$intent['id'] ?? null]);
                
                return response()->json([
                    'order_number'       => \$order->order_number,
                    'client_secret'      => \$intent['client_secret'] ?? '',
                ], 201);
            }

        } finally {
            \$lock->release();
        }
    }
}
EOT;
file_put_contents("\$base/app/Http/Controllers/Api/Public/CheckoutController.php", \$checkoutContent);

// 2. AdminProductController.php
\$adminProductContent = <<<EOT
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    public function index(Request \$request): JsonResponse
    {
        \$query = Product::with('supplier')
            ->when(\$request->search, fn(\$q) => \$q->where('title', 'like', "%{\$request->search}%")
                ->orWhere('sku', 'like', "%{\$request->search}%"))
            ->when(\$request->status, fn(\$q) => \$q->where('status', \$request->status))
            ->orderByDesc('created_at');

        \$products = \$query->paginate(\$request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => \$products->items(),
            'meta'    => [
                'total'        => \$products->total(),
                'per_page'     => \$products->perPage(),
                'current_page' => \$products->currentPage(),
                'last_page'    => \$products->lastPage(),
            ],
        ]);
    }

    public function show(int \$id): JsonResponse
    {
        \$product = Product::with(['supplier', 'variants', 'collections', 'mediaAssets'])->findOrFail(\$id);
        return response()->json(['success' => true, 'data' => \$product]);
    }

    public function store(Request \$request): JsonResponse
    {
        \$validated = \$request->validate([
            'sku'                => 'required|string|max:100|unique:products,sku',
            'title'              => 'required|string|max:255',
            'slug'               => 'nullable|string|max:255|unique:products,slug',
            'description'        => 'nullable|string',
            'image_url'          => 'nullable|string|url',
            'cost_price_minor'   => 'required|integer|min:0',
            'retail_price_minor' => 'required|integer|min:0',
            'currency'           => 'required|string|size:3',
            'inventory'          => 'required|integer|min:0',
            'status'             => 'required|in:active,inactive,archived',
            'supplier_id'        => 'nullable|exists:suppliers,id',
            'attributes_json'    => 'nullable|array',
            'collections'        => 'nullable|array',
            'collections.*'      => 'exists:collections,id',
            'variants'           => 'nullable|array',
            'variants.*.sku'     => 'required|string|max:100',
            'variants.*.title'   => 'required|string|max:255',
            'variants.*.retail_price_minor' => 'required|integer|min:0',
            'variants.*.inventory'=> 'required|integer|min:0',
        ]);

        if (empty(\$validated['slug'])) {
            \$validated['slug'] = Str::slug(\$validated['title']);
        }

        \$collections = \$validated['collections'] ?? null;
        unset(\$validated['collections']);
        
        \$variants = \$validated['variants'] ?? [];
        unset(\$validated['variants']);

        \$product = DB::transaction(function () use (\$validated, \$collections, \$variants) {
            \$p = Product::create(\$validated);
            if (\$collections !== null) {
                \$p->collections()->sync(\$collections);
            }
            foreach (\$variants as \$v) {
                \$p->variants()->create(\$v);
            }
            return \$p;
        });

        return response()->json(['success' => true, 'data' => \$product->load('collections', 'variants')], 201);
    }

    public function update(Request \$request, int \$id): JsonResponse
    {
        \$product = Product::findOrFail(\$id);

        \$validated = \$request->validate([
            'sku'                => "sometimes|string|max:100|unique:products,sku,{\$id}",
            'title'              => 'sometimes|string|max:255',
            'slug'               => "sometimes|string|max:255|unique:products,slug,{\$id}",
            'description'        => 'nullable|string',
            'image_url'          => 'nullable|string',
            'cost_price_minor'   => 'sometimes|integer|min:0',
            'retail_price_minor' => 'sometimes|integer|min:0',
            'currency'           => 'sometimes|string|size:3',
            'inventory'          => 'sometimes|integer|min:0',
            'status'             => 'sometimes|in:active,inactive,archived',
            'supplier_id'        => 'nullable|exists:suppliers,id',
            'attributes_json'    => 'nullable|array',
            'collections'        => 'nullable|array',
            'collections.*'      => 'exists:collections,id',
            'variants'           => 'nullable|array',
            'variants.*.id'      => 'nullable|integer|exists:product_variants,id',
            'variants.*.sku'     => 'required|string|max:100',
            'variants.*.title'   => 'required|string|max:255',
            'variants.*.retail_price_minor' => 'required|integer|min:0',
            'variants.*.inventory'=> 'required|integer|min:0',
        ]);

        \$collections = \$validated['collections'] ?? null;
        unset(\$validated['collections']);
        
        \$variants = \$validated['variants'] ?? null;
        unset(\$validated['variants']);

        DB::transaction(function () use (\$product, \$validated, \$collections, \$variants) {
            \$product->update(\$validated);

            if (\$collections !== null) {
                \$product->collections()->sync(\$collections);
            }

            if (\$variants !== null) {
                \$existingVariantIds = \$product->variants()->pluck('id')->toArray();
                \$newVariantIds = [];

                foreach (\$variants as \$v) {
                    if (isset(\$v['id']) && in_array(\$v['id'], \$existingVariantIds)) {
                        \$product->variants()->where('id', \$v['id'])->update(\$v);
                        \$newVariantIds[] = \$v['id'];
                    } else {
                        \$newVariant = \$product->variants()->create(\$v);
                        \$newVariantIds[] = \$newVariant->id;
                    }
                }

                \$product->variants()->whereNotIn('id', \$newVariantIds)->delete();
            }
        });

        return response()->json(['success' => true, 'data' => \$product->fresh(['collections', 'mediaAssets', 'variants'])]);
    }

    public function destroy(int \$id): JsonResponse
    {
        \$product = Product::findOrFail(\$id);
        \$product->delete();
        return response()->json(['success' => true, 'message' => 'Product deleted.']);
    }

    public function adjustInventory(Request \$request, int \$id): JsonResponse
    {
        \$product = Product::findOrFail(\$id);

        \$validated = \$request->validate([
            'quantity' => 'required|integer',
            'reason'   => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use (\$product, \$validated) {
            \$lockedProduct = \App\Models\Product::where('id', \$product->id)->lockForUpdate()->first();
            \$lockedProduct->increment('inventory', \$validated['quantity']);

            \$lockedProduct->inventoryMovements()->create([
                'type'          => \$validated['quantity'] > 0 ? 'restock' : 'adjustment',
                'quantity'      => \$validated['quantity'],
                'reference_type' => 'admin',
                'metadata_json'  => ['reason' => \$validated['reason'] ?? 'Manual admin adjustment'],
            ]);
        });

        return response()->json(['success' => true, 'data' => ['inventory' => \$product->fresh()->inventory]]);
    }
    
    public function attachMedia(Request \$request, int \$id): JsonResponse
    {
        \$product = Product::findOrFail(\$id);
        \$validated = \$request->validate([
            'media_asset_id' => 'required|exists:media_assets,id',
            'group'          => 'nullable|string|max:50',
        ]);

        // Check if already attached
        if (!\$product->mediaAssets()->where('media_asset_id', \$validated['media_asset_id'])->exists()) {
            \$product->mediaAssets()->attach(\$validated['media_asset_id'], [
                'group'      => \$validated['group'] ?? 'gallery',
                'sort_order' => (int) \$product->mediaAssets()->withPivot('sort_order')->max('mediables.sort_order') + 1,
            ]);
        }

        return response()->json(['success' => true, 'data' => \$product->fresh('mediaAssets')]);
    }

    public function detachMedia(int \$id, int \$assetId): JsonResponse
    {
        \$product = Product::findOrFail(\$id);
        \$product->mediaAssets()->detach(\$assetId);

        return response()->json(['success' => true, 'data' => \$product->fresh('mediaAssets')]);
    }

    public function reorderMedia(Request \$request, int \$id): JsonResponse
    {
        \$product = Product::findOrFail(\$id);
        \$validated = \$request->validate([
            'ordered_ids'   => 'required|array',
            'ordered_ids.*' => 'integer|exists:media_assets,id',
        ]);

        foreach (\$validated['ordered_ids'] as \$index => \$assetId) {
            \$product->mediaAssets()->updateExistingPivot(\$assetId, ['sort_order' => \$index]);
        }

        return response()->json(['success' => true, 'data' => \$product->fresh('mediaAssets')]);
    }
}
EOT;
file_put_contents("\$base/app/Http/Controllers/Api/Admin/AdminProductController.php", \$adminProductContent);

// 3. Cart.php
\$cartContent = <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected \$fillable = [
        'session_id',
        'user_id',
        'currency',
    ];

    public function items()
    {
        return \$this->hasMany(CartItem::class);
    }

    public function user()
    {
        return \$this->belongsTo(Customer::class, 'user_id');
    }
}
EOT;
file_put_contents("\$base/app/Models/Cart.php", \$cartContent);

// 4. DatabaseSeeder.php
\$seederContent = <<<EOT
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        \$this->call([
            AdminUserSeeder::class,
            CollectionSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
EOT;
file_put_contents("\$base/database/seeders/DatabaseSeeder.php", \$seederContent);

// 5. CustomerOrderController.php
\$customerOrderContent = <<<EOT
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    public function index(Request \$request): JsonResponse
    {
        \$customer = \$request->user();
        \$orders = Order::where('customer_id', \$customer->id)
            ->with(['items', 'payment'])
            ->orderByDesc('created_at')
            ->paginate(10);
            
        return response()->json(\$orders);
    }

    public function show(Request \$request, int \$id): JsonResponse
    {
        \$customer = \$request->user();
        \$order = Order::where('customer_id', \$customer->id)
            ->with(['items', 'payment']) // shipment relation not strictly required to exist, load what exists
            ->findOrFail(\$id);
            
        return response()->json(\$order);
    }
}
EOT;
file_put_contents("\$base/app/Http/Controllers/Api/Public/CustomerOrderController.php", \$customerOrderContent);

// 6. CustomerAuthController.php
\$customerAuthContent = file_get_contents("\$base/app/Http/Controllers/Api/Public/CustomerAuthController.php");
\$customerAuthAdditions = <<<EOT

    public function updateProfile(Request \$request): JsonResponse
    {
        \$customer = \$request->user();
        
        \$validated = \$request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name'  => 'sometimes|string|max:100',
            'phone'      => 'nullable|string|max:20',
        ]);
        
        \$customer->update(\$validated);
        
        return response()->json([
            'success' => true,
            'customer' => [
                'id'         => \$customer->id,
                'first_name' => \$customer->first_name,
                'last_name'  => \$customer->last_name,
                'email'      => \$customer->email,
                'phone'      => \$customer->phone,
            ]
        ]);
    }

    public function changePassword(Request \$request): JsonResponse
    {
        \$customer = \$request->user();
        
        \$validated = \$request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);
        
        if (!\Illuminate\Support\Facades\Hash::check(\$validated['current_password'], \$customer->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }
        
        \$customer->update([
            'password' => \Illuminate\Support\Facades\Hash::make(\$validated['new_password']),
        ]);
        
        return response()->json(['success' => true, 'message' => 'Password updated successfully.']);
    }
}
EOT;
\$customerAuthContent = preg_replace('/}\s*$/', \$customerAuthAdditions, \$customerAuthContent);
file_put_contents("\$base/app/Http/Controllers/Api/Public/CustomerAuthController.php", \$customerAuthContent);

// 7. CatalogController.php
\$catalogContent = <<<EOT
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Http\Resources\Public\ProductResource;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /**
     * Retrieve active products for the storefront.
     */
    public function index(Request \$request)
    {
        \$query = Product::active()->with(['mediaAssets', 'collections']);

        if (\$request->filled('collection') && \$request->collection !== 'all') {
            \$query->whereHas('collections', function (\$q) use (\$request) {
                \$q->where('slug', \$request->collection);
            });
        }
        
        if (\$request->filled('search')) {
            \$query->where('title', 'LIKE', '%' . \$request->search . '%');
        }
        
        if (\$request->filled('min_price')) {
            \$query->where('retail_price_minor', '>=', \$request->min_price);
        }
        
        if (\$request->filled('max_price')) {
            \$query->where('retail_price_minor', '<=', \$request->max_price);
        }
        
        \$sort = \$request->input('sort', 'newest');
        switch (\$sort) {
            case 'price_asc':
                \$query->orderBy('retail_price_minor', 'asc');
                break;
            case 'price_desc':
                \$query->orderBy('retail_price_minor', 'desc');
                break;
            case 'title_asc':
                \$query->orderBy('title', 'asc');
                break;
            case 'newest':
            default:
                \$query->orderBy('id', 'desc');
                break;
        }

        \$products = \$query->paginate(20);

        return ProductResource::collection(\$products);
    }

    /**
     * Retrieve a single product by slug.
     */
    public function show(string \$slug)
    {
        \$product = Product::active()
            ->with(['variants', 'mediaAssets', 'collections'])
            ->where('slug', \$slug)
            ->firstOrFail();

        return new ProductResource(\$product);
    }
}
EOT;
file_put_contents("\$base/app/Http/Controllers/Api/Public/CatalogController.php", \$catalogContent);

// 8. api.php
\$apiContent = file_get_contents("\$base/routes/api.php");

// Add Webhooks and payment-methods after automated block (or somewhere at the top)
\$apiMod1 = <<<EOT
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
EOT;
\$apiContent = str_replace("Route::prefix('v1/public')->group(function () {", \$apiMod1, \$apiContent);

// Add customer orders & profile to auth:sanctum group
\$apiMod2 = <<<EOT
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
});
EOT;
// Match the exact block to replace
\$apiContent = preg_replace("/Route::prefix\('v1\/public'\)->middleware\('auth:sanctum'\)->group\(function \(\) \{.*?\}\);/s", \$apiMod2, \$apiContent);

// Add rate limiting to auth routes
\$apiMod3 = <<<EOT
// Customer auth (public — no token required)
Route::prefix('v1/public/auth')->middleware('throttle:6,1')->group(function () {
EOT;
\$apiContent = str_replace("Route::prefix('v1/public/auth')->group(function () {", \$apiMod3, \$apiContent);

file_put_contents("\$base/routes/api.php", \$apiContent);

echo "Done modifying files.\n";

// Delete debug scripts
@unlink("\$base/check_admin_db.php");
@unlink("\$base/check_db.php");
@unlink("\$base/check_products.php");
@unlink("\$base/create_customer.php");
@unlink("\$base/diag_auth.php");
@unlink("\$base/test_login.php");

echo "Deleted debug scripts.\n";
?>
