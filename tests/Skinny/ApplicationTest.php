<?php

namespace Skinny;

use Mockery;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
   public function testGetWithMatchingRouteReturnsOutput()
   {
      $application = new Application();
      $application->get('/hello/{:name}', function($name) {
         echo "Hello, {$name}!";
      });

      $response = Mockery::mock('\React\Http\Response');
      $response->shouldReceive('writeHead')
               ->once()
               ->with(200, [ 
                  'Connection' => 'close',
                  'Content-Length' => 11,
                  'Content-Type' => 'text/html',
               ]);
      $response->shouldReceive('end')
               ->once()
               ->with('Hello, foo!');

      $request = Mockery::mock('\React\Http\Response', [
         'getPath' => '/hello/foo',
         'getMethod' => 'GET',
      ]);

      $application($request, $response);
   }

   public function testGetWithoutMatchingRouteReturnsNotFound()
   {
      $application = new Application();
      $application->get('/hello/{:name}', function($name) {
         echo "Hello, {$name}!";
      });

      $response = Mockery::mock('\React\Http\Response');
      $response->shouldReceive('writeHead')
               ->once()
               ->with(404, [ 
                  'Connection' => 'close',
                  'Content-Length' => 15,
                  'Content-Type' => 'text/html',
               ]);
      $response->shouldReceive('end')
               ->once()
               ->with('Page not found.');

      $request = Mockery::mock('\React\Http\Response', [
         'getPath' => '/goodbye/foo',
         'getMethod' => 'GET',
      ]);

      $application($request, $response);
   }
}
