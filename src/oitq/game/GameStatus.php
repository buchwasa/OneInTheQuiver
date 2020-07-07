<?php
declare(strict_types=1);

namespace oitq\game;

final class GameStatus{
	/** @var int */
	public const WAITING = 0;
	/** @var int */
	public const COUNTDOWN = 1;
	/** @var int */
	public const GAME = 2;
	/** @var int */
	public const RESET = 3;
}