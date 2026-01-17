<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return Category::query()->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validated([
            'name' => ['required','string','max:120'],
            'parent_id' => ['nullable','integer','exists:categories,id'],
            'is_active' => ['nullable','boolean'],
        ]);

        $cat = Category::create($data);
        return response()->json($cat, 201);
    }

    public function show(Category $category)
    {
        return $category;
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validated([
            'name' => ['sometimes','string','max:120'],
            'parent_id' => ['nullable','integer','exists:categories,id'],
            'is_active' => ['nullable','boolean'],
        ]);

        $category->update($data);
        return response()->json(['message' => 'Updated', 'category' => $category]);
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
