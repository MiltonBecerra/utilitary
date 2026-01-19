<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'utility_id',
        'user_id',
        'guest_id',
        'parent_id',
        'name',
        'email',
        'comment',
    ];

    /**
     * Get the user that owns the comment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the utility this comment belongs to.
     */
    public function utility()
    {
        return $this->belongsTo(Utility::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function reactions()
    {
        return $this->hasMany(\App\Models\CommentReaction::class);
    }
}
