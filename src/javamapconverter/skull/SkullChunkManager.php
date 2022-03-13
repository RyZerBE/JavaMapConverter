<?php

namespace javamapconverter\skull;

use javamapconverter\utils\InstantiableTrait;
use pocketmine\level\Level;

class SkullChunkManager {
    use InstantiableTrait;
    private array $chunks = [];

    public function addChunk(SkullChunk $chunk): void {
        $this->chunks[$chunk->getLevel()][Level::chunkHash($chunk->getX(), $chunk->getZ())] = $chunk;
    }
    public function removeChunk(SkullChunk $chunk): void {
        unset($this->chunks[$chunk->getLevel()][Level::chunkHash($chunk->getX(), $chunk->getZ())]);
    }
    public function getChunk(Level $level, int $chunkX, int $chunkZ): ?SkullChunk {
        return $this->chunks[$level->getFolderName()][Level::chunkHash($chunkX, $chunkZ)] ?? null;
    }
    public function getChunkByXZ(Level $level, int $x, int $z): ?SkullChunk {
        return $this->getChunk($level, $x >> 4, $z >> 4);
    }

    /**
     * @return SkullChunk[]
     */
    public function getChunks(Level $level): array{
        return $this->chunks[$level->getFolderName()] ?? [];
    }
}