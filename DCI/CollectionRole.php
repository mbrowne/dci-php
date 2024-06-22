<?php
namespace DCI;

/**
 * DCI Role class for roles that will be played by collection objects
 * that implement ArrayAccess, Iterator, and Countable.
 * 
 * Should never be used externally; this is used behind the scenes
 * to accomplish role binding.
 */
abstract class CollectionRole extends Role implements \Iterator, \ArrayAccess, \Countable
{
	function current() {
		return $this->self->current();
	}

	function key() {
		return $this->self->key();
	}

	function next() {
		$this->self->next();
	}
	
	function rewind() {
		$this->self->rewind();
	}
	
	function valid() {
		return $this->self->valid();
	}
	
	function offsetExists($offset) {
		return $this->self->offsetExists($offset);
	}
	
	function offsetGet($offset) {
		return $this->self->offsetGet($offset);
	}
	
	function offsetSet($offset, $value) {
		$this->self->offsetSet($offset, $value);
	}
	
	function offsetUnset($offset) {
		$this->self->offsetUnset($offset);
	}
	
	function count() {
		return $this->self->count();
	}
}