<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingZone;
use App\Services\Payment\PaymobPaymentProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WebCheckoutController extends Controller
{
    /**
     * Show checkout page with user saved addresses, dynamic cart, and live shipping zones.
     */
    public function show(Request $request): View
    {
        $settings = Setting::allAsMap();
        $user = Auth::user();
        $savedAddresses = $user ? $user->addresses()->latest()->get() : collect();
        $defaultAddress = $user ? $user->defaultAddress() : null;
        $shippingZones = ShippingZone::where('is_active', true)->orderBy('sort_order')->get();

        // 1. Check direct product checkout vs session cart
        $cart = session('cart', []);
        $cartItems = [];
        $subtotal = 0;

        if ($request->filled('product_id')) {
            $directProduct = Product::with(['mediaAssets', 'variants'])->find($request->input('product_id'));
            if ($directProduct) {
                $vId = $request->input('variant_id');
                $priceMinor = $directProduct->retail_price_minor ?? 0;
                $vLabel = null;
                $variantImage = null;
                $colorHex = null;
                if ($vId) {
                    $variant = $directProduct->variants->firstWhere('id', $vId);
                    if ($variant) {
                        $vLabel = $variant->title ?: $variant->attribute_value;
                        $priceMinor = $variant->effective_price_minor;
                        $variantImage = $variant->image_url ?: ($variant->attributes_json['image_url'] ?? null);
                        $colorHex = $variant->attributes_json['color_hex'] ?? null;
                    }
                }
                $cover = $directProduct->mediaAssets->firstWhere('is_cover', true) ?? $directProduct->mediaAssets->first();
                $coverImage = $variantImage ?: ($cover?->url ?: $directProduct->image_url);

                $cartItems['direct'] = [
                    'product_id' => $directProduct->id,
                    'variant_id' => $vId,
                    'name'       => $directProduct->title,
                    'variant'    => $vLabel,
                    'color_hex'  => $colorHex,
                    'price'      => (int) round($priceMinor / 100),
                    'image'      => $coverImage,
                    'qty'        => 1,
                ];
                $subtotal = $cartItems['direct']['price'];
            }
        } elseif (!empty($cart)) {
            foreach ($cart as $key => $item) {
                // Rebuild old session entries from the database so a selected color always keeps its correct photo.
                $product = Product::with(['mediaAssets', 'variants'])->find($item['product_id'] ?? 0);
                if (! $product) continue;
                $variant = !empty($item['variant_id']) ? $product->variants->firstWhere('id', $item['variant_id']) : null;
                $cartItems[$key] = app(CartController::class)->makeCartItem($product, $variant, (int) ($item['qty'] ?? 1));
                $subtotal += $cartItems[$key]['price'] * $cartItems[$key]['qty'];
            }
            session(['cart' => $cartItems]);
        } else {
            // Default active piece preview
            $fallback = Product::active()->first();
            if ($fallback) {
                $cartItems['fallback'] = [
                    'product_id' => $fallback->id,
                    'variant_id' => null,
                    'name'       => $fallback->title,
                    'variant'    => null,
                    'price'      => (int) round(($fallback->retail_price_minor ?? 250000) / 100),
                    'image'      => $fallback->image_url ?? null,
                    'qty'        => 1,
                ];
                $subtotal = $cartItems['fallback']['price'];
            }
        }

        $defaultRate = (float) Setting::get('default_shipping_rate', 75);
        $shippingCost = $subtotal >= (float) Setting::get('free_shipping_threshold', 999999) ? 0 : $defaultRate;
        $total = $subtotal + $shippingCost;

        return view('checkout', compact('settings', 'savedAddresses', 'defaultAddress', 'shippingZones', 'cartItems', 'subtotal', 'shippingCost', 'total'));
    }

    /**
     * Place order from Web Checkout with dynamic shipping & tax calculation.
     */
    public function place(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'saved_address_id'      => 'nullable|integer|exists:addresses,id',
            'full_name'             => 'nullable|required_without:saved_address_id|string|max:150',
            'phone'                 => 'nullable|required_without:saved_address_id|string|max:30',
            'city'                  => 'nullable|required_without:saved_address_id|string|max:100',
            'street_address'        => 'nullable|required_without:saved_address_id|string|max:255',
            'state'                 => 'nullable|string|max:100',
            'postal_code'           => 'nullable|string|max:30',
            'latitude'              => 'nullable|numeric|between:-90,90',
            'longitude'             => 'nullable|numeric|between:-180,180',
            'address_label'         => 'nullable|string|max:50',
            'save_to_address_book'  => 'nullable|boolean',
            'payment_method'        => 'required|in:cod,paymob,stripe',
            'product_id'            => 'nullable|integer|exists:products,id',
            'variant_id'            => 'nullable|integer|exists:product_variants,id',
        ]);

        if ($validated['payment_method'] === 'paymob' && (blank(config('payment.paymob.secret_key')) || blank(config('payment.paymob.public_key')) || empty(array_filter(config('payment.paymob.integration_ids', []))))) {
            return back()->withInput()->withErrors(['payment_method' => 'Online card payment is not configured yet. Please choose Cash on Delivery or ask the store owner to finish Paymob setup.']);
        }

        // Resolve delivery details
        if (!empty($validated['saved_address_id']) && $user) {
            $address = Address::where('id', $validated['saved_address_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            $fullName = $address->full_name;
            $phone = $address->phone;
            $city = $address->city;
            $streetAddress = $address->street_address;
            $state = $address->state;
            $postalCode = $address->postal_code;
            $latitude = $address->latitude;
            $longitude = $address->longitude;
        } else {
            $fullName = $validated['full_name'] ?? ($user?->name ?? 'Valued Client');
            $phone = $validated['phone'] ?? '';
            $city = $validated['city'] ?? 'Cairo';
            $streetAddress = $validated['street_address'] ?? '';
            $state = $validated['state'] ?? null;
            $postalCode = $validated['postal_code'] ?? null;
            $latitude = $request->filled('latitude') ? (float)$request->input('latitude') : null;
            $longitude = $request->filled('longitude') ? (float)$request->input('longitude') : null;

            // Save address if requested
            if ($user && !empty($request->input('save_to_address_book'))) {
                $user->addresses()->create([
                    'label'          => $request->input('address_label') ?: 'Home',
                    'full_name'      => $fullName,
                    'phone'          => $phone,
                    'street_address' => $streetAddress,
                    'city'           => $city,
                    'state'          => $state,
                    'postal_code'    => $postalCode,
                    'country_code'   => 'EG',
                    'latitude'       => $latitude,
                    'longitude'      => $longitude,
                    'is_default'     => $user->addresses()->count() === 0,
                ]);
            }
        }

        $email = $user ? $user->email : preg_replace('/[^0-9]/', '', $phone) . '@client.atelier.com';

        // Find or create customer record
        $nameParts = explode(' ', $fullName, 2);
        $customer = Customer::firstOrCreate(
            ['email' => mb_strtolower(trim($email))],
            [
                'first_name' => $nameParts[0],
                'last_name'  => $nameParts[1] ?? '',
                'phone'      => $phone,
            ]
        );

        $order = DB::transaction(function () use ($validated, $user, $customer, $email, $fullName, $phone, $city, $streetAddress, $state, $postalCode, $latitude, $longitude) {
            $sessionCart = session('cart', []);
            $itemsToProcess = [];
            $subtotalMinor = 0;

            if (!empty($validated['product_id'])) {
                // Direct product checkout
                $product = Product::with(['variants', 'mediaAssets'])->find($validated['product_id']);
                if ($product) {
                    $variant = !empty($validated['variant_id']) ? $product->variants->firstWhere('id', $validated['variant_id']) : null;
                    abort_if(!empty($validated['variant_id']) && ! $variant, 422, 'Selected product option is unavailable.');
                    $attrs = $variant?->attributes_json ?? [];
                    $cover = $product->mediaAssets->firstWhere('is_cover', true) ?? $product->mediaAssets->first();
                    $itemsToProcess[] = [
                        'product'    => $product,
                        'variant_id' => $variant?->id,
                        'variant_title' => $variant?->title ?: $variant?->attribute_value,
                        'color_hex' => $attrs['color_hex'] ?? null,
                        'image' => $variant?->image_url ?: ($attrs['image_url'] ?? ($cover?->url ?: $product->image_url)),
                        'title'      => $product->title,
                        'sku'        => $product->sku,
                        'unit_minor' => $variant?->effective_price_minor ?: $product->retail_price_minor,
                        'qty'        => 1,
                        'total_minor'=> $variant?->effective_price_minor ?: $product->retail_price_minor,
                    ];
                    $subtotalMinor += $product->retail_price_minor;
                }
            } elseif (!empty($sessionCart)) {
                // Multi-item shopping bag checkout — Strictly database-verified pricing
                foreach ($sessionCart as $cartItem) {
                    $p = Product::with(['variants'])->find($cartItem['product_id']);
                    if ($p && $p->status === 'active') {
                        $qty = max(1, min(20, (int) ($cartItem['qty'] ?? 1)));
                        // Always calculate price strictly from database records to prevent price tampering
                        $unitMinor = (int) ($p->retail_price_minor ?? 0);
                        $var = null;
                        if (!empty($cartItem['variant_id'])) {
                            $var = $p->variants->firstWhere('id', $cartItem['variant_id']);
                            if ($var) $unitMinor = $var->effective_price_minor;
                        }
                        $lineTotalMinor = $unitMinor * $qty;
                        $itemsToProcess[] = [
                            'product'    => $p,
                            'variant_id' => $cartItem['variant_id'] ?? null,
                            'variant_title' => $var?->title ?: $var?->attribute_value,
                            'color_hex' => $var?->attributes_json['color_hex'] ?? ($cartItem['color_hex'] ?? null),
                            'image' => $var?->image_url ?: ($var?->attributes_json['image_url'] ?? ($cartItem['image'] ?? $p->image_url)),
                            'title'      => $p->title,
                            'sku'        => $p->sku,
                            'unit_minor' => $unitMinor,
                            'qty'        => $qty,
                            'total_minor'=> $lineTotalMinor,
                        ];
                        $subtotalMinor += $lineTotalMinor;
                    }
                }
            }

            if (empty($itemsToProcess)) {
                $fallback = Product::active()->first();
                if ($fallback) {
                    $itemsToProcess[] = [
                        'product'    => $fallback,
                        'variant_id' => null,
                        'title'      => $fallback->title,
                        'sku'        => $fallback->sku,
                        'unit_minor' => $fallback->retail_price_minor,
                        'qty'        => 1,
                        'total_minor'=> $fallback->retail_price_minor,
                    ];
                    $subtotalMinor += $fallback->retail_price_minor;
                }
            }

            // 2. Resolve dynamic shipping zone and fee
            $zone = ShippingZone::findForGovernorate($city) ?? ShippingZone::findForGovernorate($state);
            $defaultShippingMinor = (int) round(((float) Setting::get('default_shipping_rate', 75)) * 100);
            $shippingMinor = $zone ? $zone->rate_minor : $defaultShippingMinor;

            // 3. Free Shipping Threshold Check
            $freeThreshold = Setting::get('free_shipping_threshold');
            if (!empty($freeThreshold) && (float)$freeThreshold > 0) {
                $thresholdMinor = (int) round(((float)$freeThreshold) * 100);
                if ($subtotalMinor >= $thresholdMinor) {
                    $shippingMinor = 0; // Waived
                }
            }

            // 4. Tax calculation (if enabled)
            $taxMinor = 0;
            if (Setting::get('tax_enabled') === '1') {
                $taxRate = (float) Setting::get('tax_percentage', 0);
                if ($taxRate > 0) {
                    $taxMinor = (int) round($subtotalMinor * ($taxRate / 100));
                }
            }

            // 5. COD Surcharge
            $codSurchargeMinor = ($validated['payment_method'] === 'cod')
                ? (int) config('commerce.cod.surcharge_minor', 2000)
                : 0;

            $totalMinor = $subtotalMinor + $shippingMinor + $taxMinor + $codSurchargeMinor;

            $order = Order::create([
                'order_number'        => 'AT-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
                'customer_id'         => $customer->id,
                'customer_email'      => $email,
                'customer_name'       => $fullName,
                'customer_phone'      => $phone,
                'currency'            => 'EGP',
                'subtotal_minor'      => $subtotalMinor,
                'shipping_minor'      => $shippingMinor,
                'tax_minor'           => $taxMinor,
                'discount_minor'      => 0,
                'cod_surcharge_minor' => $codSurchargeMinor,
                'total_amount_minor'  => $totalMinor,
                'payment_status'      => $validated['payment_method'] === 'cod' ? 'cod_pending' : 'pending',
                'payment_method'      => $validated['payment_method'],
                'fulfillment_status'  => 'unfulfilled',
                'shipping_status'     => 'pending',
                'notes'               => "Delivery to: {$streetAddress}, {$city}. Phone: {$phone}",
                'metadata_json'       => [
                    'shipping_zone'     => $zone ? $zone->name : 'Standard Default',
                    'delivery_estimate' => 'Usually delivers from 3 to 5 business days (Maximum 4 days from order date)',
                    'shipping_address'  => [
                        'name'      => $fullName,
                        'phone'     => $phone,
                        'street'    => $streetAddress,
                        'city'      => $city,
                        'state'     => $state,
                        'postal'    => $postalCode,
                        'country'   => 'EG',
                        'latitude'  => $latitude,
                        'longitude' => $longitude,
                    ],
                ],
            ]);

            foreach ($itemsToProcess as $it) {
                OrderItem::create([
                    'order_id'           => $order->id,
                    'product_id'         => $it['product']->id,
                    'product_variant_id' => $it['variant_id'],
                    'sku'                => $it['sku'],
                    'product_title'      => $it['title'],
                    'quantity'           => $it['qty'],
                    'unit_price_minor'   => $it['unit_minor'],
                    'total_price_minor'  => $it['total_minor'],
                    // Keep a private supplier-link snapshot for fulfilment notifications.
                    'metadata_json'      => array_filter([
                        'supplier_product_url' => $it['product']->attributes_json['supplier_product_url'] ?? null,
                        'variant_title' => $it['variant_title'] ?? null,
                        'color_hex' => $it['color_hex'] ?? null,
                        'image_url' => $it['image'] ?? null,
                    ]),
                ]);

                if ($it['product']->inventory > 0) {
                    $it['product']->decrement('inventory', min($it['product']->inventory, $it['qty']));
                }
            }

            // Clear session shopping bag
            session()->forget('cart');

            // Sync CustomerAddress
            CustomerAddress::updateOrCreate(
                ['customer_id' => $customer->id, 'type' => 'shipping'],
                [
                    'first_name'     => $customer->first_name,
                    'last_name'      => $customer->last_name,
                    'address_line_1' => $streetAddress,
                    'city'           => $city,
                    'state'          => $state,
                    'postal_code'    => $postalCode,
                    'country_code'   => 'EG',
                    'phone'          => $phone,
                ]
            );

            Payment::create([
                'order_id'        => $order->id,
                'provider'        => $validated['payment_method'],
                'amount_minor'    => $totalMinor,
                'currency'        => 'EGP',
                'status'          => $validated['payment_method'] === 'cod' ? 'pending' : 'pending',
                'idempotency_key' => Str::uuid()->toString(),
            ]);

            return $order;
        });

        // Dispatch Telegram Instant Notification (Safe & Non-blocking)
        try {
            app(\App\Services\TelegramNotificationService::class)->sendOrderNotification($order);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Telegram notification error: ' . $e->getMessage());
        }

        // Dispatch WhatsApp Client Instant Confirmation (Safe & Non-blocking)
        try {
            app(\App\Services\WhatsAppNotificationService::class)->sendOrderConfirmation($order);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('WhatsApp notification error: ' . $e->getMessage());
        }

        if ($validated['payment_method'] === 'paymob') {
            try {
                $intent = app(PaymobPaymentProvider::class)->createIntent((int) $order->total_amount_minor, 'EGP', [
                    'order_id' => $order->id, 'order_number' => $order->order_number,
                    'first_name' => $customer->first_name, 'last_name' => $customer->last_name,
                    'phone' => $phone, 'email' => $email, 'street' => $streetAddress, 'city' => $city, 'country' => 'EG',
                ]);
                $order->payment()->update(['provider_transaction_id' => $intent['provider_intent_id'], 'metadata_json' => ['checkout_url' => $intent['checkout_url']]]);
                return redirect()->away($intent['checkout_url']);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Could not start Paymob checkout', ['order_id' => $order->id, 'error' => $e->getMessage()]);
                return redirect()->route('account')->with('error', "Order #{$order->order_number} is saved, but online payment could not start. Please try again or contact support.");
            }
        }

        return redirect()->route('account')->with('success', "Order #{$order->order_number} confirmed! Our concierge will contact you via WhatsApp ({$phone}) for dispatch.");
    }
}
