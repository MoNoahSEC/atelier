<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Address;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AddressPolicy
{
    /**
     * Determine whether the user can view the address.
     */
    public function view(User $user, Address $address): Response
    {
        if ($user->isAdmin() || $address->user_id === $user->id) {
            return Response::allow();
        }

        return Response::deny('You do not have permission to view this address.');
    }

    /**
     * Determine whether the user can update the address.
     */
    public function update(User $user, Address $address): Response
    {
        if ($user->isAdmin() || $address->user_id === $user->id) {
            return Response::allow();
        }

        return Response::deny('You do not have permission to modify this address.');
    }

    /**
     * Determine whether the user can delete the address.
     */
    public function delete(User $user, Address $address): Response
    {
        if ($user->isAdmin() || $address->user_id === $user->id) {
            return Response::allow();
        }

        return Response::deny('You do not have permission to delete this address.');
    }
}
