<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

use App\Models\Product;
use App\Models\Collection;
use App\Models\Setting;
use App\Models\User;
use App\Models\Order;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\AdminImageController;
use App\Http\Controllers\WebCheckoutController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\GoogleAuthController;

Route::post('/presence/ping', [\App\Http\Controllers\PresenceController::class, 'ping'])->name('presence.ping');

/*
|--------------------------------------------------------------------------
| Storefront Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    $products = Product::active()
        ->with(['variants', 'mediaAssets', 'collections'])
        ->orderBy('sort_order')
        ->orderBy('id', 'desc')
        ->get();

    $collections = Collection::where('status', 'active')
        ->with(['products' => function($q) {
            $q->where('status', 'active')->with(['mediaAssets', 'variants'])->take(8);
        }])
        ->orderBy('sort_order')
        ->get();

    $settings = Setting::allAsMap();
    $featuredProduct = $products->firstWhere('id', (int) ($settings['featured_bestseller_product_id'] ?? 0)) ?? $products->first();

    \App\Models\PageView::track('homepage');

    return view('home', compact('products', 'collections', 'settings', 'featuredProduct'));
})->name('home');

Route::get('/collections/{slug?}', function (\Illuminate\Http\Request $request, string $slug = 'all') {
    $collections = Collection::where('status', 'active')->orderBy('sort_order')->get();
    $collection = $slug !== 'all' ? $collections->firstWhere('slug', $slug) : null;

    $query = Product::active()->with(['variants', 'mediaAssets', 'collections']);
    if ($slug !== 'all' && $collection) {
        $query->whereHas('collections', fn($q) => $q->where('collections.id', $collection->id));
    }

    // Fast first pass: direct title, material and description matches.
    if ($request && $request->filled('q')) {
        $rawSearch = trim((string)$request->input('q'));
        $normalized = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', '', $rawSearch), 'UTF-8');
        $tokens = array_filter(explode(' ', $normalized));

        $query->where(function ($q) use ($rawSearch, $tokens) {
            $q->where('title', 'like', "%{$rawSearch}%")
              ->orWhere('sku', 'like', "%{$rawSearch}%")
              ->orWhere('description', 'like', "%{$rawSearch}%")
              ->orWhere('material', 'like', "%{$rawSearch}%");

            foreach ($tokens as $token) {
                if (mb_strlen($token, 'UTF-8') >= 2) {
                    $q->orWhere('title', 'like', "%{$token}%")
                      ->orWhere('description', 'like', "%{$token}%");
                }
            }
        });
    }

    $products = $query->orderBy('id', 'desc')->get();

    // Friendly fallback for typos, missing spaces and Arabic letter variations.
    // It runs only when the fast database search has no result, so normal searches stay cheap.
    if ($request && $request->filled('q') && $products->isEmpty()) {
        $normalizeForSearch = static function (string $value): string {
            $value = mb_strtolower($value, 'UTF-8');
            $value = preg_replace('/[ًٌٍَُِّْـ]/u', '', $value);
            $value = str_replace(['أ', 'إ', 'آ', 'ى', 'ة'], ['ا', 'ا', 'ا', 'ي', 'ه'], $value);
            return preg_replace('/[^\p{L}\p{N}]+/u', '', $value);
        };
        $needle = $normalizeForSearch((string) $request->input('q'));

        if (mb_strlen($needle, 'UTF-8') >= 2) {
            $fallbackQuery = Product::active()->with(['variants', 'mediaAssets', 'collections']);
            if ($slug !== 'all' && $collection) {
                $fallbackQuery->whereHas('collections', fn ($q) => $q->where('collections.id', $collection->id));
            }
            $needles = array_values(array_filter(preg_split('/\\s+/u', str_replace(['-', '_'], ' ', (string) $request->input('q'))), fn ($token) => mb_strlen($normalizeForSearch($token), 'UTF-8') >= 2));
            $products = $fallbackQuery->get()
                ->map(function ($product) use ($normalizeForSearch, $needle, $needles) {
                    $haystack = $normalizeForSearch(implode(' ', [
                        $product->title ?? '', $product->material ?? '', $product->sku ?? '', strip_tags($product->description ?? ''),
                    ]));
                    $words = array_filter(preg_split('/[^\\p{L}\\p{N}]+/u', mb_strtolower(implode(' ', [$product->title, $product->material, $product->sku]), 'UTF-8')));
                    $scores = collect($needles)->map(function ($token) use ($words, $normalizeForSearch, $haystack): float {
                        $normalizedToken = $normalizeForSearch($token);
                        if (str_contains($haystack, $normalizedToken)) return 100.0;
                        $best = collect($words)->map(fn ($word) => levenshtein($normalizedToken, $normalizeForSearch($word)))->min() ?? 99;
                        return max(0, 100 - ($best * 34));
                    });
                    $score = str_contains($haystack, $needle) ? 100 : (float) $scores->avg();
                    $product->search_score = $score;
                    return $product;
                })
                ->filter(fn ($product) => $product->search_score >= 40)
                ->sortByDesc('search_score')
                ->values();
        }
    }

    if ($request && ($request->query('format') === 'json' || $request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest')) {
        return response()->json([
            'count' => $products->count(),
            'products' => $products->take(8)->map(function ($p) {
                $cover = $p->mediaAssets->firstWhere('is_cover', true) ?? $p->mediaAssets->first();
                $img = $cover && $cover->disk_path
                    ? asset('storage/' . ltrim($cover->disk_path, '/'))
                    : ($p->image_url ? (str_starts_with($p->image_url, 'http') ? $p->image_url : url($p->image_url)) : '');
                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'slug' => $p->slug,
                    'price' => $p->retail_price_minor ? number_format($p->retail_price_minor / 100, 0) . ' EGP' : '',
                    'image' => $img,
                    'url' => route('products.show', ['slug' => $p->slug]),
                ];
            }),
        ]);
    }

    $settings = Setting::allAsMap();
    
    $collectionTitle = $collection ? $collection->title : ($slug === 'all' ? 'All Archive Pieces' : ucwords(str_replace('-', ' ', $slug)));
    $collectionDescription = $collection ? $collection->description : 'Bespoke RFID wallets, smart magnetic cardholders, and luxury leather EDC accessories.';
    $currentSlug = $slug;

    \App\Models\PageView::track('collection', $collection?->id);

    return view('collections', compact('products', 'collections', 'settings', 'collectionTitle', 'collectionDescription', 'collection', 'currentSlug'));
})->name('collections.show');

Route::get('/products/{slug}', function (string $slug) {
    $product = Product::active()
        // Variants expose an effective price that may fall back to their parent product.
        // Eager-load it so the product page never attempts a disabled lazy load.
        ->with(['variants.product', 'mediaAssets', 'collections'])
        ->where('slug', $slug)
        ->firstOrFail();
    $settings = Setting::allAsMap();

    \App\Models\PageView::track('product', $product->id);

    // 1. Co-occurrence: Frequently bought together in past orders
    $orderIdsWithThisProduct = \App\Models\OrderItem::where('product_id', $product->id)->pluck('order_id');
    $coOccurringProductIds = collect();
    if ($orderIdsWithThisProduct->isNotEmpty()) {
        $coOccurringProductIds = \App\Models\OrderItem::whereIn('order_id', $orderIdsWithThisProduct)
            ->where('product_id', '!=', $product->id)
            ->selectRaw('product_id, COUNT(*) as freq')
            ->groupBy('product_id')
            ->orderByDesc('freq')
            ->take(4)
            ->pluck('product_id');
    }

    $relatedCoOccurring = collect();
    if ($coOccurringProductIds->isNotEmpty()) {
        $relatedCoOccurring = Product::active()
            ->with(['variants', 'mediaAssets'])
            ->whereIn('id', $coOccurringProductIds)
            ->get();
    }

    // 2. Same collection fallback
    $collectionIds = $product->collections->pluck('id');
    $relatedFromSameCollection = collect();
    $needed = 4 - $relatedCoOccurring->count();
    $excludeIds = $relatedCoOccurring->pluck('id')->push($product->id);

    if ($needed > 0 && $collectionIds->isNotEmpty()) {
        $relatedFromSameCollection = Product::active()
            ->with(['variants', 'mediaAssets'])
            ->whereHas('collections', fn($q) => $q->whereIn('collections.id', $collectionIds))
            ->whereNotIn('id', $excludeIds)
            ->orderBy('id', 'desc')
            ->take($needed)
            ->get();
    }

    // 3. Global newest fallback if still need slots
    $currentRelated = $relatedCoOccurring->merge($relatedFromSameCollection);
    $stillNeeded = 4 - $currentRelated->count();
    $allExcluded = $currentRelated->pluck('id')->push($product->id);

    $relatedFiller = $stillNeeded > 0
        ? Product::active()
            ->with(['variants', 'mediaAssets'])
            ->whereNotIn('id', $allExcluded)
            ->orderBy('id', 'desc')
            ->take($stillNeeded)
            ->get()
        : collect();

    $relatedProducts = $currentRelated->merge($relatedFiller);

    return view('product', compact('product', 'settings', 'relatedProducts'));
})->name('products.show');

// Checkout
Route::get('/checkout', [WebCheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout/place', [WebCheckoutController::class, 'place'])
    ->middleware('throttle:15,1')
    ->name('checkout.place');

/*
|--------------------------------------------------------------------------
| Shopping Cart (Session-Based Multi-Item)
|--------------------------------------------------------------------------
*/
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

// Public Track Order
Route::get('/track-order', function () {
    $settings = Setting::allAsMap();
    return view('track-order', compact('settings'));
})->name('track.order');

// CMS / Policy Pages
Route::get('/pages/{slug}', function (string $slug) {
    $settings = Setting::allAsMap();
    $storeName = $settings['store_name'] ?? ($settings['storeName'] ?? 'ATELIER');
    $storeEmail = $settings['store_email'] ?? 'hello@atelier.eg';

    $pages = [
        'privacy' => [
            'title' => 'Privacy Policy & Client Discretion',
            'content' => "
<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin-bottom:.75rem;'>1. Data We Collect</h2>
<p>When you place an order or create an account with {$storeName}, we collect your name, email address, phone number, and delivery address (including optional GPS coordinates you voluntarily pin on our map). We do not collect payment card data — all payment processing is handled by secure certified third-party gateways (Paymob / Stripe).</p>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>2. How We Use Your Data</h2>
<p>Your data is used exclusively to:</p>
<ul style='list-style:disc;padding-left:1.2rem;'>
<li>Process and dispatch your order to the correct address.</li>
<li>Send order confirmation and status updates via WhatsApp and email.</li>
<li>Improve our service and personalise your experience on future visits.</li>
</ul>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>3. Data Sharing</h2>
<p>We do <strong>not</strong> sell, rent, or share your personal data with any third party for marketing purposes. Delivery address data is shared only with our contracted courier partners strictly for the purpose of delivering your order.</p>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>4. Cookies & Analytics</h2>
<p>We use essential session cookies to maintain your shopping cart and authentication state. We may use anonymised analytics to understand how visitors interact with our platform — no personally identifiable data is shared with analytics providers.</p>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>5. Your Rights</h2>
<p>You have the right to request access to, correction of, or deletion of your personal data at any time. Contact us at <a href='mailto:{$storeEmail}' style='font-weight:700;text-decoration:underline;'>{$storeEmail}</a> and we will respond within 5 business days.</p>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>6. Security</h2>
<p>All data is stored on encrypted servers. Access to client records is strictly limited to authorised {$storeName} personnel only. We continually update our security practices to protect your information.</p>

<p style='margin-top:1.5rem;font-size:.8rem;color:#555;'>Last updated: September 2026. For questions, contact <a href='mailto:{$storeEmail}'>{$storeEmail}</a>.</p>
",
        ],

        'terms' => [
            'title' => 'Terms of Service & Bespoke Protocol',
            'content' => "
<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin-bottom:.75rem;'>1. Acceptance of Terms</h2>
<p>By accessing and placing an order through <strong>{$storeName}</strong>, you confirm that you have read, understood, and agree to be bound by these Terms of Service. If you do not agree to any part of these terms, please refrain from using our services.</p>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>2. Products & Descriptions</h2>
<p>All products offered on {$storeName} are handcrafted luxury accessories. We take every measure to accurately describe and photograph our pieces. Minor variations in leather grain, texture, or hardware finish are inherent characteristics of genuine full-grain leather and are not considered defects.</p>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>3. Pricing & Payment</h2>
<p>All prices are displayed in Egyptian Pounds (EGP) and are inclusive of applicable taxes unless otherwise stated. We reserve the right to modify prices at any time. Orders are confirmed at the price displayed at the time of purchase.</p>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>4. Order Confirmation & Cancellation</h2>
<p>Upon placing your order, you will receive an automated confirmation via WhatsApp and email. Orders may be cancelled within 2 hours of placement by contacting us at <a href='mailto:{$storeEmail}' style='font-weight:700;'>{$storeEmail}</a>. Once dispatched, orders cannot be cancelled.</p>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>5. Intellectual Property & Copyright</h2>
<p>All content published on the {$storeName} platform — including but not limited to product photographs, brand identity, logotype, descriptive text, and design layouts — is the exclusive intellectual property of {$storeName} and is protected under Egyptian and international copyright law.</p>
<p style='margin-top:.5rem;'>Reproduction, redistribution, or commercial use of any content without prior written consent from {$storeName} is strictly prohibited and will be subject to legal action.</p>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>6. Limitation of Liability</h2>
<p>{$storeName} is not liable for indirect, incidental, or consequential damages arising from the use of our products or services beyond the value of the purchased item.</p>

<p style='margin-top:1.5rem;font-size:.8rem;color:#555;'>Last updated: September 2026. Governing law: Arab Republic of Egypt. Contact: <a href='mailto:{$storeEmail}'>{$storeEmail}</a>.</p>
",
        ],

        'shipping' => [
            'title' => 'Shipping & Delivery Policy',
            'content' => "
<div style='background:#000;color:#fff;padding:1rem 1.25rem;margin-bottom:1.5rem;'>
    <p style='font-family:Cinzel,serif;font-size:.8rem;font-weight:800;text-transform:uppercase;letter-spacing:.18em;margin:0;'>⏱ Standard Delivery: 3 to 5 Business Days</p>
    <p style='font-size:.75rem;margin:.35rem 0 0;opacity:.7;'>Maximum dispatch delay from order date: 4 days</p>
</div>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin-bottom:.75rem;'>Coverage Area</h2>
<p>We deliver across <strong>all Egyptian governorates</strong> — Cairo, Giza, Alexandria, New Cairo, Sheikh Zayed, 6th of October, Maadi, Zamalek, Heliopolis, Mansoura, Tanta, Assiut, Luxor, Aswan, Red Sea, North Coast, and all other regions.</p>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>Delivery Timeframe</h2>
<ul style='list-style:disc;padding-left:1.2rem;'>
<li><strong>Greater Cairo & Giza:</strong> Usually 1–3 business days.</li>
<li><strong>Alexandria & Delta:</strong> Usually 2–4 business days.</li>
<li><strong>Upper Egypt & Remote Governorates:</strong> 3–5 business days.</li>
<li><strong>Maximum delay from order date:</strong> 4 calendar days in all cases.</li>
</ul>
<p style='margin-top:.75rem;'>Usually delivers from 3 to 5 days. Our team will contact you via WhatsApp or phone before dispatch to confirm timing.</p>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>Shipping Fees</h2>
<p>Shipping fees are calculated based on your delivery governorate and displayed at checkout. Free shipping may apply on orders above a specific threshold as indicated at time of purchase.</p>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>Cash on Delivery (COD)</h2>
<p>You may inspect the product packaging before paying. Our courier will wait while you verify the sealed ATELIER box. Once the seal is broken and payment is made, the order is considered accepted.</p>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>Packaging</h2>
<p>Every {$storeName} order is hand-wrapped in a signature ATELIER luxury box with tissue paper and ribbon. Packaging is part of the ATELIER experience — do not discard before confirming you are satisfied with your piece.</p>

<p style='margin-top:1.5rem;font-size:.8rem;color:#555;'>Questions? Contact us at <a href='mailto:{$storeEmail}' style='font-weight:700;'>{$storeEmail}</a></p>
",
        ],

        'copyright' => [
            'title' => 'Copyright & Intellectual Property',
            'content' => "
<p>All intellectual property on this platform — including but not limited to the <strong>{$storeName}</strong> brand name, logotype, product photography, descriptive copy, UI design, and all digital assets — is owned exclusively by {$storeName} and protected under the intellectual property laws of the Arab Republic of Egypt and international copyright treaties.</p>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>Prohibited Uses</h2>
<ul style='list-style:disc;padding-left:1.2rem;'>
<li>Reproducing or copying any image, logo, text, or design element without written consent.</li>
<li>Using {$storeName} branding in any commercial context.</li>
<li>Claiming authorship of any content published on this platform.</li>
</ul>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>Contact for Licensing</h2>
<p>For licensing inquiries or copyright concerns, contact us at <a href='mailto:{$storeEmail}' style='font-weight:700;text-decoration:underline;'>{$storeEmail}</a>.</p>
<p style='margin-top:1.5rem;font-size:.8rem;color:#555;'>© {{ date('Y') }} {$storeName}. All rights reserved.</p>
",
        ],

        'returns' => [
            'title' => 'Exchange & Craftsmanship Guarantee',
            'content' => "
<p>At {$storeName}, every piece undergoes a meticulous quality inspection before dispatch. We stand behind the craftsmanship of every item we produce. In the rare event that you receive a defective or incorrectly dispatched item, please contact us within <strong>48 hours of delivery</strong>.</p>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>Eligibility</h2>
<ul style='list-style:disc;padding-left:1.2rem;'>
<li>Items must be in original, unworn condition with original packaging intact.</li>
<li>Exchange request must be made within 48 hours of receiving your order.</li>
<li>Items showing signs of use, alteration, or damage will not be accepted.</li>
</ul>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>Process</h2>
<p>Contact us at <a href='mailto:{$storeEmail}' style='font-weight:700;'>{$storeEmail}</a> or via WhatsApp with your order number and photos of the issue. We will arrange a courier pickup and replacement dispatch at no extra cost.</p>
",
        ],

        'faq' => [
            'title' => 'Frequently Asked Questions',
            'content' => "
<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin-bottom:.75rem;'>How long does delivery take?</h2>
<p>Usually from 3 to 5 business days. Maximum delay from your order date is 4 days. Our team will contact you via WhatsApp before dispatch to confirm the exact timing.</p>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>Do you ship across all of Egypt?</h2>
<p>Yes — we deliver to all Egyptian governorates via our certified courier network.</p>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>Can I pay cash on delivery?</h2>
<p>Absolutely. Cash on Delivery (COD) is our primary payment method. You may inspect the sealed box before paying. We also accept Paymob, Visa, Mastercard, and ValU.</p>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>Are the materials authentic?</h2>
<p>Yes. All {$storeName} leather products use 100% full-grain Italian Tuscan leather. Hardware is solid titanium or stainless steel. We never use synthetic materials.</p>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>How do I track my order?</h2>
<p>Visit the <a href='/track-order' style='font-weight:700;text-decoration:underline;'>Track Order</a> page and enter your order number. You will also receive WhatsApp updates automatically.</p>

<h2 style='font-family:Cinzel,serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.12em;margin:.75rem 0;'>How do I contact you?</h2>
<p>Email us at <a href='mailto:{$storeEmail}' style='font-weight:700;'>{$storeEmail}</a> — we respond within 24 hours, 7 days a week.</p>
",
        ],
    ];

    if (!isset($pages[$slug])) {
        abort(404);
    }

    $pageTitle   = $pages[$slug]['title'];
    $pageContent = $pages[$slug]['content'];

    return view('page', compact('pageTitle', 'pageContent', 'settings'));
})->name('pages.show');


/*
|--------------------------------------------------------------------------
| Client Account & Authentication
|--------------------------------------------------------------------------
*/

Route::get('/account', function () {
    $settings = Setting::allAsMap();
    $user = Auth::user();
    $addresses = $user ? $user->addresses()->latest()->get() : collect();
    $orders = $user ? $user->orders()->with(['items'])->latest()->take(20)->get() : collect();
    return view('account', compact('settings', 'addresses', 'orders'));
})->name('account');

Route::post('/account/login', [AuthController::class, 'login'])->name('client.login');
Route::post('/account/register', [AuthController::class, 'register'])->name('client.register');
Route::post('/account/logout', [AuthController::class, 'logout'])->name('client.logout');

// Google OAuth Sign-In
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::get('/auth/google/setup', [GoogleAuthController::class, 'setup'])->name('auth.google.setup');

/*
|--------------------------------------------------------------------------
| Secure Password Reset Flow (User-Initiated & Admin-Triggered)
|--------------------------------------------------------------------------
*/

Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');

/*
|--------------------------------------------------------------------------
| Authenticated Client Actions (Address Book & Order History)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    // Address Book Management
    Route::post('/account/addresses', [AddressController::class, 'store'])->name('addresses.store');
    Route::put('/account/addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
    Route::delete('/account/addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
    Route::patch('/account/addresses/{address}/set-default', [AddressController::class, 'setDefault'])->name('addresses.set-default');

    // Order View with Server-Side Policy Authorization
    Route::get('/account/orders/{order}', function (Order $order) {
        \Illuminate\Support\Facades\Gate::authorize('view', $order);
        $settings = Setting::allAsMap();
        return view('orders.show', compact('order', 'settings'));
    })->name('client.orders.show');
});

/*
|--------------------------------------------------------------------------
| Admin Suite (Authentication & Protected Console)
|--------------------------------------------------------------------------
*/

// Public Admin Login & Authentication
Route::get('/admin/login', [\App\Http\Controllers\AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [\App\Http\Controllers\AdminAuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('admin.login.submit');

// Protected Admin Routes Group (Requires valid user with is_admin = true)
Route::middleware(['admin'])->group(function () {
    
    Route::post('/admin/logout', [\App\Http\Controllers\AdminAuthController::class, 'logout'])->name('admin.logout');

    Route::get('/admin', function () {
        return redirect()->route('admin.dashboard');
    })->name('admin.root');

    Route::get('/admin/quick-edit', function () {
        $products = Product::with(['variants', 'mediaAssets', 'collections'])->orderBy('id', 'desc')->get();
        $collections = Collection::orderBy('sort_order')->get();
        $settings = Setting::allAsMap();
        $users = User::with('addresses')->orderBy('id', 'desc')->get();
        return view('admin.quick-edit', compact('products', 'collections', 'settings', 'users'));
    })->name('admin.quick-edit');

    // Product & Gallery Image Click-to-Replace Endpoints
    Route::post('/admin/products/{id}/replace-image', [AdminImageController::class, 'replaceProductImage'])->name('admin.products.replace-image');
    Route::post('/admin/products/{id}/upload-gallery', [AdminImageController::class, 'uploadGalleryImage'])->name('admin.products.upload-gallery');
    Route::post('/admin/media/{id}/replace', [AdminImageController::class, 'replaceMediaAsset'])->name('admin.media.replace');
    Route::post('/admin/settings/replace-banner', [AdminImageController::class, 'replacePromoBanner'])->name('admin.settings.replace-banner');
    Route::post('/admin/collections/{id}/replace-image', [AdminImageController::class, 'replaceCollectionImage'])->name('admin.collections.replace-image');
    Route::post('/admin/settings/replace-portal-bg', [AdminImageController::class, 'replacePortalBackground'])->name('admin.settings.replace-portal-bg');
    Route::post('/admin/settings/reset-portal-bg', function() {
        \App\Models\Setting::set('account_portal_background_url', '');
        return back()->with('success', 'Portal background reset to minimalist studio aesthetic.');
    })->name('admin.settings.reset-portal-bg');
    Route::post('/admin/settings/portal-overlay', function(\Illuminate\Http\Request $request) {
        \App\Models\Setting::set('account_portal_overlay', $request->input('account_portal_overlay', 'medium'));
        return back()->with('success', 'Portal overlay style saved successfully.');
    })->name('admin.settings.portal-overlay');

    // Banner text and dimensions updater
    Route::post('/admin/settings/update-banner-text', function(Illuminate\Http\Request $request) {
        $keys = [
            'homepage_banner_title',
            'homepage_banner_subtitle',
            'homepage_banner_link',
            'homepage_banner_height',
            'homepage_banner_position',
            'homepage_banner_fit',
            'homepage_banner_zoom',
            'homepage_banner_overlay',
        ];
        foreach ($keys as $k) {
            if ($request->has($k)) {
                App\Models\Setting::set($k, $request->input($k));
            }
        }
        return back()->with('success', 'Banner dimensions, positioning, and text updated successfully!');
    })->name('admin.settings.update-banner-text');

    // Admin Triggered Password Reset
    Route::post('/admin/users/{id}/send-password-reset', [PasswordResetController::class, 'adminSendResetLink'])->name('admin.users.send-password-reset');

    // Google OAuth Admin Accounts Management
    Route::post('/admin/settings/google-admins', function(Illuminate\Http\Request $request) {
        $emails = trim((string) $request->input('admin_google_emails', ''));
        \App\Models\Setting::set('admin_google_emails', $emails);

        $emailList = array_filter(array_map('trim', explode(',', str_replace(["\r\n", "\n", ";"], ',', mb_strtolower($emails)))));
        if (!empty($emailList)) {
            \App\Models\User::whereIn('email', $emailList)->update(['is_admin' => true, 'admin_role' => 'super_admin']);
        }

        return back()->with('success', 'تم حفظ وتحديث قائمة حسابات Google المعتمدة كمسؤولين (Admins) بنجاح.');
    })->name('admin.settings.google-admins');

    Route::post('/admin/users/{user}/toggle-admin', function(\App\Models\User $user) {
        $user->is_admin = !$user->is_admin;
        $user->admin_role = $user->is_admin ? 'super_admin' : 'staff';
        $user->save();

        $current = \App\Models\Setting::get('admin_google_emails', '');
        $list = array_filter(array_map('trim', explode(',', str_replace(["\r\n", "\n", ";"], ',', mb_strtolower($current)))));
        $userEmail = mb_strtolower(trim($user->email));

        if ($user->is_admin && !in_array($userEmail, $list, true)) {
            $list[] = $userEmail;
        } elseif (!$user->is_admin) {
            $list = array_values(array_diff($list, [$userEmail]));
        }
        \App\Models\Setting::set('admin_google_emails', implode(', ', $list));

        $status = $user->is_admin ? 'تمت ترقية الحساب إلى مسؤول (Admin)' : 'تم إلغاء صلاحية المسؤول';
        return back()->with('success', "{$status} للمستخدم {$user->name}.");
    })->name('admin.users.toggle-admin');

    // Color Linker & Asset deletion
    Route::post('/admin/quick-edit/variants/{id}', function(Illuminate\Http\Request $request, $id) {
        $variant = App\Models\ProductVariant::findOrFail($id);
        $attrs = $variant->attributes_json ?? [];
        $attrs['image_url'] = $request->input('image_url');
        $variant->attributes_json = $attrs;
        $variant->save();
        return back()->with('success', 'Photo successfully linked to color: ' . ($attrs['color'] ?? $variant->title));
    })->name('admin.quick-edit.update-variant');

    Route::post('/admin/quick-edit/media/{id}', function($id) {
        $asset = App\Models\MediaAsset::findOrFail($id);
        
        $usageCount = \Illuminate\Support\Facades\DB::table('mediables')->where('media_asset_id', $asset->id)->count();
        if ($usageCount > 0) {
            return back()->with('error', "Cannot delete image. It is currently linked to {$usageCount} product(s).");
        }

        $path = 'media/' . $asset->filename;
        if (Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            Illuminate\Support\Facades\Storage::disk('public')->delete($path);
        }
        $asset->delete();

        return back()->with('success', 'Image successfully deleted!');
    })->name('admin.quick-edit.delete-media');

    // ─── Legacy closure CRUD (kept for backward compat with quick-edit page) ───

    Route::post('/admin/products/create-legacy', function(Illuminate\Http\Request $request) {
        $request->validate(['title' => 'required|string|max:255', 'sku' => 'required|string|max:100', 'retail_price_minor' => 'required|numeric|min:0', 'cost_price_minor' => 'nullable|numeric|min:0', 'description' => 'nullable|string', 'inventory' => 'nullable|integer|min:0', 'status' => 'required|in:active,draft,archived',]);
        $slug = \Illuminate\Support\Str::slug($request->title);
        $base = $slug; $i = 1;
        while (Product::where('slug', $slug)->exists()) { $slug = $base . '-' . $i++; }
        $product = Product::create(['title' => $request->title, 'slug' => $slug, 'sku' => $request->sku, 'description' => $request->description, 'retail_price_minor' => (int)($request->retail_price_minor * 100), 'cost_price_minor' => (int)(($request->cost_price_minor ?? 0) * 100), 'inventory' => $request->inventory ?? 0, 'status' => $request->status ?? 'draft', 'currency' => 'EGP',]);
        if ($request->filled('collection_ids')) { $product->collections()->sync($request->input('collection_ids')); }
        return back()->with('success', "Product \"{$product->title}\" created with ID #{$product->id}!");
    });

    // ─── Multi-Page Admin Dashboard Controllers ──────────────────────────────────

    // Dashboard
    Route::get('/admin/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
        ->name('admin.dashboard');

    // Products
    Route::get('/admin/products', [\App\Http\Controllers\Admin\ProductController::class, 'index'])
        ->name('admin.products.index');
    Route::get('/admin/products/new', [\App\Http\Controllers\Admin\ProductController::class, 'create'])
        ->name('admin.products.create');
    Route::post('/admin/products', [\App\Http\Controllers\Admin\ProductController::class, 'store'])
        ->name('admin.products.store');
    Route::get('/admin/products/{id}/edit', [\App\Http\Controllers\Admin\ProductController::class, 'edit'])
        ->name('admin.products.edit');
    Route::post('/admin/products/{id}/update', [\App\Http\Controllers\Admin\ProductController::class, 'update'])
        ->name('admin.products.update');
    Route::delete('/admin/products/{id}/delete', [\App\Http\Controllers\Admin\ProductController::class, 'destroy'])
        ->name('admin.products.delete');
    Route::post('/admin/products/{id}/toggle-status', [\App\Http\Controllers\Admin\ProductController::class, 'toggleStatus'])
        ->name('admin.products.toggle-status');
    // replace-image and upload-gallery already registered above via AdminImageController
    Route::delete('/admin/products/{productId}/media/{assetId}', [\App\Http\Controllers\Admin\ProductController::class, 'deleteMedia'])
        ->name('admin.products.delete-media');
    Route::post('/admin/products/{id}/variants', [\App\Http\Controllers\Admin\ProductController::class, 'addVariant'])
        ->name('admin.products.add-variant');
    Route::post('/admin/variants/{id}/update', [\App\Http\Controllers\Admin\ProductController::class, 'updateVariant'])
        ->name('admin.products.update-variant');
    Route::delete('/admin/variants/{id}', [\App\Http\Controllers\Admin\ProductController::class, 'deleteVariant'])
        ->name('admin.products.delete-variant');

    // Collections
    Route::get('/admin/collections', [\App\Http\Controllers\Admin\CollectionController::class, 'index'])
        ->name('admin.collections.index');
    Route::post('/admin/collections', [\App\Http\Controllers\Admin\CollectionController::class, 'store'])
        ->name('admin.collections.store');
    Route::get('/admin/collections/{id}/edit', [\App\Http\Controllers\Admin\CollectionController::class, 'edit'])
        ->name('admin.collections.edit');
    Route::post('/admin/collections/{id}/update', [\App\Http\Controllers\Admin\CollectionController::class, 'update'])
        ->name('admin.collections.update');
    // replace-image already registered above via AdminImageController
    Route::delete('/admin/collections/{id}/delete', [\App\Http\Controllers\Admin\CollectionController::class, 'destroy'])
        ->name('admin.collections.delete');

    // Orders
    Route::get('/admin/orders', [\App\Http\Controllers\Admin\OrderController::class, 'index'])
        ->name('admin.orders.index');
    Route::get('/admin/orders/{id}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])
        ->name('admin.orders.show');
    Route::post('/admin/orders/{id}/status', [\App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])
        ->name('admin.orders.update-status');

    // Customers
    Route::get('/admin/customers', [\App\Http\Controllers\Admin\CustomerController::class, 'index'])
        ->name('admin.customers.index');
    Route::get('/admin/customers/{id}', [\App\Http\Controllers\Admin\CustomerController::class, 'show'])
        ->name('admin.customers.show');
    Route::post('/admin/customers/{id}/toggle-ban', [\App\Http\Controllers\Admin\CustomerController::class, 'toggleBan'])
        ->name('admin.customers.toggle-ban');
    Route::post('/admin/customers/{id}/send-reset', [\App\Http\Controllers\Admin\CustomerController::class, 'sendPasswordReset'])
        ->name('admin.customers.send-reset');

    // Site Content & Branding
    Route::get('/admin/content', [\App\Http\Controllers\Admin\ContentController::class, 'index'])
        ->name('admin.content.index');
    Route::post('/admin/content', [\App\Http\Controllers\Admin\ContentController::class, 'update'])
        ->name('admin.content.update');

    // Analytics
    Route::get('/admin/analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])
        ->name('admin.analytics.index');

    // Coupons
    Route::get('/admin/coupons', [\App\Http\Controllers\Admin\CouponController::class, 'index'])
        ->name('admin.coupons.index');
    Route::post('/admin/coupons', [\App\Http\Controllers\Admin\CouponController::class, 'store'])
        ->name('admin.coupons.store');
    Route::post('/admin/coupons/{id}/toggle', [\App\Http\Controllers\Admin\CouponController::class, 'toggle'])
        ->name('admin.coupons.toggle');
    Route::delete('/admin/coupons/{id}', [\App\Http\Controllers\Admin\CouponController::class, 'destroy'])
        ->name('admin.coupons.delete');

    // Reviews
    Route::get('/admin/reviews', [\App\Http\Controllers\Admin\ReviewController::class, 'index'])
        ->name('admin.reviews.index');
    Route::post('/admin/reviews/{id}/approve', [\App\Http\Controllers\Admin\ReviewController::class, 'approve'])
        ->name('admin.reviews.approve');
    Route::post('/admin/reviews/{id}/reject', [\App\Http\Controllers\Admin\ReviewController::class, 'reject'])
        ->name('admin.reviews.reject');
    Route::delete('/admin/reviews/{id}', [\App\Http\Controllers\Admin\ReviewController::class, 'destroy'])
        ->name('admin.reviews.destroy');

    // Notifications
    Route::get('/admin/notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])
        ->name('admin.notifications.index');
    Route::post('/admin/notifications/mark-all-read', [\App\Http\Controllers\Admin\NotificationController::class, 'markAllAsRead'])
        ->name('admin.notifications.mark-all-read');
    Route::post('/admin/notifications/{id}/mark-read', [\App\Http\Controllers\Admin\NotificationController::class, 'markAsRead'])
        ->name('admin.notifications.mark-read');
    Route::delete('/admin/notifications/{id}', [\App\Http\Controllers\Admin\NotificationController::class, 'destroy'])
        ->name('admin.notifications.destroy');

    // Inventory & Stock Oversight
    Route::get('/admin/inventory', [\App\Http\Controllers\Admin\InventoryController::class, 'index'])
        ->name('admin.inventory.index');
    Route::post('/admin/inventory/{id}/quick-update', [\App\Http\Controllers\Admin\InventoryController::class, 'quickUpdateStock'])
        ->name('admin.inventory.quick-update');
    Route::post('/admin/inventory/threshold', [\App\Http\Controllers\Admin\InventoryController::class, 'updateGlobalThreshold'])
        ->name('admin.inventory.update-threshold');

    // Products Bulk Actions & CSV Import/Export
    Route::post('/admin/products/bulk', [\App\Http\Controllers\Admin\ProductBulkController::class, 'bulkAction'])
        ->name('admin.products.bulk');
    Route::get('/admin/products/export', [\App\Http\Controllers\Admin\ProductBulkController::class, 'exportCsv'])
        ->name('admin.products.export');
    Route::post('/admin/products/import', [\App\Http\Controllers\Admin\ProductBulkController::class, 'importCsv'])
        ->name('admin.products.import');
    Route::post('/admin/products/{id}/bulk-gallery', [\App\Http\Controllers\Admin\ProductBulkController::class, 'bulkGallery'])
        ->name('admin.products.bulk-gallery');

    // Shipping & Tax Configuration (Manager / Super Admin)
    Route::get('/admin/shipping', [\App\Http\Controllers\Admin\ShippingController::class, 'index'])
        ->name('admin.shipping.index');
    Route::post('/admin/shipping/zones', [\App\Http\Controllers\Admin\ShippingController::class, 'storeZone'])
        ->name('admin.shipping.zones.store');
    Route::post('/admin/shipping/zones/{id}', [\App\Http\Controllers\Admin\ShippingController::class, 'updateZone'])
        ->name('admin.shipping.zones.update');
    Route::delete('/admin/shipping/zones/{id}', [\App\Http\Controllers\Admin\ShippingController::class, 'deleteZone'])
        ->name('admin.shipping.zones.delete');
    Route::post('/admin/shipping/settings', [\App\Http\Controllers\Admin\ShippingController::class, 'updateSettings'])
        ->name('admin.shipping.settings');

    // Team & Roles Management (Super Admin only)
    Route::middleware(['admin:super_admin'])->group(function () {
        Route::get('/admin/team', [\App\Http\Controllers\Admin\TeamController::class, 'index'])
            ->name('admin.team.index');
        Route::post('/admin/team', [\App\Http\Controllers\Admin\TeamController::class, 'store'])
            ->name('admin.team.store');
        Route::post('/admin/team/{id}/role', [\App\Http\Controllers\Admin\TeamController::class, 'updateRole'])
            ->name('admin.team.update-role');
        Route::post('/admin/team/{id}/revoke', [\App\Http\Controllers\Admin\TeamController::class, 'revoke'])
            ->name('admin.team.revoke');
    });

    // Global Quick Search (Ctrl+K)
    Route::get('/admin/search', [\App\Http\Controllers\Admin\GlobalSearchController::class, 'search'])
        ->name('admin.search');

    // Products Inline Quick-Edit
    Route::post('/admin/products/{id}/inline-edit', [\App\Http\Controllers\Admin\InlineEditController::class, 'update'])
        ->name('admin.products.inline-edit');

    // Abandoned Carts Recovery
    Route::get('/admin/abandoned-carts', [\App\Http\Controllers\Admin\AbandonedCartController::class, 'index'])
        ->name('admin.abandoned-carts.index');
    Route::post('/admin/abandoned-carts/{id}/follow-up', [\App\Http\Controllers\Admin\AbandonedCartController::class, 'markFollowedUp'])
        ->name('admin.abandoned-carts.follow-up');

    // Telegram Order Notifications & Bot Settings
    // Telegram Order Notifications & Bot Settings
    Route::get('/admin/telegram', [\App\Http\Controllers\Admin\TelegramController::class, 'index'])
        ->name('admin.telegram.index');
    Route::post('/admin/telegram/settings', [\App\Http\Controllers\Admin\TelegramController::class, 'saveSettings'])
        ->name('admin.telegram.settings');
    Route::post('/admin/telegram/recipients', [\App\Http\Controllers\Admin\TelegramController::class, 'addRecipient'])
        ->name('admin.telegram.recipients.store');
    Route::post('/admin/telegram/recipients/{id}/toggle', [\App\Http\Controllers\Admin\TelegramController::class, 'toggleRecipient'])
        ->name('admin.telegram.recipients.toggle');
    Route::delete('/admin/telegram/recipients/{id}', [\App\Http\Controllers\Admin\TelegramController::class, 'deleteRecipient'])
        ->name('admin.telegram.recipients.delete');
    Route::post('/admin/telegram/test', [\App\Http\Controllers\Admin\TelegramController::class, 'sendTest'])
        ->name('admin.telegram.test');
    Route::post('/admin/telegram/sync', [\App\Http\Controllers\TelegramWebhookController::class, 'sync'])
        ->name('admin.telegram.sync');
    Route::post('/admin/telegram/send-dashboard', [\App\Http\Controllers\TelegramWebhookController::class, 'sendDashboard'])
        ->name('admin.telegram.send-dashboard');

    // WhatsApp Client Notifications & Gateway
    Route::get('/admin/whatsapp', [\App\Http\Controllers\Admin\WhatsAppController::class, 'index'])
        ->name('admin.whatsapp.index');
    Route::post('/admin/whatsapp/settings', [\App\Http\Controllers\Admin\WhatsAppController::class, 'updateSettings'])
        ->name('admin.whatsapp.settings');
    Route::post('/admin/whatsapp/test', [\App\Http\Controllers\Admin\WhatsAppController::class, 'sendTestMessage'])
        ->name('admin.whatsapp.test');

    // Customer Surveys & Polls Management
    Route::get('/admin/surveys', [\App\Http\Controllers\Admin\SurveyController::class, 'index'])
        ->name('admin.surveys.index');
    Route::post('/admin/surveys', [\App\Http\Controllers\Admin\SurveyController::class, 'store'])
        ->name('admin.surveys.store');
    Route::post('/admin/surveys/{id}/toggle', [\App\Http\Controllers\Admin\SurveyController::class, 'toggle'])
        ->name('admin.surveys.toggle');
    Route::delete('/admin/surveys/{id}', [\App\Http\Controllers\Admin\SurveyController::class, 'destroy'])
        ->name('admin.surveys.destroy');
    Route::delete('/admin/surveys/responses/{id}', [\App\Http\Controllers\Admin\SurveyController::class, 'deleteResponse'])
        ->name('admin.surveys.delete-response');
    Route::get('/admin/surveys/{id}/export-csv', [\App\Http\Controllers\Admin\SurveyController::class, 'exportCsv'])
        ->name('admin.surveys.export-csv');

    // Audit Log
    Route::get('/admin/audit-log', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])
        ->name('admin.audit-log.index');

});

// Public Survey Response Submission API
Route::post('/api/surveys/{id}/respond', [\App\Http\Controllers\SurveyApiController::class, 'respond'])
    ->middleware('throttle:30,1')
    ->name('api.surveys.respond');

/*
|--------------------------------------------------------------------------
| Storage File Serving — Dev Only
|--------------------------------------------------------------------------
*/

Route::get('/storage/{path}', function (string $path) {
    $disk = Storage::disk('public');
    
    if (! $disk->exists($path)) {
        abort(404, "File not found: {$path}");
    }

    try {
        $filePath = $disk->path($path);
        $mime = mime_content_type($filePath) ?: 'application/octet-stream';
        
        return response()->file($filePath, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    } catch (\Exception $e) {
        abort(500, "Error serving file: {$e->getMessage()}");
    }
})->where('path', '.*')->name('storage.serve');
