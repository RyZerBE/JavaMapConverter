<?php

namespace javamapconverter\listener;

use javamapconverter\skull\SkullChunk;
use javamapconverter\skull\SkullChunkManager;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\Listener;
use pocketmine\level\Position;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use function file_get_contents;
use function gzdecode;
use function is_file;
use function is_null;
use function unlink;

class LevelLoadListener implements Listener {
    public function onLevelLoad(LevelLoadEvent $event): void {
        $level = $event->getLevel();
        if(!is_file("worlds/" . $level->getFolderName() . "/skulls.dat")) return;
        $skulls = file_get_contents("worlds/" . $level->getFolderName() . "/skulls.dat");
        $rawLevelData = gzdecode($skulls);

        $nbt = new BigEndianNBTStream();
        $headData = $nbt->read($rawLevelData);

        foreach($headData->getValue() as $value) {
            if(!$value instanceof ListTag) continue;
            foreach($value->getValue() as $namedTag) {
                if(!$namedTag instanceof CompoundTag) continue;
                $uuid = $namedTag->getTag("uuid")->getValue();
                $x = $namedTag->getTag("x")->getValue();
                $y = $namedTag->getTag("y")->getValue();
                $z = $namedTag->getTag("z")->getValue();
                $position = new Position($x, $y, $z, $level);

                $skullChunk = SkullChunkManager::getInstance()->getChunkByXZ($level, $x, $z);
                if(is_null($skullChunk)) {
                    $skullChunk = new SkullChunk($level->getFolderName(), $x >> 4, $z >> 4);
                }
                $skullChunk->addSkull($position, $uuid);
            }
        }
        unlink("worlds/" . $level->getFolderName() . "/skulls.dat");
    }
}