<?php
declare(strict_types=1);

namespace oitq;

use oitq\game\GameSession;
use oitq\game\GameTask;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;

class Loader extends PluginBase{
	/** @var GameSession[] */
	private $gameSessions = [];
	/** @var World */
	private $map;
	/** @var GameTask */
	private $gameTask;

	public function onEnable() : void{
		$this->saveDefaultConfig();
		$this->getServer()->getWorldManager()->loadWorld($this->getConfig()->get("map"));
		$this->map = $this->getServer()->getWorldManager()->getWorldByName($this->getConfig()->get("map"));
		$this->getScheduler()->scheduleRepeatingTask($this->gameTask = new GameTask($this), 20);
		new EventListener($this);
	}

	public function getGameTask() : GameTask{
		return $this->gameTask;
	}

	public function createGameSession(Player $player) : void{
		$this->gameSessions[$player->getUniqueId()->toString()] = new GameSession($player);
	}

	public function removeGameSession(Player $player) : void{
		$session = $this->gameSessions[$player->getUniqueId()->toString()];
		if(isset($session)){
			unset($session);
		}
	}

	public function getGameSession(Player $player) : ?GameSession{
		$session = $this->gameSessions[$player->getUniqueId()->toString()];
		return isset($session) ? $session : null;
	}

	/**
	 * @return GameSession[]
	 */
	public function getGameSessions() : array{
		return $this->gameSessions;
	}

	public function getMap() : World{
		return $this->map;
	}

	public function getMaxEliminations() : int{
		return (int)$this->getConfig()->get("max-eliminations");
	}

	public function getCountdownTimer() : int{
		return (int)$this->getConfig()->get("timers")["countdown"];
	}

	public function getGameTimer() : int{
		return (int)$this->getConfig()->get("timers")["game"];
	}

	public function getResetTimer() : int{
		return (int)$this->getConfig()->get("timers")["reset"];
	}

	public function sendKit(Player $player) : void{
		$inventory = $player->getInventory();
		$inventory->clearAll();
		$inventory->addItem(VanillaItems::ARROW());
		$inventory->addItem(VanillaItems::BOW());
		$inventory->addItem(VanillaItems::IRON_AXE());
	}
}