<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use Illuminate\Http\Request;

class BankController extends Controller
{
    public function index(Request $request)
    {
        $query = Bank::orderBy('order')->orderBy('name');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%")
                  ->orWhere('account_number', 'like', "%{$request->search}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($request->per_page ?? 10),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|unique:banks,code',
            'name' => 'required',
            'account_number' => 'required',
            'account_name' => 'required',
            'admin_fee' => 'numeric|min:0',
            'is_active' => 'boolean',
        ]);

        Bank::create($data);

        return response()->json(['success' => true]);
    }

    public function update(Request $request, Bank $bank)
    {
        $data = $request->validate([
            'code' => 'required|unique:banks,code,' . $bank->id,
            'name' => 'required',
            'account_number' => 'required',
            'account_name' => 'required',
            'admin_fee' => 'numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $bank->update($data);

        return response()->json(['success' => true]);
    }

    public function destroy(Bank $bank)
    {
        $bank->delete();
        return response()->json(['success' => true]);
    }
}
