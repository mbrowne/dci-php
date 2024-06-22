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

            $this->unvisitedNodes->markVisited($startNode);
            $tentativeDistances = new ObjectMap([
                [$startNode, 0]
            ]);
            $this->tentativeDistances = (new ObjectMap())->addRole('TentativeDistances', $this);
            foreach ($this->unvisitedNodes as $n => $distance) {
                // starting value is infinity
                $this->tentativeDistances->setDistanceTo($n, INF);
            }

            $this->currentNode = $startNode->addRole('CurrentNode', $this);

            // while (!$this->unvisitedNodes->isEmpty()) {
            //     $this->currentNode->setTentativeDistancesOfNeighbors();
            //     $closestNeighbor = $this->currentNode->unvisitedNeighborWithShortestPath();
            //     // assert($closestNeighbor != null);

            //     $this->unvisitedNodes->markVisited($this->currentNode);
            //     $this->currentNode = $closestNeighbor->addRole('CurrentNode', $this);
            // }

            // ITERATION 1
            
            $this->currentNode->setTentativeDistancesOfNeighbors();

            $closestNeighbor = $this->currentNode->unvisitedNeighborWithShortestPath();

            // assert($closestNeighbor != null);

            debug('unvisitedNeighborWithShortestPath', $closestNeighbor);

            $this->unvisitedNodes->markVisited($this->currentNode);
            $this->shortestPathSegments->addSegment($this->currentNode, $closestNeighbor);

            $this->currentNode = $closestNeighbor->addRole('CurrentNode', $this);

            // ITERATION 2

            $this->currentNode->setTentativeDistancesOfNeighbors();
            
            $closestNeighbor = $this->currentNode->unvisitedNeighborWithShortestPath();
            $this->unvisitedNodes->markVisited($this->currentNode);
            
            $this->shortestPathSegments->addSegment($this->currentNode, $closestNeighbor);

            debug('tentative distances:');
            foreach ($this->tentativeDistances as $k => $d) {
                debug($k, $d);
            }

            debug('$this->unvisitedNodes->count()', $this->unvisitedNodes->count());

            debug('shortest path segments:');
            foreach ($this->shortestPathSegments as $from => $to) {
                debug($from, $to);
            }

            // for ($n = $destinationNode; $n != $startNode; $n = $this->shortestPathSegments->getPreviousNode($n)) {
            //     debug($n);
            // }
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
            if (!$this->pathsFrom($n)) {
                throw new \InvalidArgumentException("No paths from node $n");
            }
            // $paths = $this->pathsFrom($n);
            // assert($paths != null);
            return $this->pathsFrom($n)->keys();
        }
    }

    trait CurrentNode
    {
        function setTentativeDistancesOfNeighbors() {
            $unvisitedNeighbors = $this->unvisitedNeighbors();
            foreach ($unvisitedNeighbors as $neighbor) {
                $this->context->tentativeDistances->setDistanceTo(
                    $neighbor,
                    $this->distanceTo($neighbor)
                );
            }
        }

        function unvisitedNeighbors() {
            // if (!$neighborData) {
            //     return [];
            // }

            return array_filter($this->context->graph->neighborsOf($this->self), function($neighbor) {
                return $this->context->unvisitedNodes->has($neighbor);
            });
        }

        function unvisitedNeighborWithShortestPath() {
            $unvisitedNeighbors = $this->unvisitedNeighbors();
            // debug('unvisitedNeighbors', print_r($unvisitedNeighbors, true));
            $tentativeDistances = $this->context->tentativeDistances;

            $closestNeighbor = array_reduce(
                $unvisitedNeighbors,
                function($x, $y) use ($tentativeDistances) {
                    return $tentativeDistances->distanceTo($x) < $tentativeDistances->distanceTo($y)
                        ? $x
                        : $y;
                },
                $unvisitedNeighbors[0]
            );
            
            // $this->context->unvisitedNodes->markVisited($this->self);
            return $closestNeighbor;
        }

        // function unvisitedNeighbors() {
        //     return $this->context->unvisitedNodes->neighbors($this->self);
        // }

        function distanceTo(Node $neighbor) {
            return $this->context->graph->distanceBetweenNodes($this->self, $neighbor);
        }
    }

    trait UnvisitedNodes
    {
        function markVisited(Node $n) {
            $this->remove($n);
        }

        function has(Node $n) {
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