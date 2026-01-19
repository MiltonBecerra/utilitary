<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Comment;
use App\Models\Utility;
use App\Modules\Core\Services\GuestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * List comments (paginated) for infinite scroll.
     */
    public function index(Request $request, Utility $utility)
    {
        $perPage = 10;
        $comments = Comment::with(['replies', 'reactions'])
            ->where('utility_id', $utility->id)
            ->whereNull('parent_id')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $data = $comments->getCollection()->map(function (Comment $comment) {
            return [
                'id' => $comment->id,
                'utility_id' => $comment->utility_id,
                'parent_id' => $comment->parent_id,
                'name' => $comment->name,
                'email' => $comment->email,
                'comment' => $comment->comment,
                'created_at_human' => $comment->created_at->diffForHumans(),
                'replies' => $comment->replies->map(function ($reply) {
                    return [
                        'id' => $reply->id,
                        'parent_id' => $reply->parent_id,
                        'name' => $reply->name,
                        'email' => $reply->email,
                        'comment' => $reply->comment,
                        'created_at_human' => $reply->created_at->diffForHumans(),
                    ];
                }),
                'reactions' => $comment->reactions->groupBy('type')->map->count(),
            ];
        });

        return response()->json([
            'data' => $data,
            'next_page_url' => $comments->nextPageUrl(),
        ]);
    }

    /**
     * Store a new comment associated to a utility.
     */
    public function store(Request $request, Utility $utility, GuestService $guestService)
    {
        // Ensure authenticated users always send their name/email even if not in the form payload
        if (Auth::check()) {
            $request->merge([
                'name' => $request->input('name', Auth::user()->name),
                'email' => Auth::user()->email,
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'comment' => 'required|string',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $data = $request->only(['name', 'email', 'comment']);
        $data['utility_id'] = $utility->id;
        $data['parent_id'] = $request->parent_id;

        if (Auth::check()) {
            $data['user_id'] = Auth::id();
        } else {
            $data['guest_id'] = $guestService->getGuestId();
        }

        $comment = Comment::create($data);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'comment' => [
                    'id' => $comment->id,
                    'utility_id' => $comment->utility_id,
                    'parent_id' => $comment->parent_id,
                    'name' => $comment->name,
                    'email' => $comment->email,
                    'comment' => $comment->comment,
                    'created_at_human' => $comment->created_at->diffForHumans(),
                ],
            ]);
        }

        return back()->with('success', 'Comentario enviado correctamente.');
    }
}



