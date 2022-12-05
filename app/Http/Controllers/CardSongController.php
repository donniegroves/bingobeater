<?php

namespace App\Http\Controllers;

use App\Models\CardSong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CardSongController extends Controller
{
    public function viewCards($game_id) {
        $cardsong_obj = new CardSong((int) $game_id);

        // TODO write getCurrentCardCount and replace below with: $existing_num_of_cards = $cardsong_obj->getCurrentCardCount();
        $cards = DB::table('card_songs')
            ->select('card_id')
            ->groupBy('card_id')
            ->where('game_id', $game_id)
            ->orderBy('card_id', 'asc')
            ->get();

        $existing_num_of_cards = count($cards);

        if ($existing_num_of_cards < 2) {
            // getting card ids
            for($i=0;$i<2;$i++) {
                $cardsong_obj->getNewCardForGame();
            }

            $cardsong_obj->processCards();
        }

        $songs = DB::table('card_songs')
            ->select('artist', 'song_title', 'played')
            ->groupBy('song_id', 'artist', 'song_title', 'played')
            ->where('game_id', $game_id)
            ->orderBy('song_title', 'asc')
            ->get();

        $cards = DB::table('card_songs')
            ->select('card_id')
            ->groupBy('card_id')
            ->where('game_id', $game_id)
            ->orderBy('card_id', 'asc')
            ->get();

        return view('view-cards', [
            'songs' => $songs,
            'cards' => $cards
        ]);
    }
}
