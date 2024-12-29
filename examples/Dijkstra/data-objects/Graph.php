<?php
namespace DataObjects;

class Graph implements \DCI\RolePlayerInterface
{
    use \DCI\RolePlayer;

    private ObjectMap $paths;

    public function __construct(Edge ...$edges) {
        $this->paths = new ObjectMap();

        foreach ($edges as $edge) {
            $pathsFrom = $this->ensurePathMapCreated($edge->from());
            $this->ensurePathMapCreated($edge->to());
            $pathsFrom->set($edge->to(), $edge->distance());
        }
    }

    private function ensurePathMapCreated(Node $node) {
        $paths = $this->paths->get($node);
        if (!$paths) {
            $paths = new ObjectMap();
            $this->paths->set($node, $paths);
        }
        return $paths;
    }

    public function nodes() {
        return $this->paths->keys();
    }

    public function pathsFrom(Node $n) {
        return $this->paths->get($n);
    }

    public function contains(Node $n) {
        return $this->paths->contains($n);
    }

    public function distanceBetween(Node $x, Node $y): float | null {
        $neighbors = $this->paths->get($x);
        if (!$neighbors) {
            throw new \InvalidArgumentException("Node $x not found in graph");
        }
        // note: this might return null, and will always be null for any edges
        // not explicitly defined (we don't create bidirectional edges automatically)
        return $neighbors->get($y);
    }
}
