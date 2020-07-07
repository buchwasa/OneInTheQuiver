<?php
declare(strict_types=1);

namespace oitq\game;

use pocketmine\player\Player;

class GameSession{
	/** @var Player */
	private $player;
	/** @var int */
	private $eliminations;

	public function __construct(Player $player){
		$this->player = $player;
		$this->eliminations = 0;
	}

	public function addElimination() : void{
		$this->eliminations++;
	}

	public function getEliminations() : int{
		return $this->eliminations;
	}

	public function getPlayer() : Player{
		return $this->player;
	}
}