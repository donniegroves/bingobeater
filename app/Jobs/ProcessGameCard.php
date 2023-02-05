<?php

namespace App\Jobs;

use App\Models\CardSong;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessGameCard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $game_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $game_id)
    {
        $this->game_id = $game_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $cardsong_obj = new CardSong($this->game_id);
        $existing_num_of_cards = count($cardsong_obj->card_ids);
        $default_cards = env('DEFAULT_CARD_QUANTITY', 1);

        if ($existing_num_of_cards >= $default_cards){
            return;
        }

        $card_id = $cardsong_obj->getNewCardForGame();
        $cardsong_obj->processCard($card_id);
    }
}
