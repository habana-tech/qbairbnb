<?php


namespace App\Http\Controllers;

class TestController
{
    public function show($name)
    {
        dump($name);
        return "Hello $name";
    }
}
