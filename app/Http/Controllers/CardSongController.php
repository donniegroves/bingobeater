<?php

namespace App\Http\Controllers;

use App\Models\CardSong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CardSongController extends Controller
{
    public function viewCards(Request $request) {
        if (!is_numeric($request->input('game_id'))){
            return response('Game ID not provided.', 400);
        }

        $game_id = (int) $request->input('game_id');
        $cardsong_obj = new CardSong($game_id);

        // determining if at least 2 existing cards exist already.
        $existing_num_of_cards = $cardsong_obj->getCurrentCardCount();
        if ($existing_num_of_cards < 2) {
            for($i=0;$i<2;$i++) {
                $cardsong_obj->getNewCardForGame();
            }
            $cardsong_obj->processCards();
        }

        $songs = $cardsong_obj->getAllSongsInGame();
        $card_stats = $cardsong_obj->getCardsStats();

        return view('view-cards', [
            'songs' => $songs,
            'card_stats' => $card_stats
        ]);
    }
}
