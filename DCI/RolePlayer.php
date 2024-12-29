<?php
namespace DCI;

/**
 * DCI base RolePlayer trait
 * All objects that could potentially be used as role players in a DCI context should use this trait,
 * or inherit from a base class that uses this trait.
 * 
 * NOTE: In an ideal DCI implementation, it would be possible to override a data object method
 * "foo" with a role method also named "foo". Unfortunately, the __call() magic method in
 * PHP only gets called if a method isn't found, so the call $this->foo() will always go to the data
 * object if there is a "foo" method defined there. So the names of role methods always need to be
 * different from any existing methods on the data class.
 */
trait RolePlayer
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
		$roleNamespace = $this->currentContextClassName.'\Roles';
		$roleTraitName = $roleNamespace.'\\'.$roleName;
		
		if (!trait_exists($roleTraitName, false)) {
			throw new \InvalidArgumentException("The role '$roleTraitName' is not defined
				(it should be defined in the same *file* as the context to which it belongs)."
			);
		}
		
		//We need a real class (and not just a trait) that extends DCI\Role in order to instantiate the role object
		$roleClassName = $roleTraitName.'Class';
		if (!class_exists($roleClassName, false)) {
			//Check for collection-type role player (see bindRoleMethods() for an explanation of why we need this).
			//TODO We shouldn't assume that role players implementing Iterator also implement
			//ArrayAccess and Countable. It would be better to implement separate traits for each of these
			//rather than the single DCI\CollectionRole class we have now.
			if ($this instanceof \Iterator) {
				$roleClass = 'CollectionRole';
			}
			else $roleClass = 'Role';
			
			eval('namespace '.$roleNamespace.'; class '.$roleName.'Class extends \DCI\\'.$roleClass.' {use \\'.$roleTraitName.';}');
		}
		$role = new $roleClassName($this, $context, $roleName);
		
		$this->bindRoleMethods($roleName, $role);
		return $this;
	}
	
	function removeRole($roleName, Context $context) {
		$roleMethods = &$this->roleMethods[get_class($context)];
		if ($roleMethods) {
			foreach ($roleMethods as $methodName => $role) {
				if (preg_match('/\\'.$roleName.'Class$/i', get_class($role))) {
					unset($roleMethods[$methodName]);
				}
			}
		}
		return $this;
	}
	
	protected function bindRoleMethods($roleName, $role) {
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
			$roleClassMethods = ['__construct', '__get', '__set', '__call'];
			
			//Check for collection-type role player.
			//TODO We shouldn't assume that role players implementing Iterator also implement
			//ArrayAccess and Countable. It would be better to implement separate traits for each of these
			//rather than the single DCI\CollectionRole class we have now.
			if ($this instanceof \Iterator) {
				//If the role player implements the Iterator, ArrayAccess, and/or Countable interface,
				//then instead of using DCI\Role we use DCI\CollectionRole in order to delegate to the
				//methods implementing those interfaces, so we need to add some methods to the array of
				//methods of the role class...
				$roleClassMethods = array_merge($roleClassMethods, [
					'current', 'key', 'next', 'rewind', 'valid', //Iterator
					'offsetExists', 'offsetGet', 'offsetSet', 'offsetUnset', //ArrayAccess
					'count' //Countable
				]);
			}
			
			if (in_array($methodName, $roleClassMethods))
				continue;
			
			if (method_exists($this, $methodName)) {
				//Technically it's only *public* methods on the data-object that truly can't be overridden by 
				//role methods (because __call() is only called when a method isn't found), but we forbid it for
				//private and protected methods as well, for consistency
				throw new Exception("The method '$methodName' already exists on the class '".get_class($this)."'.
					Due to limitations of PHP, a role method cannot override a data-object method of the same name.
					Please rename one of the methods.");
			}
			
			if (array_key_exists($methodName, $existingRoleMethods)) {
				$conflictingRole = $existingRoleMethods[$methodName];
				if ($conflictingRole->roleName == $roleName) {
					// This role was already bound; ignore and continue.
					// (We allow this because it can be useful for contexts whose logic
					// includes re-binding of roles.)
					continue;
				}

				$conflictingRoleClassName = get_class($conflictingRole);
				throw new Exception("Error binding role '".get_class($role)."': The method '$methodName' was already added via the role '$conflictingRoleClassName'.
					Please name it something different. (In a future version of this DCI library, multiple roles with
					methods sharing the same name might be allowed, but this is not currently supported due to limitations of PHP.");
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
			$this->currentContext = $context;
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
	 * If data classes (or any class whose objects will need to play a role) implement __call(),
	 * the overriden __call() method MUST call this __call() method or else role methods will not work!
	 * (This method can be called either before or after the data class's own __call() logic.)
	 *
	 * Here is an example of how to do this:
	 *
	 * abstract class DataObject implements \DCI\RolePlayerInterface
	 * {
	 *     use \DCI\RolePlayer {
	 *         __call as private RolePlayer__call;
	 *     }
	 *
	 *     function __call($methodName, $args) {
	 *         if ($this->hasRoleMethod($methodName)) {
	 *             return $this->RolePlayer__call($methodName, $args);
	 *         }
	 *         //your custom __call() logic
	 *     }
	 *     ...
	 * }
	 */
	function __call($methodName, $args) {
		if (! $this->hasRoleMethod($methodName)) {
			throw new \BadMethodCallException(
				"There is no public method '$methodName' on class '".get_class($this)."' nor on any of the roles it is currently playing ".
				"(note that it might not be playing any roles, in which case this is just a regular bad method call). ".
				"If the role belongs to a context that acts as a sub-context in another context, make sure that the parent context initialized the ".
				'sub-context correctly, e.g. $this->fooContext = $this->initSubContext(new \UseCases\Foo($arg1, $arg2)).');
			
		}
		//Call $role->$methodName() with the given arguments
		$role = $this->roleMethods[$this->currentContextClassName][$methodName];
		return call_user_func_array(array($role, $methodName), $args);
	}
	
	/**
	 * Returns true if this object is currently playing a role with the given role method
	 * @param string $methodName
	 * @return bool
	 */
	public function hasRoleMethod($methodName) {
		if (isset($this->roleMethods[$this->currentContextClassName][$methodName])) {
			$role = $this->roleMethods[$this->currentContextClassName][$methodName];
		}
		return isset($role) && method_exists($role, $methodName);
	}
}