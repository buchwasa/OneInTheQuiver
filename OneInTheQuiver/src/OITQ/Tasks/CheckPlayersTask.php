<?php
declare(strict_types=1);

namespace OITQ\Tasks;

use OITQ\Loader;
use pocketmine\math\Vector3;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;

class CheckPlayersTask extends PluginTask{

	public $owner;
	public $starttimer = 31;

	/**
	 * @param Loader $owner
	 */
	public function __construct(Loader $owner){
		parent::__construct($owner);
		$this->owner = $owner;
	}

	/**
	 * @param $currentTick
	 */
	public function onRun($currentTick){
		if(count($this->owner->queue) >= 2){
			$this->starttimer--;
			foreach(Server::getInstance()->getLevelByName("hub")->getPlayers() as $p){
				$p->sendPopup("Starting in " . $this->starttimer);
				if($this->starttimer === 0){
					if(isset($this->owner->queue[$p->getName()])){
						$p->teleport(new Vector3(10000, 100, 10000));
						$this->owner->getServer()->getScheduler()->scheduleDelayedTask(new TeleportTask($this->owner, $p), 2);
						if($this->owner->hasGameStarted === false){
							$this->owner->hasGameStarted = true;
							Server::getInstance()->getScheduler()->scheduleRepeatingTask(new StartGameTask($this->owner), 25)->getTaskId();
							Server::getInstance()->getScheduler()->cancelTask($this->getTaskId());
						}
					}
				}
			}
		}else{
			$this->starttimer = 31;
			Server::getInstance()->broadcastTip("Waiting for players...");
		}
	}
}