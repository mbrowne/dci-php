<?php
namespace DataObjects;

class Edge
{
    private Node $from;
    private Node $to;
    private int $distance;

    public function __construct(Node $from, Node $to, float $distance)
    {
        $this->from = $from;
        $this->to = $to;
        $this->distance = $distance;
    }

    public function from(): Node {
        return $this->from;
    }
    
    public function to(): Node {
        return $this->to;
    }

    public function distance(): float {
        return $this->distance;
    }
}
