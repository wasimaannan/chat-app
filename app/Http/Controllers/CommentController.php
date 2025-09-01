<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request, $postId)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }
        $post = Post::findOrFail($postId);
        $comment = new Comment();
        $comment->user_id = $user->id;
        $comment->post_id = $post->id;
        $comment->content = $request->content;
        $comment->save();
        return redirect()->route('posts.show', $post->id)->with('success', 'Comment added!');
    }
}
