<?php
namespace DCI;

/**
 * This is thrown by RolePlayer if the requested method wasn't found in any of the roles an object is currently playing,
 * and also doesn't exist on the data object playing the role. It's thrown instead of PHP's native \BadMethodCallException
 * so that classes that use the RolePlayer trait can still define their own __call() method. For example, the Collection
 * class defines a __call() method that first delegates to RolePlayer::__call() but if the method isn't a role method, it catches
 * the DCI\BadMethodCallException and then implements its own __call() logic.
 */
class BadMethodCallException extends \BadMethodCallException {}