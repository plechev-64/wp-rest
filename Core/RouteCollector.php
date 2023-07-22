<?php

namespace Core\Rest;

class RouteCollector
{

    private array $routes = [];
    private string $currentGroup = '';

    public function __construct()
    {
    }

    public function addGroup(string $groupPath, callable $callback): RouteCollector
    {
        $prevCurrentGroup = $this->currentGroup;
        $this->currentGroup = $groupPath;
        $callback($this);
        $this->currentGroup = $prevCurrentGroup;

        return $this;
    }

    public function addRoute(string $path, RouteData $routeData): RouteCollector
    {
        $this->routes[] = $routeData->setPath($this->currentGroup . $path);

        return $this;
    }

    public function all(): \Generator
    {
        foreach ($this->routes as $data) {
            yield $data;
        }
    }
}
