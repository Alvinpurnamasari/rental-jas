<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')
            ->orderBy('sort_order')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:100|unique:categories,name',
            'slug'       => 'required|string|max:100|unique:categories,slug',
            'icon'       => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|unique:categories,sort_order',
            'is_active'  => 'boolean',
        ]);

        Category::create([
            'name'       => $request->name,
            'slug'       => $request->slug,
            'icon'       => $request->icon,
            'sort_order' => $request->sort_order,
            'is_active'  => $request->boolean('is_active', true),
        ]);

        return redirect()->route('categories.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name'       => 'required|string|max:100|unique:categories,name,' . $category->id,
            'slug'       => 'required|string|max:100|unique:categories,slug,' . $category->id,
            'icon'       => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|unique:categories,sort_order,' . $category->id,
            'is_active'  => 'boolean',
        ]);

        $category->update([
            'name'       => $request->name,
            'slug'       => $request->slug,
            'icon'       => $request->icon,
            'sort_order' => $request->sort_order,
            'is_active'  => $request->boolean('is_active', true),
        ]);

        return redirect()->route('categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->count() > 0) {
            return back()->with('error', 'Kategori tidak dapat dihapus permanen karena masih memiliki produk.');
        }

        $category->forceDelete();

        return redirect()->route('categories.index')
            ->with('success', 'Kategori berhasil dihapus permanen.');
    }
}
