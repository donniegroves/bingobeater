<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PHPHtmlParser\Dom;

class CardSong extends Model
{
    use HasFactory;

    public $game_id;
    public $rounds;
    public $card_ids;
    public $cards;
    public $songs;

    public function __construct(int $game_id) {
        $this->game_id = $game_id;
        $this->rounds = $this->getRoundsFromDB();
        $this->card_ids = $this->getCardIDsFromDB();
        $this->cards = $this->getCardsFromDB();
        $this->songs = $this->getAllSongsInGameFromDB();
    }

    public function getRoundsFromDB() {
        $rounds = DB::table('card_songs')
            ->select('round', 'round_name')
            ->groupBy('round', 'round_name')
            ->where('game_id', $this->game_id)
            ->pluck('round_name', 'round')
            ->toArray();
        return $this->rounds = $rounds;
    }

    public function getCardIDsFromDB() {
        $card_ids = DB::table('card_songs')
            ->where('game_id', $this->game_id)
            ->pluck('card_id')
            ->unique()
            ->toArray();
        return $this->card_ids = $card_ids;
    }

    public function getCardsFromDB() {
        $cards = [];
        foreach ($this->card_ids as $card_id) {
            foreach ($this->rounds as $round_num => $round) {
                $cards[$card_id][$round_num] = DB::table('card_songs')
                    ->select("song_id", "col", "row", "artist", "song_title", "played")
                    ->where('game_id', $this->game_id)
                    ->where('card_id', $card_id)
                    ->where('round', $round_num)
                    ->orderBy('row', 'asc')
                    ->orderBy('col', 'asc')
                    ->get()
                    ->toArray();
            }
        }
        return $this->cards = $cards;
    }

    public function getAllSongsInGameFromDB() {
        $songs = DB::table('card_songs')
            ->select('artist', 'song_title', 'played')
            ->groupBy('song_id', 'artist', 'song_title', 'played')
            ->where('game_id', $this->game_id)
            ->orderBy('song_title', 'asc')
            ->get();

        return $this->songs = $songs;
    }

    public function getNewCardForGame() {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => env('EXTERNAL_BINGO_URL',''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "GameID={$this->game_id}&CardID=Auto&GenerateCardID=GenerateNewID",
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

        $this->card_ids[] = (int) $card_id;

        return;
    }

    public function displayCards($round_num) {
        foreach ($this->cards as $card_id => $card) {
            $played_count = 0;
            $max_per_row = 5;
            $cur_col = 1;
            $grid = "";
            foreach ($card[$round_num] as $song){
                if ($cur_col > $max_per_row) {
                    $cur_col = 1;
                    $grid .= "<br />";
                }

                if ($song->played) {
                    $played_count++;
                    $grid .= "x";
                }
                else {
                    $grid .= "o";
                }
                $cur_col++;
            }
            $output = "<br />Card # {$card_id}<br />";
            $total_songs = count($card[$round_num]);
            $output .= "{$played_count} / {$total_songs}<br />";
            $output .= $grid . "<br />";
            echo $output;
        }
    }

    public function processCards() {
        // getting songs for each card
        foreach ($this->card_ids as $card_id) {
            $songs_by_round = $this->getSongsOnCard($card_id);
            echo '<pre>';
            foreach ($songs_by_round as $round_key => $round) {
                $_round_name = $round->Name;
                $_round = $round_key + 1;
                foreach ($round->Columns as $key => $col) {
                    $_col = $key + 1;
                    foreach ($col as $r_key => $row) {
                        $_row = $r_key + 1;
                        $_id = $row->ID;
                        $_artist = $row->FullArtist;
                        $_song_title = $row->FullTitle;

                        $rows_for_insert[] = [
                            "song_id"    => $_id,
                            "game_id"    => $this->game_id,
                            "card_id"    => $card_id,
                            "round"      => $_round,
                            "round_name" => str_replace('<BR>','-',$_round_name),
                            "col"        => $_col,
                            "row"        => $_row,
                            "artist"     => $_artist,
                            "song_title" => $_song_title,
                        ];
                    }
                }
            }
        }

        // TODO: put this above to not duplicate.
        foreach ($rows_for_insert as $row) {
            DB::table('card_songs')->insert([
                'song_id'       => $row['song_id'],
                'game_id'       => $row['game_id'],
                'card_id'       => $row['card_id'],
                'round'         => $row['round'],
                'round_name'    => $row['round_name'],
                'col'           => $row['col'],
                'row'           => $row['row'],
                'artist'        => $row['artist'],
                'song_title'    => $row['song_title'],
            ]);
        }
    }

    public function getSongsOnCard(int $card_id) {
        $aqt = $this->getAQT($this->game_id, $card_id);
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
