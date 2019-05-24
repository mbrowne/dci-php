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
	protected $data;
	/**
	 * The context to which this role belongs
	 * @var Context
	 */
	protected $context;
	
	private $publicDataPropertyNames = array();

	function __construct(RolePlayerInterface $data, Context $context) {
		$this->data = $data;
		$this->context = $context;
		$context->_addToInternalRolePlayerArray($data);
	}
	
	/**
	 * Get a data property
	 */
	function __get($propName) {
		//TODO
		//If this causes an undefined property notice, it does not currently report the line number
		//where the error actually occurred. It may be possible to correct this using
		//set_error_handler() to temporarily set the error handler to handle the "Undefined property" notice.
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
	
	/**
	 * This method is pubilc so that it can be accessed by model and ORM classes only;
	 * generally it shouldn't be necessary to access the $data property directly
	 */
	function getDataObject() {
		return $this->data;
	}
	
	/**
	 * Returns whether the given property name is a public property of $this->data 
	 * @param string
	 * @return bool
	 */
	private function isPublicDataProperty($propName) {
		//check if the property exists in the cached list of public properties
		if (array_key_exists($propName, $this->publicDataPropertyNames)) {
			return true;
		}
		//if it wasn't found in the cache, or if this method is being called for the first time,
		//call get_object_vars() again in case the property isn't defined on the object's class but
		//was dynamically added to it sometime after calling addRole(). Recall that PHP allows new
		//properties (not defined in the class) to be added to an object at any time.
		$this->publicDataPropertyNames = get_object_vars($this->data);
		if (array_key_exists($propName, $this->publicDataPropertyNames)) {
			return true;
		}
		return false;
	}
}