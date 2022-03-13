<?php

namespace javamapconverter\listener;

use javamapconverter\head\HeadsManager;
use javamapconverter\skull\SkullChunkManager;
use javamapconverter\utils\ItemUtils;
use pocketmine\block\Block;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBlockPickEvent;
use pocketmine\item\Item;
use function is_null;

class BlockPickListener implements Listener {

    /**
     * @param PlayerBlockPickEvent $event
     * @priority HIGHEST
     */
    public function onPick(PlayerBlockPickEvent $event): void {
        if($event->isCancelled()) return;
        $block = $event->getBlock();
        $skullChunk = SkullChunkManager::getInstance()->getChunkByXZ($block->getLevel(), $block->x, $block->z);

        if(is_null($skullChunk) || !$skullChunk->isSkull($block) || $block->getId() !== Block::SKULL_BLOCK) return;
        $skull = $skullChunk->getSkull($block);
        $head = HeadsManager::getInstance()->getHeadBySkinId($skull);
        $item = ItemUtils::addItemTag(Item::get(Item::SKULL, 3), $skull, "head")->setCustomName("§r§a" . (is_null($head) ? "Custom Head" : $head->getName()))->setLore([
            "§r§8" . $skullChunk->getSkull($block)
        ]);
        $event->setResultItem($item);
    }
}