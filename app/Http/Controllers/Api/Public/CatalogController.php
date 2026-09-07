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
    public function index(Request $request)
    {
        $query = Product::active()->with(['variants', 'mediaAssets', 'collections']);

        if ($request->filled('collection') && $request->collection !== 'all') {
            $query->whereHas('collections', function ($q) use ($request) {
                $q->where('slug', $request->collection);
            });
        }
        
        if ($request->filled('search')) {
            $query->where('title', 'LIKE', '%' . $request->search . '%');
        }
        
        if ($request->filled('min_price')) {
            $query->where('retail_price_minor', '>=', $request->min_price);
        }
        
        if ($request->filled('max_price')) {
            $query->where('retail_price_minor', '<=', $request->max_price);
        }
        
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('retail_price_minor', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('retail_price_minor', 'desc');
                break;
            case 'title_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('id', 'desc');
                break;
        }

        $products = $query->paginate(20);

        return ProductResource::collection($products);
    }

    /**
     * Retrieve a single product by slug.
     */
    public function show(string $slug)
    {
        $product = Product::active()
            ->with(['variants', 'mediaAssets', 'collections'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new ProductResource($product);
    }
}
