<?php

namespace App\Http\Controllers;

use App\Models\CardSong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CardSongController extends Controller
{
    public function viewCards($game_id)
    {
        $cardsong_obj = new CardSong();

        // getting card ids
        for($i=0;$i<2;$i++) {
            $card_ids[] = $cardsong_obj->getNewCardForGame((int) $game_id);
        }

        // getting songs for each card
        foreach ($card_ids as $card_id) {
            $songs[$card_id] = $cardsong_obj->getSongsOnCard((int) $game_id, (int) $card_id);
        }

        echo '<pre>';var_dump($songs);exit();

        $card_songs = DB::table('card_songs')->where('game_id', $game_id)->get();
        return view('view-cards');
    }
}
