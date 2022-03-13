<?php

namespace javamapconverter\listener;

use javamapconverter\entity\SkullEntity;
use javamapconverter\head\HeadsManager;
use javamapconverter\skull\SkullChunkManager;
use javamapconverter\utils\ItemUtils;
use pocketmine\block\Block;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use function is_null;

class BlockBreakListener implements Listener {

    /**
     * @param BlockBreakEvent $event
     * @priority HIGHEST
     */
    public function onBreak(BlockBreakEvent $event): void {
        if($event->isCancelled()) return;
        $block = $event->getBlock();

        $skullChunk = SkullChunkManager::getInstance()->getChunkByXZ($block->getLevel(), $block->getFloorX(), $block->getFloorZ());
        if(is_null($skullChunk) || $block->getId() !== Block::SKULL_BLOCK) return;

        $skull = $skullChunk->getSkull($block);
        if(!is_null($skull)) {
            $head = HeadsManager::getInstance()->getHeadBySkinId($skull);
            $item = ItemUtils::addItemTag(Item::get(Item::SKULL, 3), $skull, "head")->setCustomName("§r§a" . (is_null($head) ? "Custom Head" : $head->getName()))->setLore([
                "§r§8" . $skullChunk->getSkull($block)
            ]);
            $event->setDrops([$item]);
        }
        $skullChunk->removeSkull($block);
        foreach($block->getLevel()->getCollidingEntities($block->getBoundingBox()) as $entity) {
            if(!$entity instanceof SkullEntity) continue;
            $entity->flagForDespawn();
        }
    }
}