<?php
namespace DCI;

/**
 * DCI Role base class
 * 
 * In PHP 5.4 version this will be true:
 * (should never be used externally; this is used behind the scenes
 * to accomplish role binding.)
 */
abstract class Role
{
	/**
	 * The data object playing this role (the RolePlayer).
	 * @var RolePlayerInterface
	 */
	protected $data;
	/**
	 * The context to which this role belongs
	 * @var Context
	 */
	protected $context;

	function __construct(RolePlayerInterface $data, Context $context) {
		$this->data = $data;
		$this->context = $context;
		$context->_addToInternalRolePlayerArray($data);
	}
	
	/**
	 * Get a data property
	 */
	function __get($propName) {
		return $this->data->$propName;
	}
	
	/**
	 * Set a data property
	 */
	function __set($propName, $val) {
		$this->data->$propName = $val;
	}
	
	/**
	 * Returns whether or not the property is set on the data object.
	 * Note that for private/protected properties, this will always return false.
	 */
	function __isset($propName) {
		return isset($this->data->$propName);
	}
	
	/**
	 * Call a method on the data object. Data object methods should only be getters, setters,
	 * or simple data manipulation methods (or very simple calculation methods like a getName()
	 * method that returns a first and last name concatenated together).
	 * All other behavior should go in role methods, which are called normally and don't involve
	 * this __call function.
	 */
	function __call($methodName, $args) {
		//If the method is public
		if (in_array($methodName, get_class_methods($this->data))) {
			return call_user_func_array(array($this->data, $methodName), $args);		
		}
		//If the method is private or protected...
		elseif (method_exists($this->data, $methodName)) {
			//...then call the method using reflection
			$method = $this->data->getPrivateOrProtectedReflMethod($methodName);
			$method->setAccessible(true);
			return $method->invokeArgs($this->data, $args);
		}
		else {
			throw new \BadMethodCallException(
				"The method '$methodName' does not exist on the class '".get_class($this->data)."'
				nor on any of the roles it's currently playing.");
		}
		return call_user_func_array(array($this->data, $methodName), $args);
	}
	
	/**
	 * This method is pubilc so that it can be accessed by model and ORM classes only;
	 * generally it shouldn't be necessary to access the $data property directly
	 */
	function getDataObject() {
		return $this->data;
	}
}