<?php
namespace DataObjects;

class ObjectSet extends \SplObjectStorage implements \DCI\RolePlayerInterface, \Iterator, \Countable
{
    use \DCI\RolePlayer;

    function __construct(array $items = []) {
        foreach ($items as $i) {
            $this->set($i);
        }
    }

    public function get($item) {
        if (!$this->contains($item)) {
            return null;
        }
        return $this->offsetGet($item);
    }

    public function set($item) {
        $this->attach($item);
    }

    public function remove($item) {
        $this->detach($item);
    }

    public function toArray() {
        return iterator_to_array($this);
    }
}
