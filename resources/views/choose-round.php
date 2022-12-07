<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>BingoBeater - Rounds</title>
    </head>
    <body>
        <h1>BingoBeater</h1>
        <a href="<?php echo url(''); ?>">Game</a>
        <div>
            <?php
                foreach ($cardsong_obj->rounds as $round_num => $round_name) {
                    echo "<a href=\"view-cards?game_id={$cardsong_obj->game_id}&round={$round_num}\">{$round_name}</a><br />";
                }
            ?>
        </div>
    </body>
</html>