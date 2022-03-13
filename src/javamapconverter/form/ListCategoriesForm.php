<?php

namespace javamapconverter\form;

use javamapconverter\head\HeadsManager;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use function array_rand;
use function is_null;
use function ucfirst;

class ListCategoriesForm {

    /**
     * @param Player $player
     */
    public static function open(Player $player): void {
        $form = new SimpleForm(function(Player $player, $data): void {
            if(is_null($data)) return;
            switch($data) {
                case "search_head": {
                    SearchHeadForm::open($player);
                    break;
                }
                default: {
                    $category = HeadsManager::getInstance()->getCategory($data);
                    if(!is_null($category)) {
                        ListHeadsForm::open($player, $category->getHeads(), 1, $category);
                        return;
                    }
                }
            }
        });
        $form->setTitle("§lHeads");
        foreach(HeadsManager::getInstance()->getCategories() as $category) {
            if(empty(($heads = $category->getHeads()))) continue;
            $randomHead = $heads[array_rand($heads)];
            $form->addButton(ucfirst($category->getName()), 1, "https://mc-heads.net/head/" . $randomHead->getSkinId(), $category->getName());
        }
        $form->addButton("Search", 0, "textures/ui/magnifyingGlass", "search_head");
        $form->sendToPlayer($player);
    }
}