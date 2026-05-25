<?php 

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Paper;
use Illuminate\Http\Request;

class PaperReviewController extends Controller
{
    public function index(Request $request)
    {
        $search    = $request->get('search');
        $perPage   = $request->get('per_page', 10);
        $paperType = $request->get('paper_type');

        $user = $request->user();
        $username = strtolower($user->username ?? '');
        $isReviewer = str_contains($username, 'reviewer');

        $query = Paper::with(['paperType', 'authors', 'participant'])
            ->whereIn('status', ['submitted', 'under_review']);

        if ($isReviewer) {
            $query->where(function ($q) {
                $q->whereNull('reviewer_score')
                ->orWhere('reviewer_score', 0);
            });
        }

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
        $user = $request->user();
        $username = strtolower($user->username ?? '');
        $isReviewer = str_contains($username, 'reviewer');

        if ($isReviewer) {
            if ($paper->reviewer_score !== null && (float) $paper->reviewer_score != 0.0) {
                return response()->json([
                    'message' => 'Reviewer score sudah disimpan dan tidak dapat diubah lagi.',
                ], 422);
            }

            $data = $request->validate([
                'reviewer_score' => ['required', 'numeric', 'min:0', 'max:100'],
                'status' => ['prohibited'],
                'final_status' => ['prohibited'],
            ]);

            $paper->update([
                'reviewer_score' => number_format((float) $data['reviewer_score'], 2, '.', ''),
                'reviewed_at' => now(),
            ]);

            return response()->json([
                'message' => 'Reviewer score berhasil disimpan',
            ]);
        }

        $data = $request->validate([
            'status' => ['required', 'in:accepted,rejected'],
            'final_status' => ['nullable', 'in:oral_presentation,poster_presentation'],
            'reviewer_score' => ['prohibited'],
        ]);

        if ($data['status'] !== 'accepted') {
            $data['final_status'] = null;
        }

        if ($data['status'] === 'accepted' && empty($data['final_status'])) {
            return response()->json([
                'message' => 'Final presentation harus dipilih jika paper accepted.',
            ], 422);
        }

        $paper->update([
            'status' => $data['status'],
            'final_status' => $data['final_status'] ?? null,
            'reviewed_at' => now(),
            'finalized_at' => in_array($data['status'], ['accepted', 'rejected']) ? now() : null,
        ]);

        return response()->json([
            'message' => 'Keputusan review berhasil disimpan',
        ]);
    }
}
