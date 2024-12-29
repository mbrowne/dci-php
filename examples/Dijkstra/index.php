<?php
/**
 * This example implements the Dijkstra algorithm to find the shortest path between
 * two nodes in a graph.
 */

ini_set('display_errors', 1);
set_include_path(__DIR__ . '/../../DCI' . ':' . __DIR__ . '/data-objects');

require('Role.php');
require('RolePlayer.php');
require('RolePlayerInterface.php');
require('CollectionRole.php');
require('Context.php');
require('Exception.php');

require('Edge.php');
require('Graph.php');
require('Node.php');
require('ObjectMap.php');
require('ObjectSet.php');
require('CalculateShortestPath.php');

function debug(...$messages) {
    echo implode(' ', $messages) . "\n";
}

$nodeIds = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i'];
foreach ($nodeIds as $nodeId) {
    $$nodeId = new \DataObjects\Node($nodeId);
}

/**
 * A fictional section of the street grid of Manhattan.
 * Avenues run east to west and are longer than streets, which run north to south.
 * 
 * The numbers (weights) of the graph represent traffic that makes that path slower.
 * 
 *    a - 3 - b - 4 - c
 *    |       |       |
 *    1       2       1
 *    |       |       |
 *    d - 3 - e - 3 - f
 *    |               |
 *    2               3
 *    |               |
 *    g - 3 - h - 4 - i
 */

$edgeData = [
    [$a, $b, 3],
    [$a, $d, 1],
    [$b, $c, 4],
    [$b, $e, 2],
    [$c, $f, 1],
    [$d, $e, 3],
    [$d, $g, 2],
    [$e, $f, 3],
    [$f, $i, 4],
    [$g, $h, 3],
    [$h, $i, 4],
];

$edges = [];
foreach ($edgeData as $edge) {
    $edges[] = new \DataObjects\Edge(...$edge);
}
$graph = new \DataObjects\Graph(...$edges);

$context = new UseCases\CalculateShortestPath($graph, $a, $i);
$shortestPath = $context->shortestPath();

echo "Shortest path:\n";
echo implode(' -> ', $shortestPath) . "\n";
