<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Collection;
use App\Models\MediaAsset;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductBulkController extends Controller
{
    /**
     * Handle bulk actions on selected products.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action'      => 'required|string|in:activate,draft,price_percent,price_fixed,delete',
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
            'adjustment_value' => 'nullable|numeric',
        ]);

        $ids = $request->input('product_ids');
        $action = $request->input('action');
        $adjVal = (float) $request->input('adjustment_value', 0);

        $count = count($ids);

        switch ($action) {
            case 'activate':
                Product::whereIn('id', $ids)->update(['status' => 'active']);
                AuditLog::log('product.bulk_activate', 'product', 0, "Bulk activated {$count} products.");
                return back()->with('success', "{$count} products set to ACTIVE.");

            case 'draft':
                Product::whereIn('id', $ids)->update(['status' => 'draft']);
                AuditLog::log('product.bulk_draft', 'product', 0, "Bulk set {$count} products to DRAFT.");
                return back()->with('success', "{$count} products set to DRAFT.");

            case 'price_percent':
                $multiplier = 1 + ($adjVal / 100);
                $products = Product::whereIn('id', $ids)->get();
                foreach ($products as $p) {
                    $newPrice = (int) round($p->retail_price_minor * $multiplier);
                    $p->retail_price_minor = max(0, $newPrice);
                    $p->save();
                }
                AuditLog::log('product.bulk_price_percent', 'product', 0, "Adjusted price of {$count} products by {$adjVal}%.");
                return back()->with('success', "Price of {$count} products updated by {$adjVal}%.");

            case 'price_fixed':
                $diffMinor = (int) round($adjVal * 100);
                $products = Product::whereIn('id', $ids)->get();
                foreach ($products as $p) {
                    $newPrice = $p->retail_price_minor + $diffMinor;
                    $p->retail_price_minor = max(0, $newPrice);
                    $p->save();
                }
                AuditLog::log('product.bulk_price_fixed', 'product', 0, "Adjusted price of {$count} products by {$adjVal} EGP.");
                return back()->with('success', "Price of {$count} products adjusted by {$adjVal} EGP.");

            case 'delete':
                foreach (Product::whereIn('id', $ids)->get() as $p) {
                    $p->collections()->detach();
                    $p->mediaAssets()->detach();
                    $p->variants()->delete();
                    $p->delete();
                }
                AuditLog::log('product.bulk_delete', 'product', 0, "Permanently deleted {$count} products.");
                return back()->with('success', "{$count} products permanently deleted.");
        }

        return back();
    }

    /**
     * Export all products to CSV.
     */
    public function exportCsv(): StreamedResponse
    {
        $fileName = 'atelier_products_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            
            // Output UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($handle, [
                'SKU',
                'Name',
                'Retail_Price_EGP',
                'Compare_Price_EGP',
                'Cost_Price_EGP',
                'Inventory',
                'Status',
                'Collections',
                'Material',
                'Dimensions',
                'Weight',
                'Description'
            ]);

            Product::with('collections')->chunk(100, function ($products) use ($handle) {
                foreach ($products as $p) {
                    $collections = $p->collections->pluck('title')->implode(' | ');
                    fputcsv($handle, [
                        $p->sku,
                        $p->title,
                        $p->retail_price_minor / 100,
                        $p->compare_at_price_minor ? $p->compare_at_price_minor / 100 : '',
                        $p->cost_price_minor ? $p->cost_price_minor / 100 : '',
                        $p->inventory,
                        $p->status,
                        $collections,
                        $p->material ?? '',
                        $p->dimensions ?? '',
                        $p->weight ?? '',
                        strip_tags($p->description ?? ''),
                    ]);
                }
            });

            fclose($handle);
        }, $fileName, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Import products from CSV file with matching by SKU.
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            // Check for BOM
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $headers = fgetcsv($handle);
            if (!$headers) {
                return back()->with('error', 'The uploaded CSV file is empty.');
            }

            // Normalize headers
            $headers = array_map(fn($h) => strtolower(trim(str_replace([' ', '_'], '', $h))), $headers);

            $line = 1;
            while (($data = fgetcsv($handle)) !== false) {
                $line++;
                if (count($data) === count($headers)) {
                    $row = array_combine($headers, $data);
                    $row['_line'] = $line;
                    $rows[] = $row;
                }
            }
            fclose($handle);
        }

        if (empty($rows)) {
            return back()->with('error', 'No valid product rows detected in CSV.');
        }

        $createdCount = 0;
        $updatedCount = 0;
        $errors = [];

        foreach ($rows as $row) {
            $sku = trim($row['sku'] ?? '');
            $title = trim($row['name'] ?? ($row['title'] ?? ''));

            if (empty($sku)) {
                $errors[] = "Row #{$row['_line']}: Missing required SKU.";
                continue;
            }

            $price = (float) ($row['retailpriceegp'] ?? ($row['price'] ?? ($row['retailprice'] ?? 0)));
            $comparePriceRaw = $row['comparepriceegp'] ?? ($row['compareprice'] ?? null);
            $comparePrice = !empty($comparePriceRaw) ? (float) $comparePriceRaw : null;
            $costPriceRaw = $row['costpriceegp'] ?? ($row['costprice'] ?? null);
            $costPrice = !empty($costPriceRaw) ? (float) $costPriceRaw : 0;
            $stock = (int) ($row['inventory'] ?? ($row['stock'] ?? 10));
            $status = in_array(strtolower($row['status'] ?? ''), ['active', 'draft', 'archived'], true) ? strtolower($row['status']) : 'active';
            $material = $row['material'] ?? null;
            $dimensions = $row['dimensions'] ?? null;
            $weight = $row['weight'] ?? null;

            $product = Product::where('sku', $sku)->first();

            if ($product) {
                // Update existing
                $updateData = [
                    'retail_price_minor'     => (int) round($price * 100),
                    'inventory'              => $stock,
                    'status'                 => $status,
                ];
                if (!empty($title)) $updateData['title'] = $title;
                if ($comparePrice !== null) $updateData['compare_at_price_minor'] = (int) round($comparePrice * 100);
                if ($costPrice > 0) $updateData['cost_price_minor'] = (int) round($costPrice * 100);
                if ($material) $updateData['material'] = $material;
                if ($dimensions) $updateData['dimensions'] = $dimensions;
                if ($weight) $updateData['weight'] = $weight;

                $product->update($updateData);
                $updatedCount++;
            } else {
                // Create new
                if (empty($title)) {
                    $errors[] = "Row #{$row['_line']} (SKU {$sku}): Name is required for new products.";
                    continue;
                }

                $slug = Str::slug($title);
                $base = $slug; $i = 1;
                while (Product::where('slug', $slug)->exists()) { $slug = $base . '-' . $i++; }

                Product::create([
                    'sku'                    => $sku,
                    'title'                  => $title,
                    'slug'                   => $slug,
                    'retail_price_minor'     => (int) round($price * 100),
                    'compare_at_price_minor' => $comparePrice ? (int) round($comparePrice * 100) : null,
                    'cost_price_minor'       => (int) round($costPrice * 100),
                    'inventory'              => $stock,
                    'status'                 => $status,
                    'material'               => $material,
                    'dimensions'             => $dimensions,
                    'weight'                 => $weight,
                    'currency'               => 'EGP',
                ]);
                $createdCount++;
            }
        }

        AuditLog::log('product.import_csv', 'product', 0, "CSV Import completed: {$createdCount} created, {$updatedCount} updated.");

        $msg = "CSV Import complete: {$createdCount} product(s) created, {$updatedCount} product(s) updated.";
        if (!empty($errors)) {
            $msg .= " (Note: " . count($errors) . " row warnings encountered).";
        }

        return back()->with('success', $msg);
    }

    /**
     * Bulk gallery images upload for product.
     */
    public function bulkGallery(Request $request, int $id)
    {
        $request->validate([
            'images'   => 'required|array|min:1',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $product = Product::findOrFail($id);
        $uploaded = 0;

        foreach ($request->file('images') as $file) {
            $filename = 'gallery-bulk-' . time() . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('media', $filename, 'public');
            $url = '/storage/' . $path;

            $asset = MediaAsset::create([
                'type'       => 'image',
                'url'        => $url,
                'filename'   => $filename,
                'mime_type'  => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
            ]);

            $nextOrder = (int) $product->mediaAssets()->max('sort_order') + 1;
            $product->mediaAssets()->attach($asset->id, ['group' => 'gallery', 'sort_order' => $nextOrder]);
            $uploaded++;
        }

        AuditLog::log('product.bulk_gallery', 'product', $product->id, "Batch uploaded {$uploaded} gallery photos for {$product->title}");

        return back()->with('success', "{$uploaded} gallery photos uploaded successfully!");
    }
}
