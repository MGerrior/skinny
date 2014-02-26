<?php

namespace Skinny;

use Aura\Router\Map;
use Aura\Router\DefinitionFactory;
use Aura\Router\RouteFactory;

class Application
{ 
   private $http;
   private $loop;
   private $router;
   private $socket;

   public function __construct()
   {
      $this->loop = \React\EventLoop\Factory::create();
      $this->socket = new \React\Socket\Server($this->loop);
      $this->http = new \React\Http\Server($this->socket, $this->loop);
      $this->router = new Map(new DefinitionFactory, new RouteFactory);

      $this->http->on('request', $this);
   }

   public function __invoke($request, $response)
   {
      $this->dispatchRoute($request, $response);
   }

   public function get($path, callable $callback)
   {
      $this->router->add(null, $path, array(
         'values' => array(
            'controller' => $callback,
         ),
         'method' => array('GET')
      ));
   }

   public function run()
   {
      $this->socket->listen(1337);
      $this->loop->run();
   }

   private function dispatchRoute($request, $response)
   {
      $route = $this->lookupRoute($request);

      if (! $route) {
         return $this->writeRouteNotFoundResponse($response);
      }

      $this->writeRouteResponse($route, $response);
   }

   private function lookupRoute($request)
   {
      return $this->router->match(
         $request->getPath(),
         array_merge($_SERVER, ['REQUEST_METHOD' => $request->getMethod()])
      );
   }

   private function writeRouteNotFoundResponse($response)
   {
      $message = 'Page not found.';

      $response->writeHead(404, [
         'Connection' => 'close',
         'Content-Length' => strlen($message),
         'Content-Type' => 'text/html',
      ]);
      $response->end($message);
   }

   private function writeRouteResponse($route, $response)
   {
      $body = $this->captureOutput(function () use ($route) {
         $this->executeRoute($route);
      });
      $this->writeSuccessfulResponse($response, $body);
   }

   private function startOutputBuffering()
   {
      ob_start();
   }

   private function captureOutput($callable)
   {
      $this->startOutputBuffering();

      $callable();

      $output = $this->getOutputBufferContents();
      $this->stopOutputBuffering();

      return $output;
   }

   private function executeRoute($route)
   {
      $params = $route->values;
      $controller = $params['controller'];

      unset($params['controller']);

      call_user_func_array($controller, $params);
   }

   private function getOutputBufferContents()
   {
      return ob_get_contents();
   }

   private function stopOutputBuffering()
   {
      ob_end_clean();
   }

   private function writeSuccessfulResponse($response, $body)
   {
      $response->writeHead(200, [
         'Connection' => 'close',
         'Content-Length' => strlen($body),
         'Content-Type' => 'text/html',
      ]);
      $response->end($body);
   }
}
