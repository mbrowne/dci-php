<?php
namespace DataObjects;

class ObjectMap implements \DCI\RolePlayerInterface, \IteratorAggregate, \Countable {
    use \DCI\RolePlayer;

    private \SplObjectStorage $storage;

    function __construct(array $keyValuePairs = []) {
        $this->storage = new \SplObjectStorage();
        foreach ($keyValuePairs as $entry) {
            list($key, $val) = $entry;
            $this->set($key, $val);
        }
    }

    public function getIterator() {
        return new ObjectStorageIterator($this->storage);
    }

    public function get($key) {
        if (!$this->storage->contains($key)) {
            return null;
        }
        return $this->storage->offsetGet($key);
    }

    public function set($key, $value) {
        $this->storage->attach($key, $value);
    }

    public function remove($key) {
        $this->storage->detach($key);
    }

    public function contains($key) {
        return $this->storage->contains($key);
    }

    public function count() {
        return $this->storage->count();
    }

    // SplObjectStorage doesn't have a method to return all the keys,
    // so we need to write our own
    public function keys() {
        $keys = [];
        foreach ($this->storage as $key) {
            $keys[] = $key;
        }
        return $keys;
    }
}

// Source: https://gist.github.com/alexeyshockov/4fff855faa82bf23e621fb1e2ca07365
class ObjectStorageIterator extends \IteratorIterator
{
    public function __construct(\SplObjectStorage $storage)
    {
        parent::__construct($storage);
    }

    /**
     * @return object
     */
    public function key()
    {
        return $this->getInnerIterator()->current();
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->getInnerIterator()->getInfo();
    }
}
