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
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly TaxCalculator $tax,
        private readonly ShippingProvider $shippingProvider,
        private readonly PaymentGatewayManager $paymentGatewayManager
    ) {}

    public function place(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_session_id'          => 'required|string|exists:carts,session_id',
            'email'                    => 'nullable|email|max:255',
            'phone'                    => 'nullable|string|max:30',
            'shipping_address.name'    => 'required|string|max:255',
            'shipping_address.line1'   => 'required|string|max:255',
            'shipping_address.city'    => 'required|string|max:100',
            'shipping_address.state'   => 'nullable|string|max:100',
            'shipping_address.country' => 'nullable|string|size:2',
            'shipping_address.postal'  => 'nullable|string|max:20',
            'payment_token'            => 'nullable|string',
            'payment_method'           => 'required|in:stripe,paymob,cod',
        ]);

        $sessionId = $validated['cart_session_id'];

        $lock = \Illuminate\Support\Facades\Cache::lock('checkout_' . $sessionId, 30);
        if (!$lock->get()) {
            abort(409, 'Checkout already in progress for this cart.');
        }

        try {
            $cart = Cart::where('session_id', $sessionId)
                ->with(['items.product', 'items.variant'])
                ->firstOrFail();

            abort_if($cart->items->isEmpty(), 422, 'Cart is empty.');

            $orderData = DB::transaction(function () use ($cart, $validated) {
                // ── 1. Re-validate all items and deduct inventory ───────────
                $subtotalMinor = 0;
                $lineItems = [];

                foreach ($cart->items as $item) {
                    $product = $item->product;
                    $variant = $item->variant;

                    abort_if($product->status !== 'active', 422, 'Product "' . $product->title . '" is no longer available.');

                    $inventoryModel = $variant
                        ? \App\Models\ProductVariant::where('id', $variant->id)->lockForUpdate()->first()
                        : \App\Models\Product::where('id', $product->id)->lockForUpdate()->first();

                    $available = $inventoryModel->inventory;
                    $buffer = config('commerce.inventory.oversell_buffer', 0);

                    abort_if(
                        $available < ($item->quantity - $buffer),
                        422,
                        'Insufficient stock for "' . $product->title . '".',
                    );

                    // Deduct inventory
                    $inventoryModel->inventory -= $item->quantity;
                    $inventoryModel->save();

                    $unitPrice = $variant ? $variant->retail_price_minor : $product->retail_price_minor;
                    $lineTotal = $unitPrice * $item->quantity;
                    $subtotalMinor += $lineTotal;

                    $lineItems[] = [
                        'product_id'         => $product->id,
                        'product_variant_id' => $item->product_variant_id,
                        'sku'                => $product->sku,
                        'product_title'      => $product->title . ($variant ? ' – ' . $variant->title : ''),
                        'quantity'           => $item->quantity,
                        'unit_price_minor'   => $unitPrice,
                        'total_price_minor'  => $lineTotal,
                        'metadata_json'      => array_filter([
                            'supplier_product_url' => $product->attributes_json['supplier_product_url'] ?? null,
                        ]),
                    ];
                }

                // ── 2. Calculate Tax & Shipping ─────────────────────────────
                $addr = $validated['shipping_address'];
                $addrCountry = $addr['country'] ?? 'EG';
                $addrPostal = $addr['postal'] ?? '11511';

                $taxResult = $this->tax->calculate($subtotalMinor, 'EGP', [
                    'country_code' => $addrCountry,
                    'postal_code'  => $addrPostal,
                ], $lineItems);

                $taxMinor = $taxResult['tax_minor'];
                $rates = $this->shippingProvider->getRates([], $addr, []);
                $shippingMinor = $rates[0]['amount_minor'] ?? 0;

                // ── COD Surcharge ────────────────────────────────────────────
                $paymentMethod = $validated['payment_method'];
                $codSurchargeMinor = ($paymentMethod === 'cod')
                    ? config('commerce.cod.surcharge_minor', 2000)
                    : 0;

                $totalMinor = $subtotalMinor + $taxMinor + $shippingMinor + $codSurchargeMinor;

                // ── 3. Resolve customer and create pending order ────────────
                $nameParts = explode(' ', $addr['name'], 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? '';
                $cleanPhone = preg_replace('/[^0-9]/', '', $validated['phone'] ?? '');

                $customerEmail = !empty($validated['email'])
                    ? mb_strtolower(trim($validated['email']))
                    : ($cleanPhone ? $cleanPhone . '@guest.atelier.com' : 'guest_' . Str::random(8) . '@guest.atelier.com');

                $customer = Customer::firstOrCreate(
                    ['email' => $customerEmail],
                    [
                        'first_name' => $firstName, 
                        'last_name' => $lastName,
                        'phone' => $validated['phone'] ?? null
                    ]
                );

                $order = Order::create([
                    'order_number'       => app(\App\Services\OrderService::class)->generateOrderNumber(),
                    'customer_id'        => $customer->id,
                    'customer_email'     => $customer->email,
                    'currency'           => 'EGP',
                    'subtotal_minor'     => $subtotalMinor,
                    'shipping_minor'     => $shippingMinor,
                    'tax_minor'          => $taxMinor,
                    'discount_minor'     => 0,
                    'total_amount_minor' => $totalMinor,
                    'payment_status'     => 'pending',
                    'payment_method'     => $validated['payment_method'],
                    'fulfillment_status' => 'unfulfilled',
                    'shipping_status'    => 'pending',
                    'cod_surcharge_minor'=> $codSurchargeMinor,
                ]);

                foreach ($lineItems as $line) {
                    OrderItem::create(array_merge($line, ['order_id' => $order->id]));
                    
                    InventoryMovement::create([
                        'product_id'         => $line['product_id'],
                        'product_variant_id' => $line['product_variant_id'],
                        'type'               => 'decrement',
                        'quantity'           => $line['quantity'],
                        'reference_type'     => 'order',
                        'reference_id'       => $order->id,
                        'metadata_json'      => ['reason' => 'checkout'],
                    ]);
                }

                CustomerAddress::updateOrCreate(
                    ['customer_id' => $customer->id, 'type' => 'shipping'],
                    [
                        'first_name'      => $firstName,
                        'last_name'       => $lastName,
                        'address_line_1'  => $addr['line1'],
                        'address_line_2'  => $addr['line2'] ?? null,
                        'city'            => $addr['city'],
                        'country_code'    => $addr['country'],
                        'postal_code'     => $addr['postal'],
                    ]
                );

                $idempotencyKey = Str::uuid()->toString();

                $payment = Payment::create([
                    'order_id'                => $order->id,
                    'provider'                => $validated['payment_method'],
                    'amount_minor'            => $order->total_amount_minor,
                    'currency'                => $order->currency,
                    'status'                  => 'pending',
                    'idempotency_key'         => $idempotencyKey,
                ]);

                // ── 5. Payment Gateway Intent Creation (Inside Transaction) ──
                $paymentMethod = $validated['payment_method'];
                $intentData = [];

                if ($paymentMethod === 'paymob' || $paymentMethod === 'stripe') {
                    $shipping = $validated['shipping_address'] ?? [];
                    $phone = $validated['phone'] ?? '+201000000000';
                    $metadata = [
                        'order_id'     => (string)$order->id,
                        'order_number' => $order->order_number,
                        'email'        => $order->customer_email,
                        'phone'        => $phone,
                        'first_name'   => $firstName,
                        'last_name'    => $lastName,
                        'city'         => $shipping['city'] ?? 'Cairo',
                        'country'      => $shipping['country'] ?? 'EG',
                    ];

                    try {
                        $provider = $this->paymentGatewayManager->resolve($paymentMethod);
                        $intentData = $provider->createIntent($order->total_amount_minor, $order->currency, $metadata);
                        $order->update(['provider_ref' => $intentData['provider_intent_id'] ?? null]);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error("{$paymentMethod} createIntent failed", [
                            'order_id' => $order->id,
                            'error'    => $e->getMessage(),
                        ]);
                        // Throw exception to trigger DB rollback (which restores inventory and keeps cart)
                        abort(502, 'Payment provider is currently unavailable. Please try again.');
                    }
                }

                // Delete cart ONLY after everything succeeded (transaction will commit)
                $cart->items()->delete();
                $cart->delete();

                return [$order, $payment, $intentData];
            }); // End DB Transaction

            $order = $orderData[0];
            $paymentMethod = $validated['payment_method'];
            $intentData = $orderData[2];
            $codSurchargeMinor = ($paymentMethod === 'cod')
                ? config('commerce.cod.surcharge_minor', 2000)
                : 0;

            if ($paymentMethod === 'cod') {
                $order->update(['payment_status' => 'cod_pending']);
                return response()->json([
                    'order_number'        => $order->order_number,
                    'total_minor'         => $order->total_amount_minor,
                    'cod_surcharge_minor' => $codSurchargeMinor,
                    'currency'            => $order->currency,
                    'payment_status'      => $order->payment_status,
                    'fulfillment_status'  => $order->fulfillment_status,
                ], 201);
            }

            if ($paymentMethod === 'paymob') {
                return response()->json([
                    'order_number' => $order->order_number,
                    'checkout_url' => $intentData['checkout_url'] ?? '',
                ], 201);
            }

            if ($paymentMethod === 'stripe') {
                return response()->json([
                    'order_number'  => $order->order_number,
                    'client_secret' => $intentData['client_secret'] ?? '',
                ], 201);
            }

        } finally {
            $lock->release();
        }
    }
}
