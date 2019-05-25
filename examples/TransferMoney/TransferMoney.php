<?php
namespace UseCases
{
	class TransferMoney extends \DCI\Context
	{
		//These would ideally be private but they need to be public so that the roles can access them,
		//since PHP doesn't support inner classes
		public $sourceAccount;
		public $destinationAccount;
		public $amount;

		function __construct($sourceAccount, $destinationAccount, $amount) {
			$this->sourceAccount = $sourceAccount->addRole('SourceAccount', $this);
			$this->destinationAccount = $destinationAccount->addRole('DestinationAccount', $this);
			$this->amount = $amount;
		}
		
		/**
		 * Transfer the amount from the source account to the destination account 
		 */
		function transfer() {
			$this->sourceAccount->transferOut($this->amount);
		}
	}
}

/**
 * Roles are defined in a sub-namespace of the context as a workaround for the fact that
 * PHP doesn't support inner classes.
 *
 * We use the trait keyword to define roles because the trait keyword is native to PHP (PHP 5.4+).
 * In an ideal world it would be better to have a "role" keyword -- think of "trait" as just
 * our implementation technique for roles in PHP.
 * (This particular implmentation for PHP actually uses a separate class for the role behind the scenes,
 * but that programmer needn't be aware of that.)
 */
namespace UseCases\TransferMoney\Roles
{
	trait SourceAccount
	{
		/**
		 * Withdraw the given amount from this account
		 * @param float $amount 
		 *
		 * Note that we could alternatively have retrieved the amount using $this->context->amount
		 * rather than having an $amount parameter here.
		 */
		function withdraw($amount) {
			$this->decreaseBalance($amount);
			//update transaction log...
		}
		
		/**
		 * Transfer the given amount from ("out" of) this account to the destination account
		 * @param float $amount
		 */
		function transferOut($amount) {
			$this->withdraw($amount);
			$this->context->destinationAccount->deposit($amount);
		}
	}
	
	trait DestinationAccount
	{
		function deposit($amount) {
			$this->increaseBalance($amount);
			//update transaction log...
		}
	}
}