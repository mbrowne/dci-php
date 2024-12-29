<?php
namespace DataObjects;

class Node implements \DCI\RolePlayerInterface {
    use \DCI\RolePlayer;

    private string $id;

    public function __construct($id) {
        $this->id = (string)$id;
    }

    public function id() {
        return $this->id;
    }

    public function __toString() {
        return $this->id;
    }
}
