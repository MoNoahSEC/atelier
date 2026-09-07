<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;

class PaymentMethodController extends Controller
{
    public function index(PaymentGatewayManager $manager): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $manager->enabledMethods(),
        ]);
    }
}
