<?php
namespace DataObjects;

class Graph implements \DCI\RolePlayerInterface
{
    use \DCI\RolePlayer;

    private ObjectMap $nodeMap;

    public function __construct(Edge ...$edges) {
        $this->nodeMap = new ObjectMap();

        foreach ($edges as $edge) {
            $pathsFrom = $this->ensurePathMapCreated($edge->from());
            $this->ensurePathMapCreated($edge->to());
            $pathsFrom->set($edge->to(), $edge->distance());
        }
    }

    private function ensurePathMapCreated(Node $node) {
        $paths = $this->nodeMap->get($node);
        if (!$paths) {
            $paths = new ObjectMap();
            $this->nodeMap->set($node, $paths);
        }
        return $paths;
    }

    public function allPaths() {
        $cloned = new ObjectMap();
        foreach ($this->nodeMap as $node => $paths) {
            $cloned->set($node, clone $paths);
        }
        return $cloned;
    }

    function pathsFrom(Node $n) {
        return $this->nodeMap->get($n);
    }

    public function distanceBetween(Node $x, Node $y): float | null {
        $neighbors = $this->nodeMap->get($x);
        if (!$neighbors) {
            throw new \InvalidArgumentException("Node $x not found in graph");
        }
        // note: this might return null, and will always be null for any edges
        // not explicitly defined (we don't create bidirectional edges automatically)
        return $neighbors->get($y);
    }
}
