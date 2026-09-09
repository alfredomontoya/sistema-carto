<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserLookupController extends Controller
{
    public function __construct(
        private readonly UserRepository $users,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('term', ''));

        if ($term === '' || strlen($term) < 3) {
            return response()->json([]);
        }

        $limit = min((int) $request->integer('limit', 10), 20);

        $users = $this->users->search($term, $limit);

        return response()->json(
            $users->map(
                fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'position' => $user->currentAssignment?->position?->name,
                    'area' => $user->currentArea?->name,
                ]
            )
        );
    }
}
