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
            $result['validation'] = 'CREDENTIALS_NOT_SET';
            return new Response(
                $result,
                200,
                [
                    'Content-Type' => 'application/json'
                ]
            );
        }
        if ($oAuth->verifyCredentials()) {
            $result['validation'] = 'OK';
            $result['token'] = $oAuth->tokenJWS();
        } else {
            $result['validation'] = 'INVALID';
        }
        return new Response(
            $result,
            200,
            [
                'Content-Type' => 'application/json'
            ]
        );
    }
}
