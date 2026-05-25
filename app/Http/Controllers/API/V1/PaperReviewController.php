<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Paper;
use Illuminate\Http\Request;

class PaperReviewController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $perPage = (int) $request->get('per_page', 10);
        $paperType = $request->get('paper_type');

        $user = $request->user();
        $username = strtolower($user->username ?? '');
        $isReviewer = str_contains($username, 'reviewer');

        $query = Paper::query()
            ->with([
                'paperType:id,name,code',
                'authors:id,name',
                'participant:id',
                'reviewers' => function ($q) {
                    $q->select('users.id', 'users.username')
                        ->withPivot([
                            'id',
                            'review_order',
                            'score',
                            'notes',
                            'assigned_at',
                            'reviewed_at',
                        ]);
                },
            ])
            ->whereIn('status', ['submitted', 'under_review']);

        if ($isReviewer) {
            $query->whereHas('reviewers', function ($q) use ($user) {
                $q->where('users.id', $user->id);
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

        $papers = $query->latest()->paginate($perPage);

        $papers->getCollection()->transform(function ($paper) use ($user) {
            $reviewers = $paper->reviewers->map(function ($reviewer) {
                return [
                    'id' => $reviewer->id,
                    'username' => $reviewer->username,
                    'assignment_id' => $reviewer->pivot->id,
                    'review_order' => $reviewer->pivot->review_order,
                    'score' => $reviewer->pivot->score !== null
                        ? (float) $reviewer->pivot->score
                        : null,
                    'notes' => $reviewer->pivot->notes,
                    'assigned_at' => $reviewer->pivot->assigned_at,
                    'reviewed_at' => $reviewer->pivot->reviewed_at,
                ];
            })->sortBy('review_order')->values();

            $scoredReviewers = $reviewers->filter(fn ($r) => $r['score'] !== null)->values();
            $reviewersCount = $reviewers->count();
            $scoredCount = $scoredReviewers->count();

            $myReviewerRow = $reviewers->firstWhere('id', $user->id);

            return [
                'id' => $paper->id,
                'uuid' => $paper->uuid,
                'title' => $paper->title,
                'abstract' => $paper->abstract,
                'gdrive_link' => $paper->gdrive_link,
                'file_type' => $paper->file_type,
                'status' => $paper->status,
                'final_status' => $paper->final_status,
                'final_score' => $paper->final_score !== null ? (float) $paper->final_score : null,
                'submitted_at' => $paper->submitted_at,
                'reviewed_at' => $paper->reviewed_at,
                'finalized_at' => $paper->finalized_at,
                'paper_type' => $paper->paperType
                    ? [
                        'id' => $paper->paperType->id,
                        'name' => $paper->paperType->name,
                        'code' => $paper->paperType->code,
                    ]
                    : null,
                'authors' => $paper->authors->map(fn ($a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                ])->values(),
                'reviewers' => $reviewers,
                'review_summary' => [
                    'reviewers_count' => $reviewersCount,
                    'scored_count' => $scoredCount,
                    'is_complete' => $reviewersCount === 2 && $scoredCount === 2,
                    'scores' => $scoredReviewers->pluck('score')->values(),
                    'average_score' => $scoredCount > 0
                        ? round($scoredReviewers->avg('score'), 2)
                        : null,
                ],
                'my_reviewer_assignment' => $myReviewerRow,
            ];
        });

        return response()->json([
            'data' => $papers,
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

        $paper->load([
            'reviewers' => function ($q) {
                $q->select('users.id', 'users.username')
                    ->withPivot([
                        'id',
                        'review_order',
                        'score',
                        'notes',
                        'assigned_at',
                        'reviewed_at',
                    ]);
            }
        ]);

        if ($isReviewer) {
            $assignment = $paper->reviewers->firstWhere('id', $user->id);

            if (! $assignment) {
                return response()->json([
                    'message' => 'Paper ini tidak ditugaskan kepada reviewer yang sedang login.',
                ], 403);
            }

            if ($assignment->pivot->score !== null) {
                return response()->json([
                    'message' => 'Nilai reviewer sudah disimpan dan tidak dapat diubah lagi.',
                ], 422);
            }

            $data = $request->validate([
                'reviewer_score' => ['required', 'numeric', 'min:0', 'max:100'],
                'notes' => ['nullable', 'string'],
                'status' => ['prohibited'],
                'final_status' => ['prohibited'],
            ]);

            $paper->reviewers()->updateExistingPivot($user->id, [
                'score' => number_format((float) $data['reviewer_score'], 2, '.', ''),
                'notes' => $data['notes'] ?? null,
                'reviewed_at' => now(),
            ]);

            $paper->refresh()->load('reviewers');

            $scores = $paper->reviewers
                ->pluck('pivot.score')
                ->filter(fn ($score) => $score !== null)
                ->map(fn ($score) => (float) $score)
                ->values();

            $paper->update([
                'status' => $scores->count() > 0 ? 'under_review' : $paper->status,
                'reviewed_at' => now(),
                'final_score' => $scores->count() === 2
                    ? number_format($scores->avg(), 2, '.', '')
                    : null,
            ]);

            return response()->json([
                'message' => 'Reviewer score berhasil disimpan',
            ]);
        }

        $data = $request->validate([
            'status' => ['required', 'in:accepted,rejected'],
            'final_status' => ['nullable', 'in:oral_presentation,poster_presentation'],
            'reviewer_score' => ['prohibited'],
            'notes' => ['prohibited'],
        ]);

        $reviewersCount = $paper->reviewers->count();
        $scores = $paper->reviewers
            ->pluck('pivot.score')
            ->filter(fn ($score) => $score !== null)
            ->map(fn ($score) => (float) $score)
            ->values();

        if ($reviewersCount !== 2 || $scores->count() !== 2) {
            return response()->json([
                'message' => 'Keputusan review hanya bisa dilakukan setelah 2 reviewer memberikan nilai.',
            ], 422);
        }

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
            'finalized_at' => now(),
            'final_score' => number_format($scores->avg(), 2, '.', ''),
        ]);

        return response()->json([
            'message' => 'Keputusan review berhasil disimpan',
        ]);
    }
}