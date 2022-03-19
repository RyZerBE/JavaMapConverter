<?php

namespace javamapconverter\scheduler;

use javamapconverter\converter\Converter;
use javamapconverter\Loader;
use javamapconverter\skull\SkullChunk;
use pocketmine\level\format\io\exception\CorruptedChunkException;
use pocketmine\level\format\io\LevelProviderManager;
use pocketmine\level\format\io\region\Anvil;
use pocketmine\level\format\io\region\RegionLoader;
use pocketmine\math\Vector3;
use pocketmine\nbt\BigEndianNBTStream;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\tile\Skull as SkullTile;
use pocketmine\tile\Tile;
use pocketmine\utils\MainLogger;
use function base64_decode;
use function basename;
use function ceil;
use function count;
use function explode;
use function floor;
use function glob;
use function implode;
use function is_dir;
use function is_null;
use function microtime;
use function round;
use function str_repeat;
use function str_replace;
use function strlen;
use function zlib_decode;

class WorldConvertAsyncTask extends AsyncTask {

    private string $error = "";
    private array $skulls = [];

    public function __construct(
        private string $level,
        private bool $showProgress = false,
        private int $chunksUntilSleep = 10,
        private bool $ignoreHeads = false
    ){}

    public function onRun(): void {
        $level = $this->level;
        $path = "worlds/" . $level;
        if(!is_dir($path)) {
            $this->error = "Could not find world in " . $path;
            return;
        }

        LevelProviderManager::init();
        $providerClass = LevelProviderManager::getProvider($path);
        if($providerClass === null) {
            $this->error = "Unknown provider";
            return;
        }

        try{
            $provider = new $providerClass($path . "/");
        } catch(\Error $error) {
            $this->error = $error->getMessage();
            return;
        }

        if(!$provider instanceof Anvil){
            if($provider === null){
                $this->error = "Unknown world provider";
                return;
            }
            $this->error = "Chunk format {$provider->getName()} is not supported.";
            return;
        }

        $fixer = new Converter();

        $microtime = microtime(true);

        $chunks = $this->getChunks($path);
        $chunksFinished = 0;
        $nextSleep = $this->chunksUntilSleep;
        foreach ($chunks as $index => [$chunkX, $chunkZ]) {
            $nextSleep--;
            if($nextSleep <= 0) {
                usleep(1000 * 50);
                $nextSleep = $this->chunksUntilSleep;
            }
            $chunksFinished++;
            $chunk = $provider->loadChunk($chunkX, $chunkZ);
            if($chunk === null) continue;
            for($x = 0; $x <= 15; $x++) {
                for($z = 0; $z <= 15; $z++) {
                    for($y = 0; $y <= $provider->getWorldHeight(); $y++) {
                        $id = $chunk->getBlockId($x, $y, $z);
                        $data = $chunk->getBlockData($x, $y, $z);
                        if($id === 0) continue;

                        $fixer->fixBlock($id, $data);
                        $chunk->setBlockId($x, $y, $z, $id);
                        $chunk->setBlockData($x, $y, $z, $data);
                    }
                }
            }
            $provider->saveChunk($chunk);
            $provider->doGarbageCollection();

            if($this->showProgress) {
                $percentage = round((($index + 1) * 100) / count($chunks), 2);

                $sPercentage = (string)$percentage;
                if($percentage < 10) $sPercentage = "0" . $sPercentage;
                $extraBox = "";
                switch(strlen($sPercentage)) {
                    case 4: {
                        $sPercentage .= "0";
                        break;
                    }
                    case 2: {
                        $sPercentage .= ".00";
                        $extraBox = "§7█";
                        break;
                    }
                    default: {
                        if((int)$percentage === 100){
                            $sPercentage = "100.0";
                            $extraBox = "§a█";
                        }
                    }
                }
                if(floor($percentage) % 5 === 0) {
                    MainLogger::getLogger()->info("§r§a" . $sPercentage . "% §7| §a " . $chunksFinished . "§7/§a" . count($chunks) . " §7| §a" . str_repeat("§a█", (int)ceil($percentage)) . "§7" . str_repeat("§7█", (int)ceil(100 - $percentage)) . $extraBox);
                }
            }
        }
        MainLogger::getLogger()->info(Loader::PREFIX . "Converted world " . $this->level . ". Took " . round(microtime(true) - $microtime, 2) . " seconds");
    }

    /**
     * @param Server $server
     */
    public function onCompletion(Server $server){
        if(!empty($this->error)) MainLogger::getLogger()->info($this->error);
        Server::getInstance()->loadLevel($this->level);
        $level = $server->getLevelByName($this->level);
        if(is_null($level)) return;

        if(!$this->ignoreHeads) {
            foreach($this->skulls as $vector3 => $skullRot) {
                $vector3 = explode(":", $vector3);
                $vector3 = new Vector3($vector3[0], $vector3[1], $vector3[2]);
                $level->loadChunk($vector3->x >> 4, $vector3->z >> 4);

                $nbt = SkullTile::createNBT($vector3);
                $nbt->setByte("Rot", $skullRot);
                Tile::createTile("Skull", $level, $nbt);
            }
        }
    }

    /**
     * @param string $path
     * @return array
     */
    public function getChunks(string $path): array {
        $path = $path . "/region/";
        $files = glob($path . "*.mca*");
        if($files === false) return [];
        $chunks = [];
        foreach ($files as $regionFilePath) {
            $split = explode(".", basename($regionFilePath));
            $regionX = (int)$split[1];
            $regionZ = (int)$split[2];

            $region = new RegionLoader($regionFilePath, $regionX, $regionZ);
            $region->open();

            for($x = 0; $x <= 31; $x++) {
                for($z = 0; $z <= 31; $z++) {
                    if($region->chunkExists($x, $z)) {
                        $chunks[] = [($regionX << 5) + $x, ($regionZ << 5) + $z];

                        if(!$this->ignoreHeads) {
                            $chunkData = $region->readChunk($x, $z);
                            if($chunkData !== null){
                                $skullChunk = new SkullChunk($this->level, ($regionX << 5) + $x, ($regionZ << 5) + $z);
                                foreach($this->getTiles($chunkData) as $tile) {
                                    if(!$tile->hasTag("Owner")) continue;
                                    $owner = $tile->getTag("Owner");
                                    if(!isset($owner->getValue()["Properties"])) continue;
                                    $skinId = explode("/", base64_decode($owner->getValue()["Properties"]->getValue()["textures"]->getValue()[0]["Value"]));
                                    $skinId = $skinId[4];
                                    foreach([" ", "}}}", '"', "},CAPE:{url:http:", ",metadata:{model:slim}}}}"] as $str) $skinId = str_replace($str, "", $skinId);

                                    $vector3 = new Vector3(
                                        $tile->getTag("x")->getValue(),
                                        $tile->getTag("y")->getValue(),
                                        $tile->getTag("z")->getValue()
                                    );
                                    $skullChunk->addSkull($vector3, $skinId);

                                    $this->skulls[implode(":", [$vector3->x, $vector3->y, $vector3->z])] = $tile->getTag("Rot")->getValue();
                                }
                                $skullChunk->onUnload();
                            }
                        }
                    }
                }
            }
        }
        return $chunks;
    }

    /**
     * @param string $data
     * @return array
     */
    private function getTiles(string $data) : array{
        $data = @zlib_decode($data);
        if($data === false) throw new CorruptedChunkException("Failed to decompress chunk data");
        $nbt = new BigEndianNBTStream();
        $chunk = $nbt->read($data);
        if(!$chunk instanceof CompoundTag || !$chunk->hasTag("Level")) throw new CorruptedChunkException("'Level' key is missing from chunk NBT");
        $chunk = $chunk->getCompoundTag("Level");
        return $chunk->hasTag("TileEntities", ListTag::class) ? self::getCompoundList("TileEntities", $chunk->getListTag("TileEntities")) : [];
    }

    /**
     * @param string $context
     * @param ListTag $list
     * @return CompoundTag[]
     */
    private static function getCompoundList(string $context, ListTag $list) : array{
        if($list->count() === 0) return [];
        if($list->getTagType() !== NBT::TAG_Compound) throw new CorruptedChunkException("Expected TAG_List<TAG_Compound> for '$context'");
        $result = [];
        foreach($list as $tag){
            if(!$tag instanceof CompoundTag) throw new CorruptedChunkException("Expected TAG_List<TAG_Compound> for '$context'");
            $result[] = $tag;
        }
        return $result;
    }
}