<?php
declare(strict_types=1);

namespace OITQ\Tasks;

use OITQ\Loader;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ServerRestartTask extends PluginTask{

	private $loader;
	private $timer = 21;

	/**
	 * @param Loader $loader
	 */
	public function __construct(Loader $loader){
		parent::__construct($loader);
		$this->loader = $loader;
	}

	/**
	 * @param $currentTick
	 */
	public function onRun($currentTick){
		$this->timer--;
		if($this->timer === 30){
			Server::getInstance()->broadcastMessage(TextFormat::GOLD . "Server will restart in 20 seconds.");
		}elseif($this->timer <= 3){
			Server::getInstance()->broadcastMessage(TextFormat::GOLD . "Server restarting in " . $this->timer . " second(s)...");
		}
		if($this->timer === 1){
			Server::getInstance()->shutdown(true, "Game restarting...");
		}
	}
}