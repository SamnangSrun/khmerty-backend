<?php

namespace App\Http\Controllers;

use App\Models\NewsPost;
use App\Models\NewsCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class NewsPostController extends Controller
{
    // Get all published posts (public)
    public function index(Request $request)
    {
        $query = NewsPost::with(['category', 'author:id,name'])
            ->where('status', 'published');

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $posts = $query->latest('published_at')->paginate(12);

        return response()->json($posts);
    }

    // Get single post by slug (public)
    public function show($slug)
    {
        $post = NewsPost::with(['category', 'author:id,name,email'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        // Increment views
        $post->increment('views_count');

        return response()->json($post);
    }

    // Create new post (admin only)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:news_categories,id',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        // Generate slug
        $slug = Str::slug($validated['title']);
        $originalSlug = $slug;
        $counter = 1;
        
        while (NewsPost::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Upload images to local storage
        $imageUrls = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('news_posts', $filename, 'public');
                $imageUrls[] = Storage::url($path);
            }
        }

        $post = NewsPost::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'category_id' => $validated['category_id'],
            'author_id' => auth()->id(),
            'content' => $validated['content'] ?? '',
            'excerpt' => $validated['excerpt'] ?? '',
            'images' => $imageUrls,
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'published' ? now() : null,
        ]);

        return response()->json([
            'message' => 'Post created successfully',
            'post' => $post->load(['category', 'author'])
        ], 201);
    }

    // Update post (admin only)
    public function update(Request $request, $id)
    {
        $post = NewsPost::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:news_categories,id',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string',
            'status' => 'sometimes|in:draft,published,archived',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        // Update slug if title changed
        if (isset($validated['title']) && $validated['title'] !== $post->title) {
            $slug = Str::slug($validated['title']);
            $originalSlug = $slug;
            $counter = 1;
            
            while (NewsPost::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        // Upload new images if provided
        if ($request->hasFile('images')) {
            $imageUrls = [];
            foreach ($request->file('images') as $image) {
                $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('news_posts', $filename, 'public');
                $imageUrls[] = Storage::url($path);
            }
            $validated['images'] = array_merge($post->images ?? [], $imageUrls);
        }

        // Update published_at if status changed to published
        if (isset($validated['status']) && $validated['status'] === 'published' && $post->status !== 'published') {
            $validated['published_at'] = now();
        }

        $post->update($validated);

        return response()->json([
            'message' => 'Post updated successfully',
            'post' => $post->load(['category', 'author'])
        ]);
    }

    // Delete post (admin only)
    public function destroy($id)
    {
        $post = NewsPost::findOrFail($id);
        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }

    // Increment share count
    public function share($id)
    {
        $post = NewsPost::findOrFail($id);
        $post->increment('shares_count');

        return response()->json([
            'message' => 'Share count incremented',
            'shares_count' => $post->shares_count
        ]);
    }

    // Get all posts for admin (includes drafts)
    public function adminIndex(Request $request)
    {
        $query = NewsPost::with(['category', 'author:id,name']);

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $posts = $query->latest()->paginate(20);

        return response()->json($posts);
    }

    // Get single post for editing (admin)
    public function adminShow($id)
    {
        $post = NewsPost::with(['category', 'author'])->findOrFail($id);
        return response()->json($post);
    }
}
