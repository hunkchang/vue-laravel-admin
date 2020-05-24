<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;

class IndexController extends Controller
{
    public function getIndex()
    {
        return view('admin::index.index');
    }
}
