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
		return $this->data->current();
	}

	function key() {
		return $this->data->key();
	}

	function next() {
		$this->data->next();
	}
	
	function rewind() {
		$this->data->rewind();
	}
	
	function valid() {
		return $this->data->valid();
	}
	
	function offsetExists($offset) {
		return $this->data->offsetExists($offset);
	}
	
	function offsetGet($offset) {
		return $this->data->offsetGet($offset);
	}
	
	function offsetSet($offset, $value) {
		$this->data->offsetSet($offset, $value);
	}
	
	function offsetUnset($offset) {
		$this->data->offsetUnset($offset);
	}
	
	function count() {
		return $this->data->count();
	}
}