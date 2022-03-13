<?php

namespace javamapconverter\listener;

use javamapconverter\skull\SkullChunk;
use javamapconverter\skull\SkullChunkManager;
use pocketmine\event\level\ChunkLoadEvent;
use pocketmine\event\Listener;

class ChunkLoadListener implements Listener {
    public function onChunkLoad(ChunkLoadEvent $event): void {
        $chunk = $event->getChunk();
        $level = $event->getLevel();
        $skullChunk = new SkullChunk($level->getFolderName(), $chunk->getX(), $chunk->getZ());
        SkullChunkManager::getInstance()->addChunk($skullChunk);
        $skullChunk->onLoad();
    }
}