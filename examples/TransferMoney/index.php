<?php
ini_set('display_errors', 1);

set_include_path(__DIR__.'/../../');
require('Role.php');
require('RolePlayerInterface.php');
require('RolePlayer.php');
require('Context.php');

require('Account.php');
require('TransferMoney.php');


//In an MVC framework, the following code would go in a controller method

$acct1 = new \DomainObjects\Account(20);
$acct2 = new \DomainObjects\Account(0);
$moneyTransfer = new \UseCases\TransferMoney($acct1, $acct2, 10);

$moneyTransfer->transfer();

var_dump($acct1->getBalance(), $acct2->getBalance());