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

    /**
     * The author, the recipient, the destination area or an administrator
     * can download the attached file.
     */
    public function download(User $user, Communication $communication): bool
    {
        if ($user->seesGlobalStats()) {
            return true;
        }

        if ($user->id === $communication->user_id || $user->id === $communication->recipient_user_id) {
            return true;
        }

        $areaId = $user->currentArea?->id;

        return $areaId !== null
            && ($communication->area_id === $areaId || $communication->area_destino_id === $areaId);
    }
}
