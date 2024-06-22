<?php
namespace DataObjects;

class Graph implements \DCI\RolePlayerInterface
{
    use \DCI\RolePlayer;

    private ObjectMap $nodes;

    public function __construct(Edge ...$edges) {
        $this->nodes = new ObjectMap();
        foreach ($edges as $edge) {
            $neighborsFrom = $this->ensureNeighborsMapCreated($edge->from());
            $this->ensureNeighborsMapCreated($edge->to());
            $neighborsFrom->set($edge->to(), $edge->distance());
        }
    }

    private function ensureNeighborsMapCreated(Node $node) {
        $neighbors = $this->nodes->get($node);
        if (!$neighbors) {
            $neighbors = new ObjectMap();
            $this->nodes->set($node, $neighbors);
        }
        return $neighbors;
    }

    public function nodes() {
        return $this->nodes;
    }

    public function distanceBetween(Node $a, Node $b): float | null {
        $neighbors = $this->nodes->get($a);
        if (!$neighbors) {
            throw new \InvalidArgumentException("Node $a not found in graph");
        }
        // note: this might return null, and will always be null for any edges
        // not explicitly defined (we don't create bidirectional edges automatically)
        return $neighbors->get($b);
    }
}
