<?php
declare(strict_types=1);

namespace OITQ\Tasks;

use OITQ\Loader;
use pocketmine\Player;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;

class TeleportTask extends PluginTask{

	public $loader, $player;

	/**
	 * @param Loader $loader
	 * @param Player $player
	 */
	public function __construct(Loader $loader, Player $player){
		parent::__construct($loader);
		$this->loader = $loader;
		$this->player = $player;
	}

	/**
	 * @param $currentTick
	 */
	public function onRun($currentTick){
		$spawn = Server::getInstance()->getLevelByName("game")->getSafeSpawn();
		$this->player->teleport($spawn);
	}
}