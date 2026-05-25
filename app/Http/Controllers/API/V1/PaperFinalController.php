<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Paper;
use Illuminate\Http\Request;

class PaperFinalController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $perPage = (int) $request->get('per_page', 10);
        $paperType = $request->get('paper_type');

        $query = Paper::query()
            ->with([
                'paperType:id,name,code',
                'authors:id,name',
                'participant:id,full_name',
            ])
            ->where('status', 'accepted')
            ->whereNotNull('final_score');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('participant', function ($p) use ($search) {
                        $p->where('full_name', 'like', "%{$search}%");
                    })
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

        $query->orderByDesc('final_score')
            ->orderBy('title');

        $papers = $query->paginate($perPage);

        $papers->getCollection()->transform(function ($paper) {
            return [
                'id' => $paper->id,
                'uuid' => $paper->uuid,
                'title' => $paper->title,
                'status' => $paper->status,
                'final_status' => $paper->final_status,
                'final_score' => $paper->final_score !== null ? (float) $paper->final_score : null,
                'submitted_at' => $paper->submitted_at,
                'reviewed_at' => $paper->reviewed_at,
                'finalized_at' => $paper->finalized_at,
                'paper_type' => $paper->paperType ? [
                    'id' => $paper->paperType->id,
                    'name' => $paper->paperType->name,
                    'code' => $paper->paperType->code,
                ] : null,
                'participant' => $paper->participant ? [
                    'id' => $paper->participant->id,
                    'full_name' => $paper->participant->full_name,
                ] : null,
                'authors' => $paper->authors->map(fn ($a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                ])->values(),
            ];
        });

        return response()->json([
            'data' => $papers,
        ]);
    }

    public function update(Request $request, Paper $paper)
    {
        if ($paper->status !== 'accepted') {
            return response()->json([
                'message' => 'Hanya paper accepted yang dapat difinalisasi.',
            ], 422);
        }

        if ($paper->final_score === null) {
            return response()->json([
                'message' => 'Paper belum memiliki final score dari 2 reviewer.',
            ], 422);
        }

        $data = $request->validate([
            'final_status' => ['required', 'in:oral_presentation,poster_presentation'],
        ]);

        $paper->update([
            'final_status' => $data['final_status'],
            'finalized_at' => now(),
        ]);

        return response()->json([
            'message' => 'Final presentation berhasil disimpan',
        ]);
    }
}