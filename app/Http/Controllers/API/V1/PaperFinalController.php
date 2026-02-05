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
            ->where('status', 'accepted');

        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($paperType) {
            $query->whereHas('paperType', fn ($q) =>
                $q->where('code', $paperType)
            );
        }

        return response()->json([
            'data' => $query->latest()->paginate($perPage),
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
