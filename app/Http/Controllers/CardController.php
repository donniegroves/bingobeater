<?php

namespace App\Http\Controllers;

use App\Models\Card;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function index()
    {
        return view('add-blog-post-form');
    }
    public function toggleSongPlayed(Request $request)
    {
        $post = new Card();
        $post->song_id = $request->song_id;
        $post->save();
        return redirect('view-cards')->with('status', 'Song mark toggled.');
    }
}
