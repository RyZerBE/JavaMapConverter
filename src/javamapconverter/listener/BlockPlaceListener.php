<?php

namespace javamapconverter\listener;

use javamapconverter\head\HeadsManager;
use javamapconverter\skull\SkullChunk;
use javamapconverter\skull\SkullChunkManager;
use javamapconverter\utils\ItemUtils;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;

class BlockPlaceListener implements Listener {

    /**
     * @param BlockPlaceEvent $event
     * @priority HIGHEST
     */
    public function onPlace(BlockPlaceEvent $event): void {
        if($event->isCancelled()) return;
        $player = $event->getPlayer();
        $item = $event->getItem();
        $block = $event->getBlock();

        if(!ItemUtils::hasItemTag($item, "head")) return;
        $skullChunk = SkullChunkManager::getInstance()->getChunkByXZ($block->getLevel(), $block->x, $block->z);
        if($skullChunk === null){
            $skullChunk = new SkullChunk($player->getLevel()->getFolderName(), $block->x >> 4, $block->z >> 4);
            SkullChunkManager::getInstance()->addChunk($skullChunk);
        }
        if($skullChunk->isSkull($block)) return;
        $skullChunk->addSkull($block, ItemUtils::getItemTag($item, "head"));
        HeadsManager::initHead($block, ItemUtils::getItemTag($item, "head"), $player->getLevel());
    }
}