<?php

declare(strict_types=1);

namespace javamapconverter\event;

use pocketmine\event\Cancellable;
use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class PlayerSendHeadEvent extends PlayerEvent implements Cancellable {
    public function __construct(
        Player $player
    ){
        $this->player = $player;
    }
}