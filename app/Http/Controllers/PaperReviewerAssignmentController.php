<?php

namespace App\Http\Controllers;

use App\Models\Paper;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PaperReviewerAssignmentController extends Controller
{
    public function assignSubmittedPapers(): JsonResponse
    {
        $reviewers = User::query()
            ->whereIn('username', ['reviewer1', 'reviewer2', 'reviewer3'])
            ->orderBy('username')
            ->get()
            ->keyBy('username');

        if (
            ! $reviewers->has('reviewer1') ||
            ! $reviewers->has('reviewer2') ||
            ! $reviewers->has('reviewer3')
        ) {
            return response()->json([
                'message' => 'User reviewer1, reviewer2, dan reviewer3 harus tersedia.',
            ], 422);
        }

        $papers = Paper::query()
            ->where('status', 'submitted')
            ->orderBy('id')
            ->get();

        if ($papers->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada paper berstatus submitted.',
            ]);
        }

        $pairs = [
            [
                'users' => [$reviewers['reviewer1'], $reviewers['reviewer2']],
                'label' => 'reviewer1-reviewer2',
            ],
            [
                'users' => [$reviewers['reviewer1'], $reviewers['reviewer3']],
                'label' => 'reviewer1-reviewer3',
            ],
            [
                'users' => [$reviewers['reviewer2'], $reviewers['reviewer3']],
                'label' => 'reviewer2-reviewer3',
            ],
        ];

        DB::transaction(function () use ($papers, $pairs) {
            $now = now();

            foreach ($papers as $index => $paper) {
                $pair = $pairs[$index % 3];

                DB::table('paper_reviewer')->insert([
                    [
                        'paper_id' => $paper->id,
                        'user_id' => $pair['users'][0]->id,
                        'review_order' => 1,
                        'assigned_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'paper_id' => $paper->id,
                        'user_id' => $pair['users'][1]->id,
                        'review_order' => 2,
                        'assigned_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ]);

                $paper->update([
                    'status' => 'under_review',
                ]);
            }
        });

        $summary = [
            'reviewer1-reviewer2' => 0,
            'reviewer1-reviewer3' => 0,
            'reviewer2-reviewer3' => 0,
        ];

        foreach ($papers as $index => $paper) {
            $pair = $pairs[$index % 3];
            $summary[$pair['label']]++;
        }

        return response()->json([
            'message' => 'Pembagian reviewer berhasil dilakukan.',
            'total_papers' => $papers->count(),
            'pair_distribution' => $summary,
        ]);
    }
}