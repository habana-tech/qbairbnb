<?php

use Laminas\Diactoros\Response\HtmlResponse;
use Rareloop\Lumberjack\Facades\Router;
//use \Zend\Diactoros\Response\HtmlResponse;

 Router::get('hello-world', function () {
     return new HtmlResponse('<h1>Hello World!!</h1>');
 });

 Router::get('test/{name}/', 'TestController@show');
