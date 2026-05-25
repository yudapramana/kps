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
        $perPage = $request->get('per_page', 10);
        $paperType = $request->get('paper_type');

        $query = Paper::with(['paperType', 'authors', 'participant'])
            ->where('status', 'accepted')
            ->whereNotNull('reviewer_score');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                ->orWhereHas('participant', function ($p) use ($search) {
                    $p->where('full_name', 'like', "%{$search}%");
                });
            });
        }

        if ($paperType) {
            $query->whereHas('paperType', function ($q) use ($paperType) {
                $q->where('code', $paperType);
            });
        }

        $query->orderByDesc('reviewer_score')
            ->orderBy('title');

        return response()->json([
            'data' => $query->paginate($perPage),
        ]);
    }

    public function update(Request $request, Paper $paper)
    {
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
