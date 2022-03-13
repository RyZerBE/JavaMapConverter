<?php

namespace javamapconverter\command;

use javamapconverter\form\ListCategoriesForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class HeadsDBCommand extends Command {
    public function __construct(){
        parent::__construct("headsdb", "Heads Database");
        $this->setPermission("command.headsdb.use");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if(!$sender instanceof Player || !$this->testPermission($sender)) return;
        ListCategoriesForm::open($sender);
    }
}