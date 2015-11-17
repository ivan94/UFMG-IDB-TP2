<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Curl\Curl;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    public function get(Request $request)
    {
        $path = $request->input('path');
        $curl = new Curl;
        $curl->get("https://api.github.com/$path", $request->all());
        dd(json_decode($curl->response));
    }
}
