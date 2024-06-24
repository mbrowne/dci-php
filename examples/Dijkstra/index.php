<?php
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
require('CalculateShortestPath.php');

function debug(...$messages) {
    echo implode(' ', $messages) . "\n";
}

$nodeIds = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i'];
foreach ($nodeIds as $nodeId) {
    $$nodeId = new \DataObjects\Node($nodeId);
}

$edgeData = [
    [$a, $b, 2],  //2
    [$a, $d, 1],  //1
    [$b, $c, 3],
    [$b, $e, 2],
    [$c, $f, 1],
    [$d, $e, 1],  //1+1
    [$d, $g, 2],  //1+2
    [$e, $f, 1],  //2+1
    [$f, $i, 4],  
    [$g, $h, 1],  //2+1
    [$h, $i, 2],
];

$edges = [];
foreach ($edgeData as $edge) {
    $edges[] = new \DataObjects\Edge(...$edge);
}
$graph = new \DataObjects\Graph(...$edges);

$context = new UseCases\CalculateShortestPath($graph);
$context->calculate($a, $i);
