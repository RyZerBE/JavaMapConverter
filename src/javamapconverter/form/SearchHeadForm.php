<?php

namespace javamapconverter\form;

use javamapconverter\head\HeadsManager;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use function is_null;

class SearchHeadForm {

    /**
     * @param Player $player
     * @param array|null $categories
     */
    public static function open(Player $player, ?array $categories = null): void {
        $form = new CustomForm(function(Player $player, $data) use ($categories): void {
            if(is_null($data)) return;
            $query = $data["query"];
            if(empty($query)) return;

            ListHeadsForm::open(
                $player,
                HeadsManager::getInstance()->searchHeads($query, $categories)
            );
        });
        $form->setTitle("§lHeads");
        $form->addInput("§aSearch Head", "", "", "query");
        $form->sendToPlayer($player);
    }
}