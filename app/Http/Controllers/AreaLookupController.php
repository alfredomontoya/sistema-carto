<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\AreaRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AreaLookupController extends Controller
{
    public function __construct(
        private readonly AreaRepository $areas,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('term', ''));

        if ($term === '') {
            return response()->json([]);
        }

        $areas = $this->areas->search($term, (int) $request->integer('limit', 10));

        return response()->json(
            $areas->map(
                fn ($area) => [
                    'id' => $area->id,
                    'name' => $area->name,
                    'code' => $area->code,
                    'parent' => $area->parent?->name,
                ]
            )
        );
    }
}
