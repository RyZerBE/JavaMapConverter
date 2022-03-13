<?php

declare(strict_types=1);

namespace javamapconverter\scheduler;

use javamapconverter\entity\SkullEntity;
use javamapconverter\event\PlayerSendHeadEvent;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use ryzerbe\core\player\RyZerPlayerProvider;
use function array_shift;

class SkullSendTask extends Task {
    public const HEADS_PER_PLAYER = 1;

    protected static array $queue = [];

    public function onRun(int $currentTick): void{
        foreach(Server::getInstance()->getOnlinePlayers() as $player) {
            $ryZerPlayer = RyZerPlayerProvider::getRyzerPlayer($player);
            if(!$player->spawned || $ryZerPlayer === null || $ryZerPlayer->getGameTimeTicks() <= 100) continue;
            for($i = 1; $i <= self::HEADS_PER_PLAYER; $i++) {
                $entity = self::shiftEntry($player);
                if($entity === null || $entity->isClosed()) break;

                $event = new PlayerSendHeadEvent($player);
                $event->call();
                if($event->isCancelled()) break;
                $entity->__sendSpawnPacket($player);
            }
        }
    }

    public static function addEntry(SkullEntity $entity, Player $player): void {
        self::$queue[$player->getName()][] = $entity;
    }

    public static function shiftEntry(Player $player): ?SkullEntity {
        if(empty( self::$queue[$player->getName()])) return null;
        return array_shift( self::$queue[$player->getName()]);
    }
}