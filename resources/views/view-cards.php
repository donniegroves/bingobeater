<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>View Cards</title>
    </head>
    <body>
        <h1><?php echo "BingoBeater - Game {$cardsong_obj->game_id} - Round {$round}";?></h1>
        <h2>View Cards</h2>
        <a href="<?php echo url("view-cards")."?game_id={$cardsong_obj->game_id}";?>">Rounds</a>
         | <a href="<?php echo url(''); ?>">Game</a>
        <?php
            $cardsong_obj->displayCards($round);
            $unique_songs = [];
            foreach ($cardsong_obj->cards as $card) {
                foreach ($card[$round] as $song) {
                    $unique_songs[$song->song_id] = $song;
                }
            }

            usort($unique_songs, fn($a, $b) => $a->song_title <=> $b->song_title);
            foreach ($unique_songs as $song) {
                echo $song->played ? '<strong>' : '';
                echo "<a href=\"". url('toggle-played') ."?game_id={$cardsong_obj->game_id}&round={$round}&song_id={$song->song_id}\">{$song->song_title} - {$song->artist}</a><br />";
                echo $song->played ? '</strong>' : '';
            }
            
        ?>
    </body>
</html>
