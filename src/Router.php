<?php

namespace Fridde;

use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\Request;

class Router
{
    public $request;
    public $request_context;

    private $url_matcher;
    private $url_generator;
    private $route_collection;
    private $controller_namespace;

    public function __construct(string $base_path, array $routes = [], string $controller_namespace = '\\')
    {
        $this->request = Request::createFromGlobals();
        $this->request_context = new RequestContext($base_path);
        $this->request_context->fromRequest($this->request);
        $this->controller_namespace = $controller_namespace;

        $this->route_collection = new RouteCollection();

        $this->addAllRoutes($routes);
        $this->url_matcher = new UrlMatcher($this->route_collection, $this->request_context);
    }

    public function addAllRoutes(array $routes): void
    {
        foreach($routes as $name => $route_values){
            $http_methods = explode('|', $route_values[0]);
            $path = $route_values[1];

            $defaults['_controller'] = $this->createControllerName($route_values[2]);
            $defaults['_method'] = $route_values[3] ?? null;

            $route = new Route($path, $defaults, [], [], [], [], $http_methods);

            $this->route_collection->add($route);
        }
    }

    public function match(string $url = null): array
    {
        $url = $url ?? $this->request->getPathInfo();
        $p = $this->url_matcher->match($url);

        $args = array_diff_key($p, array_flip(['_controller', '_method']));

        return [$p['_controller'], $p['_method'], $args];
    }

    public function generate(string $route_name, array $args = []): string
    {
        return $this->getUrlGenerator()->generate($route_name, $args);
    }

    private function getUrlGenerator(): UrlGenerator
    {
       return $this->url_generator ?? new UrlGenerator($this->route_collection, $this->request_context);
    }

    private function createControllerName(string $short_name, string $suffix = 'Controller'): string
    {
        $fqcn = $this->controller_namespace . '\\';
        $fqcn .= $short_name;
        $fqcn .= $suffix;

        return $fqcn;
    }




}
