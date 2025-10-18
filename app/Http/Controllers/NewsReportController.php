<?php

namespace App\Http\Controllers;

use App\Models\NewsReport;
use App\Models\NewsPost;
use Illuminate\Http\Request;

class NewsReportController extends Controller
{
    // Submit a report (authenticated users)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'news_post_id' => 'required|exists:news_posts,id',
            'reason' => 'required|string|in:spam,misleading,inappropriate,copyright,other',
            'description' => 'required|string|max:500',
        ]);

        // Check if user already reported this post
        $existingReport = NewsReport::where('news_post_id', $validated['news_post_id'])
            ->where('user_id', auth()->id())
            ->first();

        if ($existingReport) {
            return response()->json([
                'message' => 'You have already reported this post'
            ], 422);
        }

        $report = NewsReport::create([
            'news_post_id' => $validated['news_post_id'],
            'user_id' => auth()->id(),
            'reason' => $validated['reason'],
            'description' => $validated['description'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Report submitted successfully',
            'report' => $report
        ], 201);
    }

    // Get all reports (admin only)
    public function index(Request $request)
    {
        $query = NewsReport::with(['newsPost:id,title,slug', 'user:id,name,email']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by reason
        if ($request->has('reason')) {
            $query->where('reason', $request->reason);
        }

        $reports = $query->latest()->paginate(20);

        return response()->json($reports);
    }

    // Update report status (admin only)
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,reviewed,resolved,dismissed',
            'admin_notes' => 'nullable|string',
        ]);

        $report = NewsReport::findOrFail($id);
        $report->update($validated);

        return response()->json([
            'message' => 'Report status updated',
            'report' => $report
        ]);
    }

    // Get reports for a specific post (admin only)
    public function getPostReports($postId)
    {
        $reports = NewsReport::with('user:id,name,email')
            ->where('news_post_id', $postId)
            ->latest()
            ->get();

        return response()->json($reports);
    }

    // Delete report (admin only)
    public function destroy($id)
    {
        $report = NewsReport::findOrFail($id);
        $report->delete();

        return response()->json(['message' => 'Report deleted successfully']);
    }
}
