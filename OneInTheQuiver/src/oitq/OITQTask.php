<?php
declare(strict_types=1);

namespace oitq;

use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use function count;

class OITQTask extends Task{

	/** @var Loader */
	private $plugin;

	/** @var int */
	public const WAITING = 0;
	/** @var int */
	public const COUNTDOWN = 1;
	/** @var int */
	public const GAME = 2;
	/** @var int */
	public const ENDING = 3;

	/** @var int */
	private $countdown = 31;
	/** @var int */
	private $game = 901;
	/** @var int */
	private $shutdownTimer = 21;

	public function __construct(Loader $plugin){
		$this->plugin = $plugin;
	}

	public function onRun(int $currentTick){
		switch($this->plugin->gameStatus){
			case self::WAITING:
				if(count($this->plugin->queue) >= 2){
					$this->plugin->gameStatus = self::COUNTDOWN;
				}else{
					$this->plugin->getServer()->broadcastTip("Waiting for players...");
				}
				break;
			case self::COUNTDOWN:
				$this->handleCountdown();
				break;
			case self::GAME:
				$this->handleGame();
				break;
			case self::ENDING:
				$this->handleEnding();
				break;
		}
	}

	private function handleCountdown(){
		$this->countdown--;
		if(count($this->plugin->queue) < 2){
			$this->countdown = 31;
			$this->plugin->gameStatus = self::WAITING;
		}

		foreach($this->plugin->queue as $p){
			$p->sendPopup("Starting in " . $this->countdown);
			if($this->countdown === 0){
				$p->teleport($this->plugin->map->getSafeSpawn());
				$this->plugin->sendKit($p);
			}
		}
	}

	private function handleGame(){
		$this->game--;
		if($this->game === 0){
			$this->plugin->getServer()->broadcastMessage("Game ended, no one won!");
			$this->plugin->gameStatus = self::ENDING;
		}

		foreach($this->plugin->queue as $p){
			$p->sendTip("Kills: " . $this->plugin->getKills($p));

			if($this->plugin->kills[$p->getName()] >= 20){
				$this->plugin->getServer()->broadcastMessage(TextFormat::BOLD . TextFormat::AQUA . $p->getDisplayName() . " won the game!");
				$this->plugin->gameStatus = self::ENDING;
			}
		}
	}

	private function handleEnding(){
		$this->shutdownTimer--;
		switch($this->shutdownTimer){
			case 20:
				$this->plugin->getServer()->broadcastMessage("Server will shutdown in 20 seconds.");
				break;
			case 5:
			case 4:
			case 3:
			case 2:
			case 1:
				$this->plugin->getServer()->broadcastMessage("Shutting down in $this->shutdownTimer");
				break;
			case 0:
				$this->plugin->getServer()->shutdown();
				break;
		}
	}
}