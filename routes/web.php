<?php

use App\Models\Post;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Post', [
        'post' => $post = Post::first(),
        'comments' => $post->comments()
            ->get()
            ->each(function ($comment) {
                $comment->depth = count(explode('.', $comment->path));
            })
    ]);

    return view('welcome');
});
