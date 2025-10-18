<?php

namespace App\Http\Controllers;

use App\Models\NewsCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class NewsCategoryController extends Controller
{
    // Get all active categories (public)
    public function index()
    {
        $categories = NewsCategory::withCount('publishedPosts')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    // Get all categories (admin)
    public function adminIndex()
    {
        $categories = NewsCategory::withCount('publishedPosts')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($categories);
    }

    // Create category (admin only)
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:news_categories,name',
                'url' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'is_active' => 'boolean',
            ]);

            // Generate URL if not provided
            if (empty($validated['url'])) {
                $validated['url'] = Str::slug($validated['name']);
            }

            // Upload icon to local storage
            if ($request->hasFile('icon')) {
                $file = $request->file('icon');
                $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('news_categories', $filename, 'public');
                $validated['icon'] = Storage::url($path);
            }

            $category = NewsCategory::create($validated);

            return response()->json([
                'message' => 'Category created successfully',
                'category' => $category
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Category creation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update category (admin only)
    public function update(Request $request, $id)
    {
        $category = NewsCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:news_categories,name,' . $id,
            'url' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'boolean',
        ]);

        // Update URL if name changed
        if (isset($validated['name']) && empty($validated['url'])) {
            $validated['url'] = Str::slug($validated['name']);
        }

        // Upload new icon if provided
        if ($request->hasFile('icon')) {
            // Delete old icon if exists
            if ($category->icon) {
                $oldPath = str_replace('/storage/', '', parse_url($category->icon, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }
            
            $file = $request->file('icon');
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('news_categories', $filename, 'public');
            $validated['icon'] = Storage::url($path);
        }

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category
        ]);
    }

    // Delete category (admin only)
    public function destroy($id)
    {
        $category = NewsCategory::findOrFail($id);

        // Check if category has posts
        if ($category->posts()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category with existing posts. Please reassign or delete the posts first.'
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}