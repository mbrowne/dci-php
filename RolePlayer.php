<?php
namespace DCI;

/**
 * DCI base RolePlayer trait (should be made an actual trait in PHP 5.4)
 * All data/domain objects that could potentially be used as role players in
 * a DCI context should use this trait, or inherit from a base class that uses this trait
 * 
 * NOTE: In an ideal DCI implementation, it would be possible to override a data object method
 * "foo" with a role method also named "foo". Unfortunately, the __call() magic method in
 * PHP only gets called if a method isn't found, so the call $this->foo() will always go to the data
 * object if there is a "foo" method defined there. So the names of role methods always need to be
 * different from any existing methods on the data class.
 */
abstract class RolePlayer implements RolePlayerInterface
{
	/**
	 * An array of the methods currently being played by this object,
	 * indexed by the classname of the context. The method names are keys
	 * pointing to the role objects, e.g.:
	 * 
	 * $roleMethods = array(
	 *		'MoneyTransferContext' => array(
	 *			'withdraw' => [SourceAccount role object]
	 *			'transferFrom' => [SourceAccount role object]
	 *			'deposit' => [Destinationaccount role object]
	 *		)
	 * )
	 * 
	 * The current struture obviously does not allow for more than one role in the same
	 * context with the same method name, but that feature is certainly possible
	 * and may be added in the future.
	 * 
	 * @var array
	 */
	private $roleMethods = array();
	/**
	 * The currently active DCI context
	 * @var DCI\Context 
	 */
	private $currentContext;
	private $currentContextClassName;
	
	/**
	 * Add a role to this object
	 * @param string $roleName
	 * @param Context $context
	 * @return \DCI\RolePlayer
	 */
	function addRole($roleName, Context $context) {
		$this->_setCurrentContext($context);		
		$roleClassName = $this->currentContextClassName.'\Roles\\'.$roleName;
		
		if (!class_exists($roleClassName, false)) {
			throw new \InvalidArgumentException("The role '$roleClassName' is not defined
				(it should be defined in the same *file* as the context to which it belongs)."
			);
		}
		
		$role = new $roleClassName($this, $context);
		
		$this->bindRoleMethods($role);
		return $this;
	}
	
	function removeRole($roleName, Context $context) {
		$roleMethods = &$this->roleMethods[get_class($context)];
		if ($roleMethods) {
			foreach ($roleMethods as $methodName => $role) {
				if (preg_match('/\\'.$roleName.'$/i', get_class($role))) {
					unset($roleMethods[$methodName]);
				}
			}
		}
		return $this;
	}
	
	protected function bindRoleMethods($role) {
		$contextClassName = $this->currentContextClassName;
		
		//Get all public method names for this role using reflection
		$reflRole = new \ReflectionClass($role);
		$reflMethods = $reflRole->getMethods(\ReflectionMethod::IS_PUBLIC);
		
		//Add the role methods to this object (AKA this RolePlayer)
		$existingRoleMethods = (array_key_exists($contextClassName, $this->roleMethods)) ?
			$this->roleMethods[$contextClassName]: array();
		
		foreach ($reflMethods as $method) {
			$methodName = $method->name;
			
			//these methods are on the base Role class
			if (in_array($methodName, array('__construct', '__get', '__set', '__isset', '__call', 'isPublicDataProperty', 'getDataObject')))
				continue;
			
			if (method_exists($this, $methodName)) {
				//Technically it's only *public* methods on the data-object that truly can't be overridden by 
				//role methods (because __call() is only called when a method isn't found), but we forbid it for
				//private and protected methods as well, for consistency
				throw new \Exception("The method '$methodName' already exists on the class '".get_class($this)."'.
					Due to limitations of PHP, a role method cannot override a data-object method of the same name.
					Please rename one of the methods.");
			}
			
			if (array_key_exists($methodName, $existingRoleMethods)) {
				$conflictingRole = $existingRoleMethods[$methodName];
				$conflictingRoleClassName = get_class($conflictingRole);
				throw new \Exception("Error binding role '".get_class($role)."': The method '$methodName' was already added via the role '$conflictingRoleClassName'.
					Please name it something different. (In a future version of this DCI library, multiple roles with
					methods sharing the same name may be allowed, but this is not currently supported due to limitations of PHP).)");
			}
			$this->roleMethods[$contextClassName][$methodName] = $role;
		}
	}
	
	/**
	 * ** INTERNAL **
	 * Only public so it can be accessed by the Context class
	 * @param Context $context 
	 */
	function _setCurrentContext(Context $context) {
		//removeAllRolesForContext should always be called when exiting the context,
		//so it should be safe to only set the context the first time addRole() is called
		//from within a context (rather than every time addRole() is called)
		if (empty($this->currentContext)) {
			$this->currentCoontext = $context;
			$this->currentContextClassName = get_class($context);
		}
	}
	
	/**
	 * Remove all roles beloning to the given context
	 * @param string|Context $context The classname of the context, or the Context object
	 */
	function removeAllRolesForContext($context) {
		if (is_string($context)) $contextClassName = $context;
		else $contextClassName = get_class($context);
		
		$this->roleMethods[$contextClassName] = array();
		$this->currentContext = null;
	}
	
	/**
	 * IMPORTANT!
	 * If subclasses implement __call(), they MUST call parent::__call()
	 * (either before or after their own __call() logic) or else role methods will not work!
	 */
	function __call($methodName, $args) {
		if (isset($this->roleMethods[$this->currentContextClassName][$methodName])) {
			$role = $this->roleMethods[$this->currentContextClassName][$methodName];
		}
		if (!isset($role) || !method_exists($role, $methodName)) {
			//This throws \DCI\BadMethodCallException instead of just \BadMethodCallException for an important reason.
			//See \BadMethodCallException for what that reason is.
			throw new \DCI\BadMethodCallException(
				"There is no public method '$methodName' on class '".get_class($this)."' nor on any of the roles it is currently playing ".
				"(note that it might not be playing any roles, in which case this is just a regular bad method call). ".
				"If the role belongs to a context that acts as a sub-context in another context, make sure that the parent context initialized the ".
				'sub-context correctly, e.g. $this->fooContext = $this->initSubContext(new \UseCases\Foo($arg1, $arg2)).');
		}
		//Call $role->$methodName() with the given arguments
		return call_user_func_array(array($role, $methodName), $args);
	}
}