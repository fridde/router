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

    public function __construct(string $base_path, array $routes = [], string $controller_namespace = '\\', array $route_defaults = [])
    {
        $this->request = Request::createFromGlobals();
        $this->request_context = new RequestContext($base_path);
        $this->request_context->fromRequest($this->request);
        $this->controller_namespace = $controller_namespace;

        $this->route_collection = new RouteCollection();

        $this->addAllRoutes($routes, $route_defaults);
        $this->url_matcher = new UrlMatcher($this->route_collection, $this->request_context);
    }

    public function addAllRoutes(array $routes, array $defaults = []): void
    {
        $arg_order = ['path','defaults','requirements','options','host','schemes','methods','condition'];

        $base_defaults = [
            'options' => [],
            'host' => null,
            'schemes' => ['https'],
            'condition' => null
        ];

        $args = $defaults + $base_defaults;

        foreach($routes as $name => $route_values){
            $args['path'] = $route_values[1];

            $args['defaults'] = [];
            $args['defaults']['_controller'] = $this->createControllerName($route_values[2]);
            $args['defaults']['_method'] = $route_values[3] ?? null;
            $args['requirements'] = [];

            $defaults = (array) ($route_values[4] ?? []);
            array_walk($defaults, function($val, $key) use (&$args){
                if(is_int($key)){
                    $args['defaults'][$val] = null;
                } else {
                    $args['defaults'][$key] = null;
                    $args['requirements'][$key] = $val;
                }
            });

            $args['methods'] = explode('|', $route_values[0]);

            // reordering the array
            $ordered_args = array_map(function($i) use ($args){
                return $args[$i];
            }, $arg_order);

            $route = new Route(...$ordered_args);

            $this->route_collection->add($name, $route);
        }
    }

    public function match(string $url = null): array
    {
        $url = $url ?? $this->request->getPathInfo();
        $p = $this->url_matcher->match($url);

        $args = array_diff_key($p, array_flip(['_controller', '_method']));

        return [$p['_controller'], $p['_method'], $args];
    }

    public function generate(string $route_name, array $args = [], $absolute = true): string
    {
        $args = [$route_name, $args];

        if($absolute){
            $args[] = UrlGenerator::ABSOLUTE_URL;
        }

        return $this->getUrlGenerator()->generate(...$args);
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
