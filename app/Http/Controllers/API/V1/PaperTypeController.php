<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\PaperType;
use Illuminate\Http\Request;

class PaperTypeController extends Controller
{
    public function index(Request $request)
    {
        $search  = $request->get('search');
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = PaperType::query()->orderBy('code');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'data' => $query->paginate($perPage),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'        => ['required', 'string', 'max:50', 'unique:paper_types,code'],
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $paperType = PaperType::create($data);

        return response()->json([
            'message' => 'Paper type berhasil ditambahkan',
            'data'    => $paperType,
        ], 201);
    }

    public function update(Request $request, PaperType $paperType)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $paperType->update($data);

        return response()->json([
            'message' => 'Paper type berhasil diperbarui',
            'data'    => $paperType,
        ]);
    }

    public function destroy(PaperType $paperType)
    {
        // CEK: apakah sudah dipakai oleh paper
        if ($paperType->papers()->exists()) {
            return response()->json([
                'message' => 'Paper type tidak dapat dihapus karena sudah digunakan oleh paper.',
            ], 422);
        }

        $paperType->delete();

        return response()->json([
            'message' => 'Paper type berhasil dihapus.',
        ]);
    }

}
