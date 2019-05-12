<?php
declare(strict_types=1);

namespace OITQ\Tasks;

use OITQ\Loader;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\PluginTask;

class RespawnTask extends PluginTask{

	public $owner, $player;

	/**
	 * @param Loader $owner
	 * @param Player $player
	 */
	public function __construct(Loader $owner, Player $player){
		parent::__construct($owner, $player);
		$this->player = $player;
		$this->owner = $owner;
	}

	/**
	 * @param $currentTick
	 */
	public function onRun($currentTick){
		$this->player->teleport(new Vector3(10000, 100, 10000));
		$this->owner->getServer()->getScheduler()->scheduleDelayedTask(new TeleportTask($this->owner, $this->player), 2);
		$this->owner->sendKit($this->player);
	}
}