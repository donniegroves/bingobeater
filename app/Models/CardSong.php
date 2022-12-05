<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPHtmlParser\Dom;

class CardSong extends Model
{
    use HasFactory;

    public function getNewCardForGame(int $game_id) {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => env('EXTERNAL_BINGO_URL',''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "GameID={$game_id}&CardID=Auto&GenerateCardID=GenerateNewID",
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/x-www-form-urlencoded"
            ],
            CURLOPT_SSL_VERIFYPEER => 0
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $dom = new Dom;
        $dom->loadStr($response);
        $card_id = $dom->find(".container input[name='CardID']")->getAttribute('value');

        return (int) $card_id;
    }

    public function getSongsOnCard(int $game_id, int $card_id) {
        $aqt = $this->getAQT($game_id, $card_id);
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => env('EXTERNAL_BINGO_URL','')."?AQT={$aqt}&Field=JSON",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/x-www-form-urlencoded"
            ],
            CURLOPT_SSL_VERIFYPEER => 0
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response)->Rounds;
    }

    public function toggleSongPlayed($song_id) {
        return redirect('view-cards')->with('status', 'Song mark toggled.');
    }

    private function getAQT($game_id, $card_id) {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => env('EXTERNAL_BINGO_URL',''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "GameID={$game_id}&CardID={$card_id}",
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/x-www-form-urlencoded"
            ],
            CURLOPT_SSL_VERIFYPEER => 0
        ]);

        $response = curl_exec($curl);
        curl_close($curl);
        preg_match('/(?<=LSID\(\) { return ")\d*(?="; })/', $response, $matches);
        return (int) $matches[0];
    }
}
