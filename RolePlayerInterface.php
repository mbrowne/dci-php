<?php
namespace DCI;

/**
* This interface exists mainly for type-hinting purposes, although
* it also allows for alternative implementations of RolePlayer 
*/
interface RolePlayerInterface {
	function addRole($roleName, Context $context);
	function removeAllRolesForContext($context);
	function __call($methodName, $args);
}