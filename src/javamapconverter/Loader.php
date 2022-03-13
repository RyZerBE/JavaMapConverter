<?php

namespace javamapconverter;

use javamapconverter\command\ConvertCommand;
use javamapconverter\command\HeadsDBCommand;
use javamapconverter\listener\BlockBreakListener;
use javamapconverter\listener\BlockPickListener;
use javamapconverter\listener\BlockPlaceListener;
use javamapconverter\listener\ChunkLoadListener;
use javamapconverter\listener\ChunkUnloadListener;
use javamapconverter\listener\LevelLoadListener;
use javamapconverter\scheduler\DownloadHeadsAsyncTask;
use javamapconverter\scheduler\SkullSendTask;
use javamapconverter\skin\SkinManager;
use javamapconverter\skull\SkullChunk;
use javamapconverter\skull\SkullChunkManager;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class Loader extends PluginBase {
    public const PREFIX = "§r§6Converter§8 | §7";

    private static ?Loader $instance = null;

    public function onEnable(): void {
        self::$instance = $this;

        Server::getInstance()->getCommandMap()->registerAll("javamapconverter", [
            new ConvertCommand(),
            new HeadsDBCommand()
        ]);
        Server::getInstance()->getAsyncPool()->submitTask(new DownloadHeadsAsyncTask());
        $this->getScheduler()->scheduleRepeatingTask(new SkullSendTask(), 1);

        SkullChunkManager::getInstance();
        SkinManager::getInstance();

        $this->initListener();

        foreach(Server::getInstance()->getLevels() as $level) {
            foreach($level->getChunks() as $chunk) {
                if(SkullChunkManager::getInstance()->getChunk($level, $chunk->getX(), $chunk->getZ()) !== null) continue;
                $skullChunk = new SkullChunk($level->getFolderName(), $chunk->getX(), $chunk->getZ());
                SkullChunkManager::getInstance()->addChunk($skullChunk);
                $skullChunk->onLoad();
            }
        }
    }

    public static function getInstance(): ?Loader{
        return self::$instance;
    }

    private function initListener(): void {
        $listeners = [
            new ChunkLoadListener(),
            new ChunkUnloadListener(),
            new BlockBreakListener(),
            new BlockPickListener(),
            new BlockPlaceListener(),
            new LevelLoadListener()
        ];
        foreach($listeners as $listener) {
            Server::getInstance()->getPluginManager()->registerEvents($listener, $this);
        }
    }
}