<?php
declare(strict_types=1);

namespace OITQ\Tasks;

use Core\CoreLoader;
use OITQ\Loader;
use pocketmine\item\Item;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class StartGameTask extends PluginTask{

	public $owner;
	public $s2s = 901;

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
		$this->s2s--;
		foreach(Server::getInstance()->getLevelByName("game")->getPlayers() as $p){
			switch($this->s2s){
				case 900:
					$p->sendMessage(TextFormat::LIGHT_PURPLE . "You have 10 seconds of invincibility left.");
					break;
				case 895:
					$p->sendMessage(TextFormat::LIGHT_PURPLE . "You have 5 seconds of invincibilty left.");
					break;
				case 890:
					$p->sendMessage(TextFormat::LIGHT_PURPLE . "You are no longer invincible.");
					$this->owner->sendDifficulty(1);
					$p->getInventory()->clearAll();
					$p->getInventory()->addItem(Item::get(Item::IRON_AXE));
					$p->getInventory()->addItem(Item::get(Item::BOW));
					$p->getInventory()->addItem(Item::get(Item::ARROW, 0, 1));
					break;
				case 0:
					$this->owner->sendDifficulty(0);
					$p->sendMessage(TextFormat::RED . "Game ended, no one won!");
					break;
			}

			$p->sendTip("Kills: " . $this->owner->getKills($p) . "\nDeaths: " . $this->owner->getDeaths($p));

			if($this->owner->kills[$p->getName()] >= 20){
				$this->owner->sendDifficulty(0);
				Server::getInstance()->broadcastMessage(TextFormat::BOLD . TextFormat::AQUA . $p->getDisplayName() . " won the game!");
				CoreLoader::getInstance()->getEconomy()->addCoins($p, 10);

				Server::getInstance()->getScheduler()->scheduleRepeatingTask(new ServerRestartTask($this->owner), 25);
				Server::getInstance()->getScheduler()->cancelTask($this->getTaskId());
			}
		}
	}
}