<?php

namespace App\Http\Controllers;

use App\Models\CardSong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CardSongController extends Controller
{
    public function viewCards(Request $request) {
        $game_id = (int) $request->input('game_id');
        $cardsong_obj = new CardSong($game_id);

        // determining if at least 2 existing cards exist already.
        $existing_num_of_cards = count($cardsong_obj->card_ids);
        if ($existing_num_of_cards < 2) {
            for($i=0;$i<2;$i++) {
                $cardsong_obj->getNewCardForGame();
            }
            $cardsong_obj->processCards();
        }

        if (empty($request->input('round'))) {
            return view('choose-round', ['cardsong_obj' => $cardsong_obj]);
        }
        else {
            return view('view-cards', ['cardsong_obj' => $cardsong_obj, 'round' => $request->input('round')]);
        }
    }

    public function toggleSongPlayed(Request $request) {
        $song_id = (int) $request->input('song_id');
        $cur_played_status = CardSong::getPlayedStatus($song_id);
        $new_played_status = !$cur_played_status;

        try {
            DB::update(
                'update card_songs set played = ? where song_id = ?',
                [$new_played_status, $song_id]
            );
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

}
