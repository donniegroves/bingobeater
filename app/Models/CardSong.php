<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cookie;
use PHPHtmlParser\Dom;

class CardSong extends Model
{
    use HasFactory;

    public $game_id;
    public $rounds;
    public $card_ids;
    public $cards;

    public function __construct(int $game_id) {
        $this->game_id = $game_id;
        $this->rounds = $this->getRoundsFromDB();
        $this->card_ids = $this->getCardIDsFromDB();
        $this->cards = $this->getCardsFromDB();
    }

    /**
     * Verifies that passcode is set in environment, and that passcode matches it.
     *
     * @param string $passcode
     * @return boolean
     */
    public function verifyPasscode(string $passcode): bool {
        if (empty(getenv('BINGO_PASSCODE')) || $passcode !== getenv('BINGO_PASSCODE')) {
            return false;
        }
        return true;       
    }

    /**
     * adds cookie into queue for correct passcode.
     *
     * @param string $passcode
     * @return void
     */
    public function setPasscodeCookie(string $passcode): void {
        Cookie::queue('passcode', $passcode, 240);
    }

    /**
     * Checks that passcode environment var is set, and that cookie matches that passcode.
     *
     * @return boolean
     */
    public function checkCookie(): bool {
        if (empty(getenv('BINGO_PASSCODE')) || Cookie::get('passcode') !== getenv('BINGO_PASSCODE')) {
            return false;
        }
        return true;
    }

    /**
     * Sets $this->rounds, and returns an array of rounds with
     * their corresponding name using the preset game_id.
     *
     * @return array
     */
    public function getRoundsFromDB(): array {
        $rounds = DB::table('card_songs')
            ->select('round', 'round_name')
            ->groupBy('round', 'round_name')
            ->where('game_id', $this->game_id)
            ->pluck('round_name', 'round')
            ->toArray();
        return $this->rounds = $rounds;
    }

    /**
     * Sets $this->card_ids and returns an array of card ids within database
     * matching the preset game_id.
     *
     * @return array
     */
    public function getCardIDsFromDB(): array {
        $card_ids = DB::table('card_songs')
            ->where('game_id', $this->game_id)
            ->pluck('card_id')
            ->unique()
            ->toArray();
        return $this->card_ids = $card_ids;
    }

    /**
     * Sets $this->cards and returns all information related to the game_id
     * organized by card_id and round_num.
     *
     * @return array
     */
    public function getCardsFromDB(): array {
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

    /**
     * Returns true/false of whether song_id has been played or not.
     *
     * @param integer $song_id
     * @return boolean
     */
    public static function getPlayedStatus(int $song_id): bool {
        $played_status = DB::table('card_songs')
            ->select("played")
            ->where('song_id', $song_id)
            ->pluck('played')
            ->toArray();

        return (bool) $played_status[0];
    }

    public function displayCards($round_num) {
        $sorted_card_ids = [];
        foreach ($this->cards as $card_id => $card) {
            $play_count = 0;
            foreach ($card[$round_num] as $song){
                if ($song->played) {
                    $play_count++;
                }
            }
            $sorted_card_ids[$card_id] = $play_count;
        }
        arsort($sorted_card_ids);    
        $sorted_card_ids = array_keys($sorted_card_ids);

        foreach ($sorted_card_ids as $card_id) {
            $output = '<div class="col-3 mb-4 gx-3">';
            $output .= "<div class=\"row\"><div class=\"col\"><h4>#<a href=\"".env('EXTERNAL_BINGO_URL','')."?GameID={$this->game_id}&CardID=Auto&GenerateCardID={$card_id}\">{$card_id}</a></h4></div></div>";

            $max_per_row = 5;
            $cur_col = 1;
            foreach ($this->cards[$card_id][$round_num] as $song) {
                if ($cur_col == 1) {
                    $output .= "<div class='row m-0 p-0'>";
                }

                if ($song->played) {
                    $output .= "<div class=\"col-auto p-0 played-box\" title=\"{$song->song_title} - {$song->artist}\"></div>";
                }
                else {
                    $output .= "<div class=\"col-auto p-0 unplayed-box\" title=\"{$song->song_title} - {$song->artist}\"></div>";
                }

                if ($cur_col == $max_per_row) {
                    $output .= "</div>";
                    $cur_col = 0;
                }
                $cur_col++;
            }
            $output .= '</div>';
            echo $output;
        }
    }

    public function addOneCard(int $card_id) {
        $this->addSongsForCard($card_id);
    }

    public function addSongsForCard(int $card_id) {
        $songs_by_round = $this->getSongsOnCard($card_id);
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
                        "round_name" => str_replace(['<BR>', '<br>'],'-',$_round_name),
                        "col"        => $_col,
                        "row"        => $_row,
                        "artist"     => $_artist,
                        "song_title" => $_song_title,
                    ];
                }
            }
        }

        $played_song_ids = self::getPlayedSongIDs($this->game_id);

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
                'played'        => in_array($row['song_id'], $played_song_ids) ? 1 : 0
            ]);
        }
    }

    private function getPlayedSongIDs(int $game_id) {
        $played_songs = DB::table('card_songs')
            ->select("song_id")
            ->where('game_id', $game_id)
            ->where('played', 1)
            ->pluck('song_id')
            ->toArray();

        return array_unique($played_songs);
    }

    public function processCards() {
        foreach ($this->card_ids as $card_id) {
            $this->addSongsForCard($card_id);
        }
    }

    public function getNewCardForGame(): void {
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
