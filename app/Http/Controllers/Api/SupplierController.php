<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        return Supplier::query()->latest()->paginate(15);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:200'],
            'phone' => ['nullable','string','max:30'],
            'email' => ['nullable','email','max:160'],
            'address' => ['nullable','string'],
            'is_active' => ['nullable','boolean'],
        ]);

        $supplier = Supplier::create($data);
        return response()->json($supplier, 201);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'name' => ['sometimes','string','max:200'],
            'phone' => ['nullable','string','max:30'],
            'email' => ['nullable','email','max:160'],
            'address' => ['nullable','string'],
            'is_active' => ['nullable','boolean'],
        ]);

        $supplier->update($data);
        return response()->json(['message' => 'Updated', 'supplier' => $supplier]);
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
