<?php

namespace app\controllers;

use core\library\OAuth;
use core\library\Request;
use core\library\Response;

class TesteController
{
    public function index()
    {
        $request = Request::create();
        $data = $request->getAll();

        $token = OAuth::getBearerToken();

        $oAuth = new OAuth();
        $result = $oAuth->loadJWS($token);
        if (!in_array('INVALID', $result) && !in_array('EXPIRED', $result)) {
            return new Response(
                [
                    'response' => 'OK'
                ],
                200,
                [
                    'Content-Type' => 'application/json'
                ]
            );
        } else {
            return new Response(
                [
                    'response' => 'INVALID'
                ],
                200,
                [
                    'Content-Type' => 'application/json'
                ]
            );
        }
    }
}
