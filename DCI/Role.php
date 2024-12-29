<?php
namespace DCI;

/**
 * DCI Role base class
 * 
 * Should never be used externally; this is used behind the scenes
 * to accomplish role binding.
 */
abstract class Role
{
	/**
	 * The data object playing this role (the RolePlayer).
	 * @var RolePlayerInterface
	 */
	private $data;
	/**
	 * The context to which this role belongs
	 * @var Context
	 */
	protected $context;
	/**
	 * The name of the role - just its local name within the context. Used for error messages.
	 * @var string
	 */
	private $roleName;

	function __construct(RolePlayerInterface $data, Context $context, $roleName) {
		$this->data = $data;
		$this->context = $context;
		$this->roleName = $roleName;
		$context->_addToInternalRolePlayerArray($data);
	}
	
	/**
	 * Implements $this->self from inside roles
	 */
	function __get($propName) {
		switch ($propName) {
			case 'self':
				return $this->data;
			case 'roleName':
				return $this->roleName;
		}
		throw new Exception("Cannot access property '$propName' via role '$this->roleName': roles can't access data properties directly, but only public methods.");
	}

	function __set($propName, $val) {
		throw new Exception("Cannot set property '$propName' via role '$this->roleName': roles can't set data properties directly, but only via public methods.");
	}
	
	/**
	 * Call a method on the data object. Data object methods should only be getters, setters,
	 * or simple data manipulation methods (or very simple calculation methods like a getName()
	 * method that returns a first and last name concatenated together).
	 * All other behavior should go in role methods, which are called normally and don't involve
	 * this __call function.
	 */
	function __call($methodName, $args) {
		//Make sure the method exists
		//(We check get_class_methods() rather than method_exists() because the method might be
		//private or protected, in which case we let PHP throw an error)
		if (in_array($methodName, get_class_methods($this->data))) {
			//Note that roles should only have access to public data object methods.
			//If the method is private or protected, PHP will throw an error here.
			return call_user_func_array(array($this->data, $methodName), $args);		
		}
		else {
			throw new \BadMethodCallException(
				"The method '$methodName' does not exist on the class '".get_class($this->data)."'
				nor on any of the roles it's currently playing.");
		}
		return call_user_func_array(array($this->data, $methodName), $args);
	}
}