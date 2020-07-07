<?php
declare(strict_types=1);

namespace oitq\game;

use oitq\Loader;
use pocketmine\scheduler\Task;
use function count;

class GameTask extends Task{
	/** @var Loader */
	private $plugin;
	/** @var int */
	private $gameStatus = GameStatus::WAITING;
	/** @var int */
	private $countdown;
	/** @var int */
	private $game;
	/** @var int */
	private $reset;

	public function __construct(Loader $plugin){
		$this->plugin = $plugin;
		$this->resetTimers($plugin);
	}

	public function getGameStatus() : int{
		return $this->gameStatus;
	}

	public function setGameStatus(int $status) : void{
		$this->gameStatus = $status;
	}

	public function onRun() : void{
		foreach($this->plugin->getGameSessions() as $gameSession){
			if($this->getGameStatus() === GameStatus::COUNTDOWN){
				$gameSession->getPlayer()->sendTip($this->plugin->getMessage("countdown-tip", ["{TIME}" => $this->countdown]));
				if($this->countdown === 0){
					$gameSession->getPlayer()->teleport($this->plugin->getMap()->getSafeSpawn()); //Used for "spawnpoint" TODO: Make this into actual configurable spawnpoints.
					$this->plugin->sendKit($gameSession->getPlayer());
					$this->gameStatus = GameStatus::GAME;
				}
			}elseif($this->getGameStatus() === GameStatus::GAME){
				$gameSession->getPlayer()->sendTip($this->plugin->getMessage("eliminations-tip", ["{ELIMINATIONS}" => $gameSession->getEliminations()]));

				if($gameSession->getEliminations() >= $this->plugin->getMaxEliminations() || count($this->plugin->getGameSessions()) === 1){
					$this->plugin->getServer()->broadcastMessage($this->plugin->getMessage("player-won", ["{DISPLAY_NAME}" => $gameSession->getPlayer()->getDisplayName()])); //TODO: Broadcast to world.
					$this->gameStatus = GameStatus::RESET;
				}
			}
		}

		switch($this->getGameStatus()){
			case GameStatus::WAITING:
				if(count($this->plugin->getGameSessions()) >= 2){
					$this->gameStatus = GameStatus::COUNTDOWN;
				}else{
					$this->plugin->getServer()->broadcastTip($this->plugin->getMessage("waiting-tip")); //TODO: Broadcast to world.
				}
				break;
			case GameStatus::COUNTDOWN:
				if(count($this->plugin->getGameSessions()) < 2){
					$this->resetTimers($this->plugin);
					$this->gameStatus = GameStatus::WAITING;
				}

				$this->countdown--;
				break;
			case GameStatus::GAME:
				if($this->game === 0 || count($this->plugin->getGameSessions()) < 1){
					$this->plugin->getServer()->broadcastMessage($this->plugin->getMessage("no-one-won")); //TODO: Broadcast to world.
					$this->gameStatus = GameStatus::RESET;
				}

				$this->game--;
				break;
			case GameStatus::RESET:
				if($this->reset === 0){
					$this->plugin->getServer()->shutdown();//TODO: Unload and reload map.
					$this->resetTimers($this->plugin);
				}

				$this->reset--;
				break;
		}
	}

	private function resetTimers(Loader $plugin) : void{
		$this->countdown = $plugin->getCountdownTimer();
		$this->game = $plugin->getGameTimer();
		$this->reset = $plugin->getResetTimer();
	}
}