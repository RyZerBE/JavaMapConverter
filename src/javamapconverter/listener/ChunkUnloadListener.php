<?php

namespace javamapconverter\listener;

use javamapconverter\skull\SkullChunkManager;
use pocketmine\event\level\ChunkUnloadEvent;
use pocketmine\event\Listener;
use function is_null;

class ChunkUnloadListener implements Listener {
    public function onChunkUnload(ChunkUnloadEvent $event): void {
        $chunk = $event->getChunk();
        $skullChunk = SkullChunkManager::getInstance()->getChunk($event->getLevel(), $chunk->getX(), $chunk->getZ());
        if(is_null($skullChunk)) return;
        $skullChunk->onUnload();
        SkullChunkManager::getInstance()->removeChunk($skullChunk);
    }
}