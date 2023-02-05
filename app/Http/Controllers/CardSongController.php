<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessGameCard;
use App\Models\CardSong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CardSongController extends Controller
{
    public function viewCards(Request $request) {
        $game_id = (int) $request->input('game_id');
        $passcode = $request->input('passcode') ?? null;
        $cardsong_obj = new CardSong($game_id);

        if (!empty($passcode) && $cardsong_obj->verifyPasscode($passcode)) {
            $cardsong_obj->setPasscodeCookie($passcode);
        }
        else {
            if (!$cardsong_obj->checkCookie()){
                return redirect('/');
            };
        }

        $existing_num_of_cards = count($cardsong_obj->card_ids);
        $default_cards = env('DEFAULT_CARD_QUANTITY', 1);
        if ($existing_num_of_cards < $default_cards) {
            for($i=$existing_num_of_cards;$i<$default_cards;$i++) {
                ProcessGameCard::dispatch($game_id);
            }
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

        return redirect()->action(
            [CardSongController::class, 'viewCards'],
            [
                'cardsong_obj' => $cardsong_obj,
                'game_id' => $cardsong_obj->game_id
            ]);
    }

    public function toggleSongPlayed(Request $request) {
        $game_id = (int) $request->input('game_id');
        $cardsong_obj = new CardSong($game_id);

        if (!$cardsong_obj->checkCookie()){
            return redirect('/');
        };

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
