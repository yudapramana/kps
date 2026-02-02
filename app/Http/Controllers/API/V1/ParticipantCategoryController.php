<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\ParticipantCategory;
use Illuminate\Http\Request;

class ParticipantCategoryController extends Controller
{
    /**
     * GET /api/v1/participant-categories
     * List + search + pagination
     */
    public function index(Request $request)
    {
        $search  = $request->get('search');
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = ParticipantCategory::query()->orderBy('name');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return response()->json([
            'success' => true,
            'data'    => $query->paginate($perPage),
        ]);
    }

    /**
     * POST /api/v1/participant-categories
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $category = ParticipantCategory::create($data);

        return response()->json([
            'message' => 'Kategori peserta berhasil ditambahkan.',
            'data'    => $category,
        ], 201);
    }

    /**
     * PUT /api/v1/participant-categories/{participantCategory}
     */
    public function update(Request $request, ParticipantCategory $participantCategory)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $participantCategory->update($data);

        return response()->json([
            'message' => 'Kategori peserta berhasil diperbarui.',
            'data'    => $participantCategory,
        ]);
    }

    /**
     * DELETE /api/v1/participant-categories/{participantCategory}
     */
    public function destroy(ParticipantCategory $participantCategory)
    {
        $participantCategory->delete();

        return response()->json([
            'message' => 'Kategori peserta berhasil dihapus.',
        ]);
    }
}
