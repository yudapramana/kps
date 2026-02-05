<?php 

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Paper;
use Illuminate\Http\Request;

class PaperReviewController extends Controller
{
    public function index(Request $request)
    {
        $search     = $request->get('search');
        $perPage    = $request->get('per_page', 10);
        $paperType  = $request->get('paper_type');

        $query = Paper::with(['paperType', 'authors', 'participant'])
            ->whereIn('status', ['submitted', 'under_review']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                ->orWhereHas('authors', function ($a) use ($search) {
                    $a->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($paperType) {
            $query->whereHas('paperType', function ($q) use ($paperType) {
                $q->where('code', $paperType);
            });
        }

        return response()->json([
            'data' => $query->latest()->paginate($perPage),
        ]);
    }

    /**
     * PUT /api/v1/papers/{paper}/review
     */
    public function review(Request $request, Paper $paper)
    {
        $data = $request->validate([
            'status' => ['required', 'in:under_review,accepted,rejected'],
            'final_status' => ['nullable', 'in:oral_presentation,poster_presentation'],
        ]);

        // jika bukan accepted, final_status harus null
        if ($data['status'] !== 'accepted') {
            $data['final_status'] = null;
        }

        $paper->update([
            'status'        => $data['status'],
            'final_status'  => $data['final_status'] ?? null,
            'reviewed_at'   => now(),
            'finalized_at'  => $data['status'] === 'accepted' ? now() : null,
        ]);

        return response()->json([
            'message' => 'Keputusan review berhasil disimpan',
        ]);
    }
}
