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
 * PHP doesn't support inner classes 
 */
namespace UseCases\TransferMoney\Roles
{
	use DCI\Role;
	
	class SourceAccount extends Role
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
			$this->context->destinationAccount->deposit($amount);
			$this->withdraw($amount);
		}
	}
	
	class DestinationAccount extends Role
	{
		function deposit($amount) {
			$this->increaseBalance($amount);
			//update transaction log...
		}
		
		/*
		These methods are here for illustrative purposes only.
		They're commented out because they're not used in the context as currently written, so there's
		no reason for them to exist in this example (since roles are context-specific)
		
		function transferIn($amount) {
			$this->withdraw($amount);
			$this->context->sourceAccount->deposit($amount);
		}
		
		function withdraw($amount) {
			$this->decreaseBalance($amount);
		}
		*/
	}
}