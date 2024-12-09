<?php

namespace app\controllers;

use core\library\OAuth;
use core\library\Response;

class TesteController
{
    public function index()
    {
        $oAuth = new OAuth();
        $response = $oAuth->checkOAuth();
        return new Response(
            $response,
            200,
            [
                'Content-Type' => 'application/json'
            ]
        );
    }
}
