<?php
namespace DomainObjects;

class Account extends \DCI\RolePlayer
{
	protected $balance = 0;

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