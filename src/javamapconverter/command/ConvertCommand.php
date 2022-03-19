<?php

namespace javamapconverter\command;

use javamapconverter\Loader;
use javamapconverter\scheduler\WorldConvertAsyncTask;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Server;
use function boolval;
use function intval;
use function is_null;

class ConvertCommand extends Command {

    /**
     * ConvertCommand constructor.
     */
    public function __construct(){
        parent::__construct("convert", "Convert Command");
        $this->setPermission("command.convert.use");
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(!$this->testPermission($sender)) return;
        if(!$sender instanceof ConsoleCommandSender) {
            $sender->sendMessage(Loader::PREFIX . "You can only convert worlds from console.");
            return;
        }
        if(!isset($args[0])) {
            $sender->sendMessage(Loader::PREFIX . "/convert [Level] [ShowProgress = false] [ChunksUntilSleep = 10] [IgnoreHeads = false]");
            return;
        }
        $level = $args[0];
        if(!is_null(Server::getInstance()->getLevelByName($level))) {
            $level = Server::getInstance()->getLevelByName($level);
            if(Server::getInstance()->getDefaultLevel()->getId() === $level->getId()) {
                $sender->sendMessage(Loader::PREFIX . "You can not convert the default level.");
                return;
            }
            $level->unload(true);
            $level = $level->getFolderName();
        }
        $sender->sendMessage(Loader::PREFIX . "Converting world " . $level . "....");
        Server::getInstance()->getAsyncPool()->submitTask(new WorldConvertAsyncTask($level, $this->asBoolean($args[1] ?? false), intval($args[2] ?? 10), $this->asBoolean($args[3] ?? false)));
    }

    private function asBoolean(mixed $mixed): bool {
        return match ($mixed) {
            "false" => false,
            "true" => true,
            default => boolval($mixed)
        };
    }
}