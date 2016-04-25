<?php
namespace DataObjects;

class Account implements \DCI\RolePlayerInterface
{
	use \DCI\RolePlayer;

	private $balance = 0;

	function __construct($initialBalance) {
		$this->balance = $initialBalance;
	}

	function getBalance() {
		return $this->balance;
	}

	function increaseBalance($amount) {
		$this->balance += $amount;
	}

	function decreaseBalance($amount) {
		$this->balance -= $amount;
	}	
}