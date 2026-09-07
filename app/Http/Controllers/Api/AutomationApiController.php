<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Automation\FulfillmentRequest;
use App\Http\Requests\Automation\PendingOrdersRequest;
use App\Http\Requests\Automation\SyncProductRequest;
use App\Http\Resources\PendingOrderResource;
use App\Http\Resources\ProductResource;
use App\Jobs\ProcessFulfillmentWebhook;
use App\Services\OrderService;
use App\Services\ProductSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class AutomationApiController extends Controller
{
    /**
     * Synchronize or upsert product data from automation infrastructure.
     * Execution is synchronous because products are often required immediately.
     */
    public function syncProduct(SyncProductRequest $request, ProductSyncService $service): JsonResource|JsonResponse
    {
        $product = $service->sync($request->validated());

        return (new ProductResource($product))->additional([
            'success' => true,
            'message' => 'Product synchronized successfully.',
        ]);
    }

    /**
     * Receive fulfillment updates from external automation.
     * Execution is asynchronous (dispatched to a queue) to ensure high throughput
     * and immediate response to the webhook provider.
     */
    public function fulfillment(FulfillmentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $actorId = $request->user()?->id;

        // Dispatch the job to the fulfillment queue
        ProcessFulfillmentWebhook::dispatch($data, 'user', $actorId)
            ->onQueue('fulfillment');

        return response()->json([
            'success' => true,
            'message' => 'Fulfillment update accepted for processing.',
            'data'    => [
                'idempotency_key' => $data['idempotency_key'],
            ],
        ], 202);
    }

    /**
     * Return orders eligible for external fulfillment.
     */
    public function pendingOrders(PendingOrdersRequest $request, OrderService $service): JsonResource|JsonResponse
    {
        $filters = $request->only(['currency', 'created_after']);
        $perPage = (int) $request->input('per_page', config('automation.pagination.pending_orders_per_page', 100));

        $orders = $service->getPendingOrders($filters, $perPage);

        return PendingOrderResource::collection($orders)->additional([
            'success' => true,
            'message' => 'Pending orders retrieved successfully.',
        ]);
    }
}
