<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;

class IndexController extends Controller
{
    public function getIndex()
    {
        $client = new Client();
        $request = $client->get('https://www.youtube.com/',['proxy'=>'socks5://127.0.0.1:1086']);
        echo $request->getBody()->getContents();
        exit;
        return view('admin::index.index');
    }
}
