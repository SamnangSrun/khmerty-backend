<?php

namespace App\Http\Controllers;

use App\Models\NewsPost;
use Illuminate\Http\Request;

class SavedNewsController extends Controller
{
    // Get user's saved news
    public function index()
    {
        $savedNews = auth()->user()
            ->savedNews()
            ->with(['category', 'author:id,name'])
            ->latest('saved_news.created_at')
            ->paginate(12);

        return response()->json($savedNews);
    }

    // Save news
    public function store(Request $request)
    {
        $request->validate([
            'news_post_id' => 'required|exists:news_posts,id',
        ]);

        $user = auth()->user();
        $postId = $request->news_post_id;

        // Check if already saved
        if ($user->savedNews()->where('news_post_id', $postId)->exists()) {
            return response()->json([
                'message' => 'News already saved',
                'is_saved' => true
            ]);
        }

        // Save
        $user->savedNews()->attach($postId);
        
        return response()->json([
            'message' => 'News saved successfully',
            'is_saved' => true
        ], 201);
    }

    // Remove saved news
    public function destroy($postId)
    {
        $user = auth()->user();
        
        // Check if saved
        if (!$user->savedNews()->where('news_post_id', $postId)->exists()) {
            return response()->json([
                'message' => 'News not found in saved list',
            ], 404);
        }

        // Unsave
        $user->savedNews()->detach($postId);
        
        return response()->json([
            'message' => 'News removed from saved',
            'is_saved' => false
        ]);
    }

    // Save/Unsave news
    public function toggle(Request $request)
    {
        $request->validate([
            'news_post_id' => 'required|exists:news_posts,id',
        ]);

        $user = auth()->user();
        $postId = $request->news_post_id;

        // Check if already saved
        if ($user->savedNews()->where('news_post_id', $postId)->exists()) {
            // Unsave
            $user->savedNews()->detach($postId);
            
            return response()->json([
                'message' => 'News removed from saved',
                'is_saved' => false
            ]);
        } else {
            // Save
            $user->savedNews()->attach($postId);
            
            return response()->json([
                'message' => 'News saved successfully',
                'is_saved' => true
            ]);
        }
    }

    // Check if news is saved
    public function checkSaved($postId)
    {
        $isSaved = auth()->user()
            ->savedNews()
            ->where('news_post_id', $postId)
            ->exists();

        return response()->json(['is_saved' => $isSaved]);
    }
}
