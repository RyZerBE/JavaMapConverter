<?php

namespace javamapconverter\skin;

use javamapconverter\utils\AsyncExecuter;
use javamapconverter\utils\InstantiableTrait;
use javamapconverter\utils\SkinUtils;
use pocketmine\Server;
use function curl_close;
use function curl_exec;
use function curl_init;
use function curl_setopt;
use function file_put_contents;
use function is_file;
use function is_null;
use function mkdir;

class SkinManager {
    use InstantiableTrait;

    /** @var array  */
    private array $cache = [];

    /**
     * @param string $skinId
     * @return bool
     */
    public function isCached(string $skinId): bool {
        return !is_null($this->getCache($skinId));
    }

    /**
     * @param string $skinId
     * @return string|null
     */
    public function getCache(string $skinId): ?string {
        return $this->cache[$skinId] ?? null;
    }

    /**
     * @param string $skinId
     * @param string $skinData
     */
    public function cacheSkinData(string $skinId, string $skinData): void {
        $this->cache[$skinId] = $skinData;
    }

    public function requestSkinData(string $skinId, string $level, callable $callable): void {
        if($this->isCached($skinId)) {
            ($callable)(Server::getInstance(), $this->getCache($skinId));
            return;
        }
        AsyncExecuter::submitAsyncTask(function() use ($skinId, $level): string {
            $file = "worlds/" . $level . "/heads/skins/" . $skinId. ".png";
            if(!is_file($file)) {
                $curlSession = curl_init();
                curl_setopt($curlSession, CURLOPT_URL, "https://textures.minecraft.net/texture/" . $skinId);
                curl_setopt($curlSession, CURLOPT_BINARYTRANSFER, true);
                curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, true);
                @mkdir("worlds/" . $level . "/heads/skins/");
                file_put_contents($file, curl_exec($curlSession));
                curl_close($curlSession);
            }
            return SkinUtils::readImage($file);
        }, function(Server $server, string $result) use ($callable, $skinId, $level): void {
            if(empty($result)){
                //var_dump("Failed:" . $skinId);
                //$this->requestSkinData($skinId, $level, $callable);
                return;
            }
            SkinManager::getInstance()->cacheSkinData($skinId, $result);
            ($callable)($server, $result);
        });
    }
}