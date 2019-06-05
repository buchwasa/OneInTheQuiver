<?php
declare(strict_types=1);

namespace OITQ;

use pocketmine\entity\projectile\Arrow;
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
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class Loader extends PluginBase implements Listener{

	/** @var int */
	public $gameStatus = OITQTask::WAITING;
	/** @var Player[] */
	public $queue = [];
	/** @var array */
	public $kills = [];
	/** @var Level */
	public $map;

	public function onEnable(){
		$this->saveDefaultConfig();
		$this->map = $this->getServer()->getLevelByName((string)$this->getConfig()->get("map"));
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getScheduler()->scheduleRepeatingTask(new OITQTask($this), 20);
	}

	public function sendKit(Player $player){
		$inventory = $player->getInventory();
		$inventory->clearAll();
		$inventory->addItem(Item::get(Item::IRON_AXE, 0, 1));
		$inventory->addItem(Item::get(Item::BOW, 0, 1));
		$inventory->addItem(Item::get(Item::ARROW, 0, 1));
	}

	public function getKills(Player $player){
		if(!isset($this->kills[$player->getName()])){
			$this->kills[$player->getName()] = 0;
		}

		return $this->kills[$player->getName()];
	}

	public function handleJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		if(!isset($this->queue[$player->getName()])){
			$this->queue[$player->getName()] = $player;
		}
	}

	public function handleQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		if(isset($this->queue[$player->getName()])){
			unset($this->queue[$player->getName()]);
		}
	}

	public function handlePreLogin(PlayerPreLoginEvent $event){
		$player = $event->getPlayer();

		if($this->gameStatus > OITQTask::COUNTDOWN){
			$player->kick("The game has already started!", false);
		}
	}

	public function handleBreak(BlockBreakEvent $event){
		$event->setCancelled();
	}

	public function handlePlace(BlockPlaceEvent $event){
		$event->setCancelled();
	}

	public function handleProjectileHit(ProjectileHitEvent $event){
		$arrow = $event->getEntity();
		if($arrow instanceof Arrow){
			$arrow->kill();
		}
	}

	public function handleRespawn(PlayerRespawnEvent $event){
		$player = $event->getPlayer();
		if($this->gameStatus > OITQTask::COUNTDOWN){
			$player->teleport($this->map->getSafeSpawn());
			$this->sendKit($player);
		}
	}

	public function handleDeath(PlayerDeathEvent $event){
		$player = $event->getPlayer();
		$event->setDrops([]);
		$cause = $player->getLastDamageCause();

		$event->setDeathMessage(TextFormat::AQUA . $player->getDisplayName() . " has been killed.");

		if($cause instanceof EntityDamageByEntityEvent){
			$damager = $cause->getDamager();
			if($damager instanceof Player){
				$this->sendKillItem($damager);
			}
		}
	}

	public function sendKillItem(Player $player){
		if(isset($this->kills[$player->getName()])){
			$this->kills[$player->getName()] = $this->kills[$player->getName()] + 1;
		}

		$player->getInventory()->addItem(Item::get(Item::ARROW, 0, 1));
	}

	public function handleDamage(EntityDamageEvent $event){
		if($this->gameStatus === OITQTask::GAME){
			if($event instanceof EntityDamageByChildEntityEvent){
				$damager = $event->getDamager();
				$arrow = $event->getChild();
				if($damager instanceof Player){
					if($arrow instanceof Arrow){
						$event->getEntity()->attack(new EntityDamageEvent($event->getEntity(), EntityDamageEvent::CAUSE_PROJECTILE, 2000));
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