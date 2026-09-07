<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        $userEmail = mb_strtolower(trim($user->email));
        $orderEmail = mb_strtolower(trim($order->customer_email ?? ''));

        if (!empty($orderEmail) && $orderEmail === $userEmail) {
            return Response::allow();
        }

        if ($order->customer && mb_strtolower(trim($order->customer->email)) === $userEmail) {
            return Response::allow();
        }

        return Response::deny('You do not have permission to view this order.');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Order $order): Response
    {
        if ($user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Only administrators can modify orders.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Order $order): Response
    {
        return $user->isAdmin()
            ? Response::allow()
            : Response::deny('Only administrators can delete orders.');
    }
}
