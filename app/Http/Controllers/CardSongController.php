<?php

namespace App\Http\Controllers;

use App\Models\CardSong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CardSongController extends Controller
{
    public function viewCards(Request $request) {
        $game_id = (int) $request->input('game_id');
        $passcode = $request->input('passcode') ?? null;
        $cardsong_obj = new CardSong($game_id);

        if (!($cardsong_obj->checkAuth($passcode))) {
            return redirect(url(''));
        }

        // determining if at least 2 existing cards exist already.
        $existing_num_of_cards = count($cardsong_obj->card_ids);
        $default_cards = env('DEFAULT_CARD_QUANTITY', 1);
        if ($existing_num_of_cards < $default_cards) {
            for($i=0;$i<$default_cards;$i++) {
                $cardsong_obj->getNewCardForGame();
                sleep(4);
            }
            $cardsong_obj->processCards();
        }

        $cardsong_obj = new CardSong($game_id);
        if (empty($request->input('round'))) {
            return view('choose-round', ['cardsong_obj' => $cardsong_obj]);
        }
        else {
            return view('view-cards', ['cardsong_obj' => $cardsong_obj, 'round' => $request->input('round')]);
        }
    }

    public function addCard(Request $request) {
        $game_id = (int) $request->game_id;
        $card_id = (int) $request->card_id;

        $cardsong_obj = new CardSong($game_id);
        $cardsong_obj->addOneCard($card_id);

        return view('view-cards', ['game_id' => $game_id]);
    }

    public function toggleSongPlayed(Request $request) {
        $game_id = (int) $request->input('game_id');
        $cardsong_obj = new CardSong($game_id);

        if (!$cardsong_obj->checkAuth(null)) {
            return view('view-cards');
        }

        $round = (int) $request->input('round');
        $song_id = (int) $request->input('song_id');
        $cur_played_status = $cardsong_obj->getPlayedStatus($song_id);
        $new_played_status = !$cur_played_status;

        try {
            DB::update(
                'update card_songs set played = ? where song_id = ?',
                [$new_played_status, $song_id]
            );
            return redirect()->action(
                [CardSongController::class, 'viewCards'],
                [
                    'cardsong_obj' => $cardsong_obj,
                    'game_id' => $cardsong_obj->game_id,
                    'round' => $round
                ]);
        } catch (\Throwable $th) {
            return false;
        }
    }

}
