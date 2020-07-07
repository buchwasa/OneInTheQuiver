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
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use function in_array;

class Loader extends PluginBase implements Listener{

	/** @var int */
	public $gameStatus = OITQTask::WAITING;
	public $gameData = [];
	/** @var array */
	public $eliminations = [];
	/** @var World */
	public $map;

	public function onEnable() : void{
		$this->saveDefaultConfig();
		$this->map = $this->getServer()->getWorldManager()->getWorldByName($this->getConfig()->get("map"));
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getScheduler()->scheduleRepeatingTask(new OITQTask($this), 20);
	}

	public function sendKit(Player $player) : void{
		$inventory = $player->getInventory();
		$inventory->clearAll();
		$inventory->addItem(VanillaItems::ARROW());
		$inventory->addItem(VanillaItems::BOW());
		$inventory->addItem(VanillaItems::IRON_AXE());
	}

	public function handleJoin(PlayerJoinEvent $ev) : void{
		$player = $ev->getPlayer();

		$dataArray = $this->gameData[$player->getName()] = [
			"player" => $player,
			"eliminations" => 0
		];

		$pk = new GameRulesChangedPacket();
		$pk->gameRules = ["doimmediaterespawn" => new BoolGameRule(true)];
		$player->getNetworkSession()->sendDataPacket($pk);
	}

	public function handleQuit(PlayerQuitEvent $ev) : void{
		$player = $ev->getPlayer();
		if(isset($this->gameData[$player->getName()])){
			unset($this->gameData[$player->getName()]);
		}
	}

	public function handlePreLogin(PlayerPreLoginEvent $ev) : void{
		if($this->gameStatus > OITQTask::COUNTDOWN){
			$ev->setKickReason(PlayerPreLoginEvent::KICK_REASON_PLUGIN, "The game has already started!");
		}
	}

	public function handleBreak(BlockBreakEvent $ev) : void{
		$ev->setCancelled();
	}

	public function handlePlace(BlockPlaceEvent $ev) : void{
		$ev->setCancelled();
	}

	public function handleProjectileHit(ProjectileHitEvent $ev) : void{
		$arrow = $ev->getEntity();
		if($arrow instanceof Arrow){
			$arrow->kill();
		}
	}

	public function handleRespawn(PlayerRespawnEvent $ev) : void{
		$player = $ev->getPlayer();
		if($this->gameStatus > OITQTask::COUNTDOWN){
			$player->teleport($this->map->getSafeSpawn());
			$this->sendKit($player);
		}
	}

	public function handleDeath(PlayerDeathEvent $ev) : void{
		$player = $ev->getPlayer();
		$ev->setDrops([]);
		$ev->setDeathMessage("");
		$cause = $player->getLastDamageCause();

		if(in_array($cause->getCause(), [EntityDamageEvent::CAUSE_ENTITY_ATTACK, EntityDamageEvent::CAUSE_PROJECTILE])){
			$damager = $cause->getDamager();
			if($damager instanceof Player && $damager !== $player){
				$damager->sendPopup(TextFormat::RED . "Eliminated " . $player->getDisplayName());
				$this->gameData[$player->getName()]["eliminations"]++;
				$player->getInventory()->addItem(VanillaItems::ARROW());
			}
		}
	}

	public function handleDamage(EntityDamageEvent $ev) : void{
		$cause = $ev->getCause();
		if($this->gameStatus !== OITQTask::GAME){
			$ev->setCancelled();
		}

		if($ev instanceof EntityDamageByChildEntityEvent){
			$damager = $ev->getDamager();
			$entity = $ev->getEntity();
			$childEntity = $ev->getChild();
			if($damager instanceof Player && $childEntity instanceof Arrow){
				$entity->attack(new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_PROJECTILE, $entity->getMaxHealth()));
			}
		}
	}
}