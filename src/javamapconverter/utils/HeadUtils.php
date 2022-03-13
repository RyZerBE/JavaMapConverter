<?php

namespace javamapconverter\utils;

use javamapconverter\entity\SkullEntity;
use javamapconverter\skin\SkinManager;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\math\Vector3;
use pocketmine\Server;
use function explode;
use function implode;
use function is_null;

class HeadUtils {

    /**
     * @param Vector3|string $vector3
     * @param string $skinId
     * @param string $level
     */
    public static function initHead($vector3, string $skinId, string $level): void {
        if($vector3 instanceof Vector3) $vector3 = implode(":", [$vector3->x, $vector3->y, $vector3->z]);
        SkinManager::getInstance()->requestSkinData($skinId, $level, function(Server $server, string $result) use ($vector3, $skinId, $level): void {
            $level = $server->getLevelByName($level);
            if(is_null($level)) return;
            $vector3 = explode(":", $vector3);
            $vector3 = new Vector3((float)$vector3[0], (float)$vector3[1], (float)$vector3[2]);
            $nbt = Entity::createBaseNBT($vector3);
            $skullEntity = new SkullEntity($level, $nbt, new Skin($skinId, $result));
            $skullEntity->spawnToAll();
        });
    }
}