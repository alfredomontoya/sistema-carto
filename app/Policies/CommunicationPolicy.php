<?php

namespace App\Policies;

use App\Models\Communication;
use App\Models\User;

class CommunicationPolicy
{
    /**
     * Only the author can edit or annul an active record.
     */
    public function edit(User $user, Communication $communication): bool
    {
        return $user->id === $communication->user_id
            && $communication->status === Communication::STATUS_ACTIVE;
    }
}
