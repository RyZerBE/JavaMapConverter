<?php

namespace javamapconverter\converter;

use pocketmine\block\BlockIds;
use function in_array;
use function is_null;

class Converter {

    /** @var array[]  */
    private $block_fix_list = [
        125 => [BlockIds::DOUBLE_WOODEN_SLAB, null],
        126 => [BlockIds::WOODEN_SLAB, null],
        158 => [BlockIds::WOODEN_SLAB, 0],
        166 => [BlockIds::INVISIBLE_BEDROCK, 0],
        188 => [BlockIds::FENCE, 1],
        189 => [BlockIds::FENCE, 2],
        190 => [BlockIds::FENCE, 3],
        191 => [BlockIds::FENCE, 5],
        192 => [BlockIds::FENCE, 4],
        198 => [BlockIds::END_ROD, 0],
        199 => [BlockIds::CHORUS_PLANT, 0],
        202 => [BlockIds::PURPUR_BLOCK, 0],
        204 => [BlockIds::PURPUR_BLOCK, 0],
        208 => [BlockIds::GRASS_PATH, 0],
        251 => [BlockIds::CONCRETE, null],
        252 => [BlockIds::CONCRETE_POWDER, null],
        95 => [BlockIds::STAINED_GLASS, null],
        207 => [BlockIds::BEETROOT_BLOCK, "?"]
    ];

    /**
     * @param int $id
     * @param int $damage
     */
    public function fixBlock(int &$id, int &$damage): void {
        if(isset($this->block_fix_list[$id])) {
            if(!is_null($this->block_fix_list[$id][1])) {
                if($this->block_fix_list[$id][1] !== "?") {
                    $damage = $this->block_fix_list[$id][1];
                }
            }
            $id = $this->block_fix_list[$id][0];
        }

        if(in_array($id, [BlockIds::TRAPDOOR, BlockIds::IRON_TRAPDOOR])) $damage = $this->fixTrapdoorMeta($damage);
        if(in_array($id, [BlockIds::WOODEN_BUTTON, BlockIds::STONE_BUTTON])) $damage = $this->fixButtonMeta($damage);
    }

    /**
     * @param int $meta
     * @return int
     */
    private function fixButtonMeta(int $meta): int {
        return ((6 - $meta) % 6) & 0xf;
    }

    /**
     * @param int $meta
     * @return int
     */
    private function fixTrapdoorMeta(int $meta): int {
        $key = $meta >> 2;
        switch($key) {
            case 0: return 3 - $meta;
            case 3: return 27 - $meta;
            default: return 15 - $meta;
        }
    }
}