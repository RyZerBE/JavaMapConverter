<?php

namespace javamapconverter\scheduler;

use javamapconverter\head\Head;
use javamapconverter\head\HeadsCategory;
use javamapconverter\head\HeadsManager;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use function base64_decode;
use function curl_close;
use function curl_exec;
use function curl_init;
use function curl_setopt;
use function is_null;
use function json_decode;
use function str_replace;

class DownloadHeadsAsyncTask extends AsyncTask {

    public function onRun(): void {
        $categories = ["alphabet", "animals", "blocks", "decoration", "food-drinks", "humans", "humanoid", "miscellaneous", "monsters", "plants"];
        $heads = [];
        foreach($categories as $category) {
            $curlSession = curl_init();
            curl_setopt($curlSession, CURLOPT_URL, "https://minecraft-heads.com/scripts/api.php?cat=" . $category);
            curl_setopt($curlSession, CURLOPT_BINARYTRANSFER, true);
            curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
            $json = json_decode(curl_exec($curlSession), true);
            curl_close($curlSession);
            if($json === false) continue;
            foreach($json as $value) {
                $array = json_decode(base64_decode($value["value"]), true);
                $skinData = str_replace("http://textures.minecraft.net/texture/", "", $array["textures"]["SKIN"]["url"]);
                $skinId = $value["uuid"];
                $heads[$skinId] = [
                    "Category" => $category,
                    "Name" => $value["name"],
                    "SkinData" => $skinData
                ];
            }
        }
        $this->setResult($heads);
    }

    /**
     * @param Server $server
     */
    public function onCompletion(Server $server): void {
        foreach($this->getResult() as $uuid => $headData) {
            $head = new Head($uuid, $headData["SkinData"], $headData["Name"]);
            $category = HeadsManager::getInstance()->getCategory($headData["Category"]);
            if(is_null($category)) HeadsManager::getInstance()->addCategory(($category = new HeadsCategory($headData["Category"])));
            $category->addHead($head);
        }
    }
}