<?php
declare(strict_types=1);

namespace oitq;

use pocketmine\player\Player;
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
	private $countdown = 15;
	/** @var int */
	private $game = 900;
	/** @var int */
	private $shutdownTimer = 5;

	public function __construct(Loader $plugin){
		$this->plugin = $plugin;
	}

	public function onRun() : void{
		switch($this->plugin->gameStatus){
			case self::WAITING:
				if(count($this->plugin->gameData) >= 2){
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

	private function handleCountdown() : void{
		if(count($this->plugin->gameData) < 2){
			$this->countdown = 31;
			$this->plugin->gameStatus = self::WAITING;
		}

		/** @var Player $p */
		foreach($this->plugin->gameData as $gameData){
			$gameData["player"]->sendPopup("Starting in " . $this->countdown);
			if($this->countdown === 0){
				$gameData["player"]->teleport($this->plugin->map->getSafeSpawn());
				$this->plugin->sendKit($gameData["player"]);
				$this->plugin->gameStatus = self::GAME;
			}
		}

		$this->countdown--;
	}

	private function handleGame() : void{
		if($this->game === 0 || count($this->plugin->gameData) < 1){
			$this->plugin->getServer()->broadcastMessage("Game ended, no one won!");
			$this->plugin->gameStatus = self::ENDING;
		}elseif(count($this->plugin->gameData) === 1){
			/** @var Player $p */
			foreach($this->plugin->gameData as $gameData){
				$this->plugin->getServer()->broadcastMessage(TextFormat::BOLD . TextFormat::AQUA . $gameData["player"]->getDisplayName() . " won the game!");
				$this->plugin->gameStatus = self::ENDING;
			}
		}

		/** @var Player $p */
		foreach($this->plugin->gameData as $gameData){
			$eliminations = $this->plugin->gameData[$p->getName()]["eliminations"];
			$gameData->sendTip(TextFormat::RED . "Eliminations: " . TextFormat::GOLD . $eliminations);

			if($eliminations >= 20){
				$this->plugin->getServer()->broadcastMessage(TextFormat::BOLD . TextFormat::AQUA . $gameData["player"]->getDisplayName() . " won the game!");
				$this->plugin->gameStatus = self::ENDING;
			}
		}

		$this->game--;
	}

	private function handleEnding() : void{
		switch($this->shutdownTimer){
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

		$this->shutdownTimer--;
	}
}