<?php
namespace DCI;

class ContextProxy
{
	private $context, $parentContext;
	
	function __construct(Context $ctx, Context $parentCtx) {
		$this->context = $ctx;
		$this->parentContext = $parentCtx;
	}
	
	function removeAllRoles() {
		$this->context->removeAllRoles();
	}
	
	function __call($methodName, $args) {
		$rolePlayers = array_merge($this->context->_getRolePlayers(), $this->parentContext->_getRolePlayers());
		foreach ($rolePlayers as $rolePlayer) {
			$rolePlayer->_setCurrentContext($this->context);
		}
		
		$ret = call_user_func_array(array($this->context, $methodName), $args);
		
		//TODO this should only happen for roles that previously belonged to the parent context
		foreach ($rolePlayers as $rolePlayer) {
			$rolePlayer->_setCurrentContext($this->parentContext);
		}
		
		return $ret;
	}
	
	function __get($key) {
		return $this->context->$key;
	}
}