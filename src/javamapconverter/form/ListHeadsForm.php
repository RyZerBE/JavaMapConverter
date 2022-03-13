<?php

namespace javamapconverter\form;

use javamapconverter\head\HeadsCategory;
use javamapconverter\head\HeadsManager;
use javamapconverter\utils\ItemUtils;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\item\Item;
use pocketmine\Player;
use function array_chunk;
use function count;
use function is_null;
use function str_replace;
use function strpos;

class ListHeadsForm {
    public const HEADS_PER_PAGE = 30;

    /**
     * @param Player $player
     * @param array $heads
     * @param int $page
     * @param HeadsCategory|null $category
     */
    public static function open(Player $player, array $heads, int $page = 1, ?HeadsCategory $category = null): void {
        $arrayPage = $page - 1;
        $headList = array_chunk($heads, self::HEADS_PER_PAGE);
        $headChunk = $headList[$arrayPage] ?? [];

        $form = new SimpleForm(function(Player $player, $data) use ($heads, $category): void {
            if(is_null($data)) return;
            switch($data) {
                case "search_head": {
                    SearchHeadForm::open($player, [$category]);
                    break;
                }
                default: {
                    if(strpos($data, "Page:") !== false) {
                        $page = (int)str_replace("Page:", "", $data);
                        self::open($player, $heads, $page);
                        return;
                    }
                    $head = HeadsManager::getInstance()->getHead($data);
                    if(!is_null($head)) {
                        $item = ItemUtils::addItemTag(Item::get(Item::SKULL, 3), $head->getSkinId(), "head")->setCustomName("§r§a" . $head->getName())->setLore([
                            "§r§8" . $head->getSkinId()
                        ]);
                        $player->getInventory()->addItem($item);
                        return;
                    }
                }
            }
        });
        $totalPages = count(array_chunk($heads, self::HEADS_PER_PAGE));
        $form->setTitle("§lHeads (" . $page . "/" . $totalPages . ")");
        foreach($headChunk as $head) {
            $form->addButton($head->getName(), 1, "https://mc-heads.net/head/" . $head->getSkinId(), $head->getUUID());
        }
        if(isset($headList[($arrayPage + 1)]) && !empty($headList[($arrayPage + 1)])) {
            $form->addButton("Next Page [" . ($page + 1) . "/" . $totalPages . "]", 0, "textures/ui/chevron_grey_right", "Page:" . ($page + 1));
        }
        if(isset($headList[($arrayPage - 1)]) && !empty($headList[($arrayPage - 1)])) {
            $form->addButton("Previous Page [" . ($page - 1) . "/" . $totalPages . "]", 0, "textures/ui/chevron_grey_left", "Page:" . ($page - 1));
        }
        if(empty($heads)){
            $form->setContent("§cNo Heads found");
        } else {
            $form->addButton("Search", 0, "textures/ui/magnifyingGlass", "search_head");
        }
        $form->sendToPlayer($player);
    }
}