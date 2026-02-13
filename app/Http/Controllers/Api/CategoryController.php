<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    
    // GET /categories/summary
    public function summary()
    {
        $total = Category::query()->count();
        $active = Category::query()->where('is_active', true)->count();
        $disabled = Category::query()->where('is_active', false)->count();

        // products.category_id -> categories.id
        $linkedProducts = DB::table('products')
            ->whereNotNull('category_id')
            ->count();

        return response()->json([
            'total_categories' => (int) $total,
            'active' => (int) $active,
            'disabled' => (int) $disabled,
            'linked_products' => (int) $linkedProducts,
        ]);
    }

    // GET /categories?search=&status=&sort=&per_page=&page=
    public function index(Request $request)
    {
        $search = trim((string)$request->query('search', ''));
        $status = trim((string)$request->query('status', '')); // active|disabled
        $sort = trim((string)$request->query('sort', 'newest')); // newest|oldest|a-z|z-a

        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min(100, $perPage));

        $q = Category::query()
            ->select('id', 'name', 'description', 'is_active', 'created_at')
            ->when($search !== '', function ($qq) use ($search) {
                // Postgres-safe
                $s = mb_strtolower($search);
                $qq->whereRaw('LOWER(name) LIKE ?', ["%{$s}%"]);
            })
            ->when($status !== '', function ($qq) use ($status) {
                if (strtolower($status) === 'active') $qq->where('is_active', true);
                if (strtolower($status) === 'disabled') $qq->where('is_active', false);
            });

        $sortKey = strtolower($sort);

        if ($sortKey === 'oldest') $q->orderBy('created_at', 'asc');
        elseif ($sortKey === 'a-z') $q->orderBy('name', 'asc');
        elseif ($sortKey === 'z-a') $q->orderBy('name', 'desc');
        else $q->orderBy('created_at', 'desc'); // newest

        return $q->paginate($perPage);
    }

   public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:120'],
            'description' => ['nullable','string'],
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
        $data = $request->validate([
            'name' => ['sometimes','string','max:120'],
            'description' => ['sometimes','nullable','string'],
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
