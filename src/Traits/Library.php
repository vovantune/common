<?php

namespace ArtSkills\Traits;

trait Library
{

	/**
	 * Защищаем от создания через new
	 */
	private function __construct()
	{
	}

	/**
	 * Защищаем от создания через клонирование
	 */
	private function __clone()
	{
	}

	/**
	 * Защищаем от создания через unserialize
	 */
	private function __wakeup()
	{
	}
}