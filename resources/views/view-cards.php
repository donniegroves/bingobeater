<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>View Cards</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
        <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    </head>
    <body>
        <div class="container">

        <style>
            .unplayed-box {
                border-style: solid;
                border-width: 1px;
                height: 14px;
                width: 14px;
            }
            .played-box {
                border-style: solid;
                border-width: 1px;
                height: 14px;
                width: 14px;
                background-color: red;
            }
        </style>
        
            <div class="row">
                <div class="col col-md-auto text-center">
                    <a href="<?php echo url(''); ?>">Game</a>
                </div>
                <div class="col col-md-auto text-center">
                    <a href="<?php echo url("view-cards")."?game_id={$cardsong_obj->game_id}";?>">Rounds</a>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <h1><?php echo "BingoBeater - Game {$cardsong_obj->game_id} - Round {$round}";?></h1>
                </div>
            </div>

            <div class="row">
                <?php $cardsong_obj->displayCards($round); ?>
            </div>
            
            <?php
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
        </div>
    </body>
</html>
