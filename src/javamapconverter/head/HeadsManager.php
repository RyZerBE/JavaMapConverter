<?php

namespace javamapconverter\head;

use javamapconverter\entity\SkullEntity;
use javamapconverter\skin\SkinManager;
use javamapconverter\utils\InstantiableTrait;
use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Server;
use function explode;
use function implode;
use function is_null;
use function strtolower;

class HeadsManager {
    use InstantiableTrait;

    /** @var array  */
    private $categories = [];

    /**
     * @return HeadsCategory[]
     */
    public function getCategories(): array{
        return $this->categories;
    }

    /**
     * @param string $name
     * @return HeadsCategory|null
     */
    public function getCategory(string $name): ?HeadsCategory {
        return $this->categories[$name] ?? null;
    }

    /**
     * @param HeadsCategory $category
     */
    public function addCategory(HeadsCategory $category): void {
        $this->categories[$category->getName()] = $category;
    }

    /**
     * @param string $uuid
     * @return Head|null
     */
    public function getHead(string $uuid): ?Head {
        foreach($this->getCategories() as $category) {
            $head = $category->getHead($uuid);
            if(!is_null($head)) return $head;
        }
        return null;
    }

    /**
     * @param string $skinId
     * @return Head|null
     */
    public function getHeadBySkinId(string $skinId): ?Head {
        foreach($this->getCategories() as $category) {
            foreach($category->getHeads() as $head) {
                if($head->getSkinId() === $skinId) return $head;
            }
        }
        return null;
    }

    /**
     * @param string $query
     * @param array|null $categories
     * @return Head[]
     */
    public function searchHeads(string $query, ?array $categories = null): array {
        $heads = [];
        $query = strtolower($query);
        if(is_null($categories)) $categories = $this->getCategories();
        foreach($categories as $category) {
            if(!$category instanceof HeadsCategory) continue;
            foreach($category->getHeads() as $head) {
                if(!str_contains(strtolower($head->getName()), $query)) continue;
                $heads[] = $head;
            }
        }
        return $heads;
    }

    public static function initHead(Vector3|string $vector3, string $skinId, Level|string $level): void {
        if($vector3 instanceof Vector3) $vector3 = implode(":", [$vector3->x, $vector3->y, $vector3->z]);
        if($level instanceof Level) $level = $level->getFolderName();
        SkinManager::getInstance()->requestSkinData($skinId, $level, function(Server $server, string $result) use ($vector3, $skinId, $level): void {
            $level = $server->getLevelByName($level);
            if($level === null) return;
            $vector3 = explode(":", $vector3);
            $vector3 = new Vector3((float)$vector3[0], (float)$vector3[1], (float)$vector3[2]);
            $block = $level->getBlock($vector3);
            if($block->getId() !== BlockIds::SKULL_BLOCK) return;
            $nbt = Entity::createBaseNBT($vector3->floor());
            $skullEntity = new SkullEntity($level, $nbt, new Skin($skinId, $result));
            $skullEntity->spawnToAll();
        });
    }
}