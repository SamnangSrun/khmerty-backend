<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Get user's notifications
    public function index()
    {
        return auth()->user()->notifications()->latest()->get();
    }

    // Get unread count
    public function unreadCount()
    {
        $count = auth()->user()->notifications()
            ->where('status', 'unread')
            ->count();

        return response()->json(['count' => $count]);
    }

    // Create notification (admin only)
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'send_to_all' => 'boolean',
            'message' => 'required|string',
            'type' => 'nullable|string|in:info,success,warning,error',
        ]);

        // Send to all users
        if ($request->send_to_all) {
            $users = User::all();
            foreach ($users as $user) {
                Notification::create([
                    'user_id' => $user->id,
                    'message' => $request->message,
                    'type' => $request->type ?? 'info',
                ]);
            }
            
            return response()->json([
                'message' => 'Notification sent to all users successfully.',
                'count' => $users->count()
            ]);
        }

        // Send to specific user
        if ($request->user_id) {
            Notification::create([
                'user_id' => $request->user_id,
                'message' => $request->message,
                'type' => $request->type ?? 'info',
            ]);

            return response()->json(['message' => 'Notification sent successfully.']);
        }

        return response()->json(['message' => 'Please specify user_id or set send_to_all'], 400);
    }

    // Mark single notification as read
    public function markAsRead($id)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $notification->update(['status' => 'read']);

        return response()->json(['message' => 'Notification marked as read.']);
    }

    // Mark all notifications as read
    public function markAllAsRead()
    {
        auth()->user()->notifications()
            ->where('status', 'unread')
            ->update(['status' => 'read']);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    // Delete notification
    public function destroy($id)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $notification->delete();

        return response()->json(['message' => 'Notification deleted.']);
    }

    // Get all notifications (admin only)
    public function getAllNotifications(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = Notification::with('user:id,name,email');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $notifications = $query->latest()->paginate(50);

        return response()->json($notifications);
    }

    // Get sent notifications history (admin only)
    public function getSentNotifications()
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get unique notifications with user info
        // Group by message, type, and created_at to show unique sent notifications
        $notifications = Notification::with('user:id,name,email')
            ->select('id', 'user_id', 'message', 'type', 'created_at')
            ->latest()
            ->limit(50)
            ->get()
            ->map(function ($notification) {
                // Check if this notification was sent to all users
                $sameMessageCount = Notification::where('message', $notification->message)
                    ->where('type', $notification->type)
                    ->whereBetween('created_at', [
                        $notification->created_at->copy()->subSeconds(5),
                        $notification->created_at->copy()->addSeconds(5)
                    ])
                    ->count();
                
                $totalUsers = User::count();
                
                return [
                    'id' => $notification->id,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'created_at' => $notification->created_at,
                    'sent_to_all' => $sameMessageCount >= $totalUsers,
                    'user' => $notification->user,
                ];
            })
            // Remove duplicates based on message and created_at
            ->unique(function ($item) {
                return $item['message'] . $item['created_at']->timestamp;
            })
            ->values();

        return response()->json([
            'notifications' => $notifications
        ]);
    }
}