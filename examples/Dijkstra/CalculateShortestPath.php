<?php
/*
  Algorithm
  (see https://en.wikipedia.org/wiki/Dijkstra%27s_algorithm for more details)

  Let us choose a starting node, and let the distance of node N be the distance from the
  starting node to N. Dijkstra's algorithm will initially start with infinite distances
  and will try to improve them step by step.

    1. Mark all nodes as unvisited. Create a set of all the unvisited nodes.

    2. Assign to every node a tentative distance from start value: for the starting node,
    it is zero, and for all other nodes, it is infinity. Set the starting node as the
    current node.

    3. For the current node, consider all of its unvisited neighbors and update their
    distances through the current node; compare the newly calculated distance to the one
    currently assigned to the neighbor and assign it the smaller one. For example, if the
    current node A is marked with a distance of 6, and the edge connecting it with its
    neighbor B has length 2, then the distance to B through A is 6 + 2 = 8. If B was
    previously marked with a distance greater than 8, then update it to 8 (the path to B
    through A is shorter). Otherwise, keep its current distance (the path to B through A
    is not the shortest).

    4. When we are done considering all of the unvisited neighbors of the current node,
    mark the current node as visited and remove it from the unvisited set. A visited node
    is never checked again.

    5. If the unvisited set is empty, or contains only nodes with infinite distance
    (which are unreachable), then stop - the algorithm is finished. In this
    implementation we are only concerned about the path to the destination node, so we
    also terminate here if the current node is the destination node.
    
    6. Select the unvisited node that is marked with the smallest tentative distance, and
    set it as the new current node, then go back to step 3.

  Once the loop exits (steps 3–5), we will know the shortest distance from the starting
  node to every visited node.
 */


namespace UseCases
{
    use DataObjects\Graph,
        DataObjects\Node,
        DataObjects\ObjectMap,
        DataObjects\ObjectSet;

    class CalculateShortestPath extends \DCI\Context
    {
        // These would ideally be private but they need to be public so that the roles can
        // access them, since PHP doesn't support inner classes
        public Graph $graph;
        public ObjectSet $unvisitedNodes;
        public Node $startNode;
        public Node $currentNode;
        public ObjectMap $shortestPathSegments;

        function __construct(Graph $graph) {
            $this->graph = $graph->addRole('Graph', $this);

            $unvisitedNodes = new ObjectSet($graph->nodes());
            $this->unvisitedNodes = $unvisitedNodes->addRole('UnvisitedNodes', $this);

            $this->shortestPathSegments = (new ObjectMap())->addRole('ShortestPathSegments', $this);
        }

        public function shortestPathFrom(Node $startNode, Node $destinationNode) {
            assert($this->graph->contains($startNode) && $this->graph->contains($destinationNode));

            $this->startNode = $startNode->addRole('StartNode', $this);
            $this->destinationNode = $destinationNode->addRole('DestinationNode', $this);
            $this->currentNode = $startNode->addRole('CurrentNode', $this);
            $this->currentNode->markVisited();

            $tentativeDistances = new ObjectMap([
                [$this->startNode, 0]
            ]);
            $this->tentativeDistances = $tentativeDistances->addRole('TentativeDistances', $this);
            foreach ($this->unvisitedNodes as $n) {
                // starting tentative value is infinity
                $this->tentativeDistances->setDistanceTo($n, INF);
            }

            while ($nextUnvisitedNode = $this->currentNode->traverse()) {
                $this->currentNode = $nextUnvisitedNode->addRole('CurrentNode', $this);
            }

            for (
                $n = $this->destinationNode;
                $n != $this->startNode;
                $n = $this->shortestPathSegments->getPreviousNode($n)
            ) {
                $segments[] = $n;
            }
            $startToEnd = array_reverse($segments);
            return array_merge([$this->startNode], $startToEnd);
        }
    }
}

/**
 * Roles are defined in a sub-namespace of the context as a workaround for the fact that
 * PHP doesn't support inner classes.
 */
namespace UseCases\CalculateShortestPath\Roles
{
    use DataObjects\Node;

    trait Graph {
        function distanceBetweenNodes(Node $from, Node $to): float {
            return $this->distanceBetween($from, $to);
        }

        function neighborsOf(Node $n) {
            $paths = $this->pathsFrom($n);
            assert($paths != null);
            return $this->pathsFrom($n)->keys();
        }
    }

    trait CurrentNode
    {
        // visit this node, and determine the next closest unvisited node from the start
        function traverse(): Node | null {
            $this->markVisited();
            if ( !$this->context->unvisitedNodes->hasNode($this->context->destinationNode) ) {
                return null;
            }
            return $this->context->startNode->findClosestUnvisitedNode();
        }

        function determinePreviousInPath() {
            foreach ($this->unvisitedNeighbors() as $neighbor) {
                $neighbor->addRole('Neighbor', $this->context);
                if ($neighbor->shorterPathAvailable()) {
                    $this->context->shortestPathSegments->setSegment($neighbor, $this->self);
                }
            }
        }

        private function unvisitedNeighbors() {
            return array_filter($this->context->graph->neighborsOf($this->self), function($neighbor) {
                return $this->context->unvisitedNodes->hasNode($neighbor);
            });
        }

        function distanceTo(Node $neighbor) {
            return $this->context->graph->distanceBetweenNodes($this->self, $neighbor);
        }

        function markVisited() {
            $this->context->unvisitedNodes->removeNode($this->self);
        }
    }

    trait Neighbor
    {
        // Is there a shorter path (from the start node to this node) than previously
        // determined?
        function shorterPathAvailable() {
            $tentativeDistances = $this->context->tentativeDistances;
            $currentNode = $this->context->currentNode;

            $distanceFromStartToCurrent = $tentativeDistances->distanceTo($currentNode);
            $netDistance = $distanceFromStartToCurrent + $currentNode->distanceTo($this->self);

            if ($netDistance < $tentativeDistances->distanceTo($this->self)) {
                $tentativeDistances->setDistanceTo($this->self, $netDistance);
                return true;
            }
            return false;
        }
    }

    trait StartNode
    {
        function findClosestUnvisitedNode() {
            $this->context->currentNode->determinePreviousInPath();

            $tentativeDistances = $this->context->tentativeDistances;
            $unvisitedNodes = iterator_to_array($this->context->unvisitedNodes);
            if (empty($unvisitedNodes)) {
                return null;
            }

            return array_reduce(
                $unvisitedNodes,
                function($x, $y) use ($tentativeDistances) {
                    return $tentativeDistances->distanceTo($x) < $tentativeDistances->distanceTo($y)
                        ? $x
                        : $y;
                },
                $unvisitedNodes[0]
            );
        }
    }

    trait DestinationNode {}

    trait UnvisitedNodes
    {
        function removeNode(Node $n) {
            $this->remove($n);
        }

        function hasNode(Node $n) {
            return $this->contains($n);
        }

        function isEmpty() {
            return $this->count() === 0;
        }
    }

    trait TentativeDistances
    {
        function distanceTo(Node $n): float {
            return $this->get($n);
        }

        function setDistanceTo(Node $n, float $distance) {
            $this->set($n, $distance);
        }
    }

    // shortest paths from each node back to the start
    trait ShortestPathSegments
    {
        function setSegment(Node $from, Node $to) {
            $this->set($from, $to);
        }

        function getPreviousNode(Node $n) {
            return $this->get($n);
        }
    }
}