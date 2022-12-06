<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PHPHtmlParser\Dom;

class CardSong extends Model
{
    use HasFactory;

    private $game_id;
    private $card_ids;

    public function __construct(int $game_id)
    {
        $this->game_id = $game_id;
        $this->card_ids = [];
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

    public function getAllSongsInGame() {
        $songs = DB::table('card_songs')
            ->select('artist', 'song_title', 'played')
            ->groupBy('song_id', 'artist', 'song_title', 'played')
            ->where('game_id', $this->game_id)
            ->orderBy('song_title', 'asc')
            ->get();

        return $songs;
    }

    public function getCardsStats() {
        $card_ids = DB::table('card_songs')
            ->where('game_id', $this->game_id)
            ->pluck('card_id')
            ->unique()
            ->toArray();

        $output = [];
        foreach ($card_ids as $card_id) {
            $output[$card_id] = [
                "played"    => null,
                "unplayed"  => null
            ];
        }

        $card_stats_played = DB::table('card_songs')
            ->select(DB::raw('COUNT(played) as played_count'))
            ->addSelect('card_id')
            ->where('game_id', $this->game_id)
            ->where('played', 1)
            ->whereIn('card_id', $card_ids)
            ->groupBy('card_id')
            ->get()
            ->toArray();
        $card_stats_unplayed = DB::table('card_songs')
            ->select(DB::raw('COUNT(played) as unplayed_count'))
            ->addSelect('card_id')
            ->where('game_id', $this->game_id)
            ->where('played', 0)
            ->whereIn('card_id', $card_ids)
            ->groupBy('card_id')
            ->get()
            ->toArray();

        foreach ($card_stats_played as $played) {
            $output[$played->card_id]["played"] = $played->played_count;
        }
        foreach ($card_stats_unplayed as $unplayed) {
            $output[$unplayed->card_id]["unplayed"] = $unplayed->unplayed_count;
        }
        
        return $output;
    }

    public function getCurrentCardCount() {
        return DB::table('card_songs')
            ->select('card_id')
            ->where('game_id', $this->game_id)
            ->groupBy('card_id')
            ->get()
            ->count();
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
