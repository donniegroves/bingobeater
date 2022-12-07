<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>View Cards</title>
    </head>
    <body>
        <h1>BingoBeater - View Cards</h1>
        Rounds | Game
        <?php
            $cardsong_obj->displayCards($round);
            foreach ($cardsong_obj->cards as $card) {
                foreach ($card[$round] as $song) {
                    echo $song->played ? '<strong>' : '';
                    echo "<a href=\"". url('toggle-played') ."?song_id={$song->song_id}\">{$song->song_title} - {$song->artist}</a><br />";
                    echo $song->played ? '</strong>' : '';
                }
            }
        ?>
    </body>
</html>
