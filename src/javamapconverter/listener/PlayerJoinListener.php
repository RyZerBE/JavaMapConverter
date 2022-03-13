<?php

declare(strict_types=1);

namespace javamapconverter\listener;

use javamapconverter\entity\SkullEntity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class PlayerJoinListener implements Listener {
    public function onPlayerJoin(PlayerJoinEvent $event): void{
        $player = $event->getPlayer();
        foreach($player->getLevel()->getEntities() as $entity) {
            if(!$entity instanceof SkullEntity) continue;
            $entity->sendSpawnPacket($player);
        }
    }
}