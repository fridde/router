<?php

namespace Fridde;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\Request;

class Router
{
    public $request_context;


    private $url_generator;
    private $route_collection;

    public function __construct(array $routes = [])
    {
        $this->request_context = new RequestContext();
        $this->request_context->fromRequest(Request::createFromGlobals());
        $this->route_collection = new RouteCollection();



    }

    public function addAllRoutes(array $routes)
    {
        foreach($routes as $name => $route_values){
            $method = $route_values[0];
            $path = $route_values[1];


            $route = new Route($path, $route_values);
        }
    }


}
