<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>BingoBeater - Welcome</title>
    </head>
    <body>
        <h1>BingoBeater</h1>
        
        <div>
            <h3>Game ID / Passcode</h3>
            <form action="view-cards" method="get">
                <input type="text" name="game_id" id="game_id">
                <input type="text" name="passcode" id="passcode">
                <a href="{{ url('view-cards') }}">
                    <button type="submit">Submit</button>
                </a>
            </form>
        </div>
        <div>
            <h3>Reset</h3>
            <button type="submit">Reset All</button>
        </div>
    </body>
</html>
