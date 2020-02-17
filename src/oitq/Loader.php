<?php
declare(strict_types=1);

namespace oitq;

use pocketmine\entity\projectile\Arrow;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
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
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use function in_array;

class Loader extends PluginBase implements Listener{

	/** @var int */
	public $gameStatus = OITQTask::WAITING;
	public $gameData = [];
	/** @var array */
	public $eliminations = [];
	/** @var Level */
	public $map;

	public function onEnable(){
		$this->saveDefaultConfig();
		$this->map = $this->getServer()->getLevelByName("world");
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

	public function handleJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();

		$dataArray = $this->gameData[$player->getName()] = [
			"player" => $player,
			"eliminations" => 0
		];

		$pk = new GameRulesChangedPacket();
		$pk->gameRules = ["doimmediaterespawn" => [1, true]];
		$player->sendDataPacket($pk);
	}

	public function handleQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		if(isset($this->gameData[$player->getName()])){
			unset($this->gameData[$player->getName()]);
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
		$event->setDeathMessage("");
		$cause = $player->getLastDamageCause();

		if(in_array($cause->getCause(), [EntityDamageEvent::CAUSE_ENTITY_ATTACK, EntityDamageEvent::CAUSE_PROJECTILE])){
			/** @noinspection PhpUndefinedMethodInspection */
			$damager = $cause->getDamager();
			if($damager instanceof Player){
				$damager->sendPopup(TextFormat::RED . "Eliminated " . $player->getDisplayName());
				if($damager !== $player){
					$this->awardEliminator($damager);
				}
			}
		}
	}

	public function awardEliminator(Player $player){
		$this->gameData[$player->getName()]["eliminations"]++;

		$player->getInventory()->addItem(Item::get(Item::ARROW, 0, 1));
	}

	public function handleDamage(EntityDamageEvent $ev){
		$cause = $ev->getCause();
		if($this->gameStatus === OITQTask::GAME && in_array($cause, [EntityDamageEvent::CAUSE_ENTITY_ATTACK, EntityDamageEvent::CAUSE_PROJECTILE])){
			if($ev instanceof EntityDamageByChildEntityEvent){
				$damager = $ev->getDamager();
				$arrow = $ev->getChild();
				if($damager instanceof Player){
					if($arrow instanceof Arrow){
						$ev->getEntity()->attack(new EntityDamageEvent($ev->getEntity(), EntityDamageEvent::CAUSE_PROJECTILE, 2000));
					}
				}
			}
		}else{
			$ev->setCancelled();
		}
	}
}