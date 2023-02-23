<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Post::factory()
            ->has(Comment::factory()->count(2))
            ->create();

        Comment::factory()
            ->count(2)
            ->create([
                'parent_id' => 1
            ]);

        Comment::factory()
            ->count(2)
            ->create([
                'post_id' => 1
            ]);

        Comment::factory()
            ->count(2)
            ->create([
                'parent_id' => 1
            ]);

        Comment::factory()
            ->count(2)
            ->create([
                'parent_id' => 3
            ]);

        Comment::factory()
            ->count(2)
            ->create([
                'parent_id' => 5
            ]);
    }
}
