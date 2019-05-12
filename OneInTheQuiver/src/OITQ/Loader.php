<?php
declare(strict_types=1);

namespace OITQ;

use OITQ\Tasks\CheckPlayersTask;
use OITQ\Tasks\RespawnTask;
use OITQ\Tasks\StartGameTask;
use pocketmine\entity\Arrow;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class Loader extends PluginBase implements Listener{

	public $hasGameStarted = false;
	public $queue = [];
	public $kills = [];
	public $deaths = [];

	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->getServer()->loadLevel("hub");
		$this->getServer()->loadLevel("game");

		$this->startCheckPlayersTask();

		$this->sendDifficulty(0);

	}

	/**
	 * @return int
	 */
	public function startCheckPlayersTask() : int{
		return $this->getServer()->getScheduler()->scheduleRepeatingTask(new CheckPlayersTask($this), 25)->getTaskId();
	}

	/**
	 * @param $difficulty
	 */
	public function sendDifficulty($difficulty){
		$this->getServer()->setConfigInt("difficulty", $difficulty);
	}

	/**
	 * @param Player $player
	 */
	public function sendKit(Player $player){
		$player->getInventory()->clearAll();
		$player->getInventory()->addItem(Item::get(Item::IRON_AXE, 0, 1));
		$player->getInventory()->addItem(Item::get(Item::BOW, 0, 1));
		$player->getInventory()->addItem(Item::get(Item::ARROW, 0, 1));
	}

	/**
	 * @return CheckPlayersTask
	 */
	public function getTimer() : CheckPlayersTask{
		$task = new CheckPlayersTask($this);

		return $task;
	}

	/**
	 * @param Player $player
	 *
	 * @return mixed
	 */
	public function getDeaths(Player $player){
		if(!isset($this->deaths[$player->getName()])){
			$this->deaths[$player->getName()] = 0;
		}

		return $this->deaths[$player->getName()];
	}

	/**
	 * @param Player $player
	 */
	public function addDeath(Player $player){
		if(isset($this->deaths[$player->getName()])){
			$this->deaths[$player->getName()] = $this->deaths[$player->getName()] + 1;
		}
	}

	/**
	 * @param Player $player
	 *
	 * @return mixed
	 */
	public function getKills(Player $player){
		if(!isset($this->kills[$player->getName()])){
			$this->kills[$player->getName()] = 0;
		}

		return $this->kills[$player->getName()];
	}

	/**
	 * @param PlayerJoinEvent $event
	 */
	public function handleJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		if(!isset($this->queue[$player->getName()])){
			$this->queue[$player->getName()] = $event->getPlayer()->getName();
		}
	}

	/**
	 * @param PlayerQuitEvent $event
	 */
	public function handleQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		if(isset($this->queue[$player->getName()])){
			unset($this->queue[$player->getName()]);
		}
	}

	/**
	 * @param PlayerPreLoginEvent $event
	 */
	public function handlePreLogin(PlayerPreLoginEvent $event){
		$player = $event->getPlayer();
		if($player->getName() === "TheAppleGamerYT"){
			$player->setDisplayName("AppleDevelops");
			$player->setNameTag("AppleDevelops");
		}

		if($this->hasGameStarted === true){
			$player->kick("The game has already started!", false);
		}
	}

	/**
	 * @param BlockBreakEvent $event
	 */
	public function handleBreak(BlockBreakEvent $event){
		$event->setCancelled();
	}


	//GAME EVENTS

	/**
	 * @param BlockPlaceEvent $event
	 */
	public function handlePlace(BlockPlaceEvent $event){
		$event->setCancelled();
	}

	/**
	 * @param ProjectileHitEvent $event
	 */
	public function handleProjectileHit(ProjectileHitEvent $event){
		$arrow = $event->getEntity();
		if($arrow instanceof Arrow){
			$arrow->kill();
		}
	}

	/**
	 * @param PlayerRespawnEvent $event
	 */
	public function handleRespawn(PlayerRespawnEvent $event){
		$player = $event->getPlayer();
		if($this->hasGameStarted === true){
			$this->getServer()->getScheduler()->scheduleDelayedTask(new RespawnTask($this, $player), 1);
		}
	}

	/**
	 * @param PlayerDeathEvent $event
	 */
	public function handleDeath(PlayerDeathEvent $event){
		$player = $event->getPlayer();
		$event->setDrops([]);
		$cause = $player->getLastDamageCause();

		$this->addDeath($player);
		$event->setDeathMessage(TextFormat::AQUA . $player->getDisplayName() . " has been killed.");

		if($cause instanceof EntityDamageByEntityEvent){
			$damager = $cause->getDamager();
			if($damager instanceof Player){
				$this->sendKillItem($damager);
			}
		}
	}

	/**
	 * @param Player $player
	 */
	public function sendKillItem(Player $player){
		$player->getInventory()->addItem(Item::get(Item::ARROW, 0, 1));
		$this->addKill($player);
	}

	/**
	 * @param Player $player
	 */
	public function addKill(Player $player){
		if(isset($this->kills[$player->getName()])){
			$this->kills[$player->getName()] = $this->kills[$player->getName()] + 1;
		}
	}

	/**
	 * @param EntityDamageEvent $event
	 */
	public function handleDamage(EntityDamageEvent $event){
		if($event instanceof EntityDamageByEntityEvent){
			$damager = $event->getDamager();
			if($this->hasGameStarted === true || $this->getGameTimer()->s2s <= 890){
				if($damager instanceof Player){
					if($event instanceof EntityDamageByChildEntityEvent){
						$arrow = $event->getChild();
						if($arrow instanceof Arrow){
							$event->getEntity()->attack(20, new EntityDamageEvent($event->getEntity(), EntityDamageEvent::CAUSE_PROJECTILE, 20));
							if($damager !== $event->getEntity()){
								$this->sendKillItem($damager);
							}
						}
					}
				}
			}else{
				$event->setCancelled();
			}
		}
	}

	/**
	 * @return StartGameTask
	 */
	public function getGameTimer() : StartGameTask{
		$task = new StartGameTask($this);

		return $task;
	}
}