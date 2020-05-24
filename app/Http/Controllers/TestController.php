<?php

namespace App\Http\Controllers;

class TestController extends Controller
{
    public function getIndex()
    {
        \helper::cacheConfig();
    }
}
