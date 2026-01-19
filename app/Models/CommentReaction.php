<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentReaction extends Model
{
    protected $fillable = [
        'comment_id',
        'user_id',
        'guest_id',
        'type',
    ];

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }
}
