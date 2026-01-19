<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Comment;
use App\Models\CommentReaction;
use App\Modules\Core\Services\GuestService;
use Illuminate\Support\Facades\Auth;

class CommentsSection extends Component
{
    public $utilityId;
    public $comments;

    public $name;
    public $email;
    public $commentText;

    public $replyingTo = null;
    public $replyName;
    public $replyEmail;
    public $replyText;

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'nullable|email|max:255',
        'commentText' => 'required|string',
    ];

    public function mount($utility)
    {
        $this->utilityId = $utility->id;
        $this->prefillUser();
        $this->loadComments();
    }

    public function render()
    {
        return view('livewire.comments-section');
    }

    protected function prefillUser()
    {
        if (Auth::check()) {
            $this->name = Auth::user()->name;
            $this->email = Auth::user()->email;
        }
    }

    protected function loadComments()
    {
        $this->comments = Comment::with(['replies', 'reactions'])
            ->where('utility_id', $this->utilityId)
            ->whereNull('parent_id')
            ->latest()
            ->get();
    }

    public function submitComment(GuestService $guestService)
    {
        $this->validate();

        $data = [
            'utility_id' => $this->utilityId,
            'name' => $this->name,
            'email' => $this->email,
            'comment' => $this->commentText,
        ];

        if (Auth::check()) {
            $data['user_id'] = Auth::id();
        } else {
            $data['guest_id'] = $guestService->getGuestId();
        }

        Comment::create($data);
        $this->commentText = '';
        $this->loadComments();
    }

    public function startReply($commentId)
    {
        $this->replyingTo = $commentId;
        $this->replyText = '';
        $this->prefillReply();
    }

    protected function prefillReply()
    {
        if (Auth::check()) {
            $this->replyName = Auth::user()->name;
            $this->replyEmail = Auth::user()->email;
        }
    }

    public function submitReply(GuestService $guestService)
    {
        $this->validate([
            'replyName' => 'required|string|max:255',
            'replyEmail' => 'nullable|email|max:255',
            'replyText' => 'required|string',
        ]);

        if (!$this->replyingTo) {
            return;
        }

        $data = [
            'utility_id' => $this->utilityId,
            'parent_id' => $this->replyingTo,
            'name' => $this->replyName,
            'email' => $this->replyEmail,
            'comment' => $this->replyText,
        ];

        if (Auth::check()) {
            $data['user_id'] = Auth::id();
        } else {
            $data['guest_id'] = $guestService->getGuestId();
        }

        Comment::create($data);

        $this->replyingTo = null;
        $this->replyText = '';
        $this->loadComments();
    }

    public function react($commentId, $type, GuestService $guestService)
    {
        $allowed = ['like', 'sad', 'laugh', 'angry'];
        if (!in_array($type, $allowed)) {
            return;
        }

        $where = ['comment_id' => $commentId];

        if (Auth::check()) {
            $where['user_id'] = Auth::id();
        } else {
            $where['guest_id'] = $guestService->getGuestId();
        }

        CommentReaction::updateOrCreate($where, ['type' => $type]);
        $this->loadComments();
    }
}
