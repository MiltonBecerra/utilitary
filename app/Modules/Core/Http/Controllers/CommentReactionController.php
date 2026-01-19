<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Comment;
use App\Models\CommentReaction;
use App\Modules\Core\Services\GuestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentReactionController extends Controller
{
    public function store(Request $request, Comment $comment, GuestService $guestService)
    {
        $request->validate([
            'type' => 'required|string|max:20',
        ]);

        $data = [
            'comment_id' => $comment->id,
            'type' => $request->type,
        ];

        if (Auth::check()) {
            $data['user_id'] = Auth::id();
        } else {
            $data['guest_id'] = $guestService->getGuestId();
        }

        // Avoid duplicate reaction of the same type by the same actor
        CommentReaction::updateOrCreate(
            [
                'comment_id' => $comment->id,
                'user_id' => $data['user_id'] ?? null,
                'guest_id' => $data['guest_id'] ?? null,
            ],
            ['type' => $request->type]
        );

        $counts = $comment->reactions()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['counts' => $counts]);
        }

        return back();
    }
}



