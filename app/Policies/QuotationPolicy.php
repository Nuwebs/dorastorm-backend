<?php

namespace App\Policies;

use App\Models\User;
use App\Utils\DsPermission;

class QuotationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAbleTo(DsPermission::QUOTATIONS_READ);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): bool
    {
        return $user->isAbleTo(DsPermission::QUOTATIONS_DELETE);
    }
}
