<?php

namespace Core\Model;

Trait MagicTrait
{

	public function __get($key)
	{
		$method = 'get' . ucfirst($key);
		$this->$key = $this->$method();
		return $this->$key;
	}

}