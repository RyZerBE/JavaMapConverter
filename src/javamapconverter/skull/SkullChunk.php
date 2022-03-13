<?php

namespace javamapconverter\skull;

use javamapconverter\head\HeadsManager;
use pocketmine\math\Vector3;
use pocketmine\Server;
use function base64_decode;
use function base64_encode;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_file;
use function json_decode;
use function json_encode;
use function mkdir;
use function unlink;

class SkullChunk {

    /** @var int */
    protected $x;
    /** @var int */
    protected $z;
    /** @var string */
    protected $level;

    /** @var bool  */
    protected $hasChanged = false;

    /** @var array  */
    private $skulls = [];

    /**
     * SkullChunk constructor.
     * @param string $level
     * @param int $chunkX
     * @param int $chunkZ
     */
    public function __construct(string $level, int $chunkX, int $chunkZ){
        $this->level = $level;
        $this->x = $chunkX;
        $this->z = $chunkZ;
    }

    /**
     * @return string
     */
    public function getLevel(): string{
        return $this->level;
    }

    /**
     * @return int
     */
    public function getZ(): int{
        return $this->z;
    }

    /**
     * @return int
     */
    public function getX(): int{
        return $this->x;
    }

    /**
     * @return bool
     */
    public function hasChanged(): bool{
        return $this->hasChanged;
    }

    /**
     * @return array
     */
    public function getSkulls(): array{
        return $this->skulls;
    }

    /**
     * @param Vector3 $vector3
     * @param string $skinId
     */
    public function addSkull(Vector3 $vector3, string $skinId): void {
        $vector3 = $vector3->floor();
        $this->skulls[implode(":", [$vector3->x, $vector3->y, $vector3->z])] = $skinId;
        $this->hasChanged = true;
    }

    /**
     * @param Vector3 $vector3
     */
    public function removeSkull(Vector3 $vector3): void {
        $vector3 = $vector3->floor();
        if(!$this->isSkull($vector3)) return;
        unset($this->skulls[implode(":", [$vector3->x, $vector3->y, $vector3->z])]);
        $this->hasChanged = true;
    }

    /**
     * @param Vector3 $vector3
     * @return string|null
     */
    public function getSkull(Vector3 $vector3): ?string {
        return $this->skulls[implode(":", [$vector3->x, $vector3->y, $vector3->z])] ?? null;
    }

    /**
     * @param Vector3 $vector3
     * @return bool
     */
    public function isSkull(Vector3 $vector3): bool {
        $vector3 = $vector3->floor();
        return isset($this->skulls[implode(":", [$vector3->x, $vector3->y, $vector3->z])]);
    }

    public function onLoad(): void {
        $path = Server::getInstance()->getDataPath() . "/worlds/" . $this->getLevel() . "/heads/";
        @mkdir($path);

        $file = $path . implode(".", [$this->getX(), $this->getZ()]) . ".dat";
        if(!is_file($file)) return;
        $skulls = json_decode(base64_decode(file_get_contents($file)), true);
        if($skulls === false || $skulls === null){
            Server::getInstance()->getLogger()->error("Failed to load skull chunk '" . $file . "'");
            return;
        }
        foreach($skulls as $skull) {
            $vector3 = (new Vector3((float)$skull["X"], (float)$skull["Y"], (float)$skull["Z"]))->floor();
            $this->skulls[implode(":", [$vector3->x, $vector3->y, $vector3->z])] = $skull["SkinId"];
            HeadsManager::initHead($vector3, $skull["SkinId"], $this->getLevel());
        }
    }

    public function onUnload(): void {
        if(empty($this->getSkulls()) && !$this->hasChanged()) return;
        $skulls = [];
        foreach($this->getSkulls() as $vector3 => $skinId) {
            $vector3 = explode(":", $vector3);
            $skulls[] = [
                "X" => $vector3[0],
                "Y" => $vector3[1],
                "Z" => $vector3[2],
                "SkinId" => $skinId
            ];
        }

        $path = "worlds/" . $this->getLevel() . "/heads/";
        @mkdir($path);
        $file = $path . implode(".", [$this->getX(), $this->getZ()]) . ".dat";
        if(is_file($file) && empty($skulls)) {
            unlink($file);
            return;
        }
        file_put_contents($file, base64_encode(json_encode($skulls)));
    }
}