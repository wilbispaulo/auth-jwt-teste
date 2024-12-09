<?php

namespace app\controllers;

use core\library\OAuth;
use core\library\Request;
use core\library\Response;

class TokenController
{
    public function index()
    {
        $request = Request::create();
        $data = $request->getAll();
        $oAuth = new OAuth();
        if (isset($data['claims'])) {
            $oAuth->setClaims($data['claims']);
        }
        if (isset($data['PHP_AUTH_USER']) && isset($data['PHP_AUTH_PW'])) {
            $oAuth->setCredentials($data['PHP_AUTH_USER'], $data['PHP_AUTH_PW']);
        } else if (isset($data['CLIENT_ID']) && isset($data['CLIENT_SECRET'])) {
            $oAuth->setCredentials($data['CLIENT_ID'], $data['CLIENT_SECRET']);
        } else {
            return new Response(
                [
                    'validation' => 'CREDENTIALS_NOT_SET'
                ],
                200,
                [
                    'Content-Type' => 'application/json'
                ]
            );
        }
        return $oAuth->tokenJWT();
    }
}
