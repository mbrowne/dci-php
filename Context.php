<?php
namespace DCI;

/**
 * DCI Context base class
 */
abstract class Context
{
	//Contexts can be RolePlayers too (i.e. nested contexts)
	use RolePlayer;

	//static private $instantiatedContexts = array();
	
	/**
	 * Any time addRole() is called on an object, it's added to this array so that the role
	 * can be removed when exiting the context (see __destruct())
	 * @var array
	 */
	private $rolePlayers = array();
	private $subContexts = array();
	
	/**
	 * Sub-contexts must be wrapped in ContextProxy objects in order to not interfere
	 * with the role binding of the parent context. 
	 * 
	 * Don't forget to use the proxy object returned by this method, not the $subContext object you passed in!
	 */
	function initSubContext(Context $subContext) {
		$subContext = new ContextProxy($subContext, $this);
		$this->subContexts[] = $subContext;
		return $subContext;
	}
	
	/**
	 * INTERNAL
	 * Allows us to keep track of all the role players in this context
	 * Should be called only from Role::__construct()
	 */
	function _addToInternalRolePlayerArray($rolePlayer) {
//		if (!in_array($this, self::$instantiatedContexts)) self::$instantiatedContexts[] = $this;
//		if ( ??? ) {
//			throw new \DCI\Exception("It appears that you are attempting to use an unitialized DCI sub-context - i.e. you did not call initSubContext(). ".
//				"when creating it. (This could also have been caused by instantiating an additional context, which should be unnecessary...sub-contexts should ".
//				"generally be the only reason to instantiate more than one context in the same request. To work around this limitation, ".
//				"create a DCI\\ContextProxy wrapping the additional context).");
//		}
		$this->rolePlayers[] = $rolePlayer;
		
		//var_dump($this->rolePlayers);
	}
	
	/**
	 * INTERNAL
	 */
	function _getRolePlayers() {
		return $this->rolePlayers;
	}
	
	/**
	 * CodeIgniter-specific
	 * __get
	 *
	 * Allows contexts to access CI's loaded classes using the same
	 * syntax as controllers.
	 *
	 * @param	string
	 */
	function __get($key)
	{
		$CI = get_instance();
		$val = @$CI->$key;
		if ($val == null) trigger_error("Undefined property: \$$key", E_USER_NOTICE);
		return $val;
	}
	
	/**
	 * Unbind (remove) all roles upon exiting the context
	 * (roles are ALWAYS context-specific, though there may be a role with the same name
	 * in another context, that would be a different role)
	 */	
	function removeAllRoles() {
		$thisContextClass = get_class($this);
		foreach ($this->rolePlayers as $rolePlayer) {
			$rolePlayer->removeAllRolesForContext($thisContextClass);
		}
		//unbind all roles from sub-contexts as well
		foreach ($this->subContexts as $subContext) {
			$subContext->removeAllRoles();
		}
	}
	
	function __destruct() {
		$this->removeAllRoles();
	}
}