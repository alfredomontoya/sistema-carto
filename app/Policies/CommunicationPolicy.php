<?php

namespace App\Policies;

use App\Models\Communication;
use App\Models\User;

class CommunicationPolicy
{
    /**
     * The author or an administrator can edit an active record.
     */
    public function edit(User $user, Communication $communication): bool
    {
        if ($communication->status !== Communication::STATUS_ACTIVE) {
            return false;
        }

        return $user->id === $communication->user_id || $user->hasRole('administrador');
    }

    /**
     * Only an administrator can annul an active record.
     */
    public function annul(User $user, Communication $communication): bool
    {
        return $communication->status === Communication::STATUS_ACTIVE
            && $user->hasRole('administrador');
    }
}
