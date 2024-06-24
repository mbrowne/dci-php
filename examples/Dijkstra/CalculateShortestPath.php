<?php
namespace UseCases
{
    use DataObjects\Graph,
        DataObjects\Node,
        DataObjects\ObjectMap;

	class CalculateShortestPath extends \DCI\Context
	{
		// These would ideally be private but they need to be public so that the roles can
        // access them, since PHP doesn't support inner classes
        public Graph $graph;
		public ObjectMap $unvisitedNodes;
        public Node $currentNode;
        public ObjectMap $shortestPathSegments;

        function __construct(Graph $graph) {
            $this->graph = $graph->addRole('Graph', $this);

            $unvisitedNodes = $graph->allPaths();
            $this->unvisitedNodes = $unvisitedNodes->addRole('UnvisitedNodes', $this);

            $this->shortestPathSegments = (new ObjectMap())->addRole('ShortestPathSegments', $this);
        }

        public function calculate(Node $startNode, Node $destinationNode) {
            assert($this->graph->contains($startNode) && $this->graph->contains($destinationNode));

            $this->currentNode = $startNode->addRole('CurrentNode', $this);
            $this->currentNode->markVisited();

            $tentativeDistances = new ObjectMap([
                [$startNode, 0]
            ]);
            $this->tentativeDistances = $tentativeDistances->addRole('TentativeDistances', $this);
            foreach ($this->unvisitedNodes as $n => $distance) {
                // starting tentative value is infinity
                $this->tentativeDistances->setDistanceTo($n, INF);
            }

            while (!$this->unvisitedNodes->isEmpty()) {
                $this->currentNode->markVisited();
                if ( !$this->unvisitedNodes->hasNode($destinationNode) ) {
                    break;
                }

                $closest = $this->unvisitedNodes->findClosestFromStart();
                if (!$closest) {
                    break;
                }
                
                $this->currentNode = $closest->addRole('CurrentNode', $this);
            }

            $segments = [];
            for ($n = $destinationNode; $n != $startNode; $n = $this->shortestPathSegments->getPreviousNode($n)) {
                $segments[] = $n;
            }
            $startToEnd = array_merge([$startNode], array_reverse($segments));

            foreach ($startToEnd as $n) {
                debug($n);
            }

            return $startToEnd;
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
        function determineTentativeDistances() {
            foreach ($this->unvisitedNeighbors() as $neighbor) {
                $neighbor->addRole('Neighbor', $this->context);
                $neighbor->determineTentativeDistance();
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
        function determineTentativeDistance() {
            $currentNode = $this->context->currentNode;
            $tentativeDistances = $this->context->tentativeDistances;

            $tentativeDistanceToNeighbor = $tentativeDistances->distanceTo($this->self);

            $distanceFromStartToCurrent = $tentativeDistances->distanceTo($currentNode);
            $netDistance = $distanceFromStartToCurrent + $currentNode->distanceTo($this->self);

            if ($netDistance < $tentativeDistanceToNeighbor) {
                $tentativeDistances->setDistanceTo($this->self, $netDistance);
                $this->context->shortestPathSegments->addSegment($this->self, $this->context->currentNode);
            }
        }   
    }

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

        function findClosestFromStart() {
            $this->context->currentNode->determineTentativeDistances();
            
            $tentativeDistances = $this->context->tentativeDistances;
            $unvisitedNodes = $this->keys();

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

    trait TentativeDistances
    {
        function distanceTo(Node $n): float {
            return $this->get($n);
        }

        function setDistanceTo(Node $n, float $distance) {
            $this->set($n, $distance);
        }
    }

    trait ShortestPathSegments
    {
        function addSegment(Node $from, Node $to) {
            $this->set($from, $to);
        }

        function getPreviousNode(Node $n) {
            return $this->get($n);
        }
    }
}