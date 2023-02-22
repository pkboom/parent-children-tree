<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted()
    {
        static::created(function ($comment) {
            $parent = $comment->parent;

            if (! $comment->post_id) {
                $comment->post_id = $parent->post_id;
            }

            $comment->update([
                'path' => $parent ? $parent->path. '.'. $comment->id : $comment->id
            ]);
        });
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}
