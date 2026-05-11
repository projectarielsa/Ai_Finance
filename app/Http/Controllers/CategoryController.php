<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $systemCategories = Category::whereNull('user_id')
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $userCategories = Category::where('user_id', $user->id)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('systemCategories', 'userCategories'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'type'        => 'required|in:income,expense,transfer',
            'icon'        => 'nullable|string|max:10',
            'color'       => 'nullable|string|max:7',
            'description' => 'nullable|string|max:500',
            'ai_keywords' => 'nullable|string',
        ]);

        // Parse keywords dari textarea ke array
        $keywords = [];
        if (!empty($data['ai_keywords'])) {
            $keywords = array_filter(array_map('trim', explode(',', $data['ai_keywords'])));
        }

        $slug = Str::slug($data['name']);
        // Ensure slug uniqueness for this user
        $slugExists = Category::where('user_id', $user->id)->where('slug', $slug)->exists();
        if ($slugExists) $slug = $slug . '-' . Str::random(4);

        Category::create([
            'user_id'     => $user->id,
            'name'        => $data['name'],
            'slug'        => $slug,
            'type'        => $data['type'],
            'icon'        => $data['icon'] ?? null,
            'color'       => $data['color'] ?? '#3b82f6',
            'description' => $data['description'] ?? null,
            'ai_keywords' => $keywords ?: null,
            'is_system'   => false,
            'is_active'   => true,
        ]);

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil ditambahkan!');
    }

    public function edit(Category $category)
    {
        // Only allow editing user's own categories
        abort_unless($category->user_id === Auth::id(), 403);
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        abort_unless($category->user_id === Auth::id(), 403);

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'type'        => 'required|in:income,expense,transfer',
            'icon'        => 'nullable|string|max:10',
            'color'       => 'nullable|string|max:7',
            'description' => 'nullable|string|max:500',
            'ai_keywords' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        $keywords = [];
        if (!empty($data['ai_keywords'])) {
            $keywords = array_filter(array_map('trim', explode(',', $data['ai_keywords'])));
        }

        $category->update([
            'name'        => $data['name'],
            'type'        => $data['type'],
            'icon'        => $data['icon'] ?? null,
            'color'       => $data['color'] ?? $category->color,
            'description' => $data['description'] ?? null,
            'ai_keywords' => $keywords ?: null,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return redirect()->route('categories.index')->with('success', 'Kategori diperbarui!');
    }

    public function destroy(Category $category)
    {
        abort_unless($category->user_id === Auth::id(), 403);

        if ($category->transactions()->exists()) {
            return back()->with('error', 'Kategori tidak bisa dihapus karena masih digunakan oleh transaksi.');
        }

        $category->delete();
        return back()->with('success', 'Kategori dihapus!');
    }
}
