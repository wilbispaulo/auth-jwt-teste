<?php

namespace app\controllers;

use core\library\OAuth;
use core\library\Request;
use core\library\Response;

class TokenController
{
    public function token()
    {
        $request = Request::create();
        $data = $request->getAll();
        $oAuth = new OAuth();

        if (isset($data['PHP_AUTH_USER']) && isset($data['PHP_AUTH_PW'])) {
            $oAuth->setCredentials($data['PHP_AUTH_USER'], $data['PHP_AUTH_PW']);
        } else if (isset($data['CLIENT_ID']) && isset($data['CLIENT_SECRET'])) {
            $oAuth->setCredentials($data['CLIENT_ID'], $data['CLIENT_SECRET']);
        } else {
            return new Response(
                [
                    'validation' => 'CREDENTIALS_NOT_SET'
                ],
                400,
                [
                    'Content-Type' => 'application/json'
                ]
            );
        }
        return $oAuth->tokenJWT();
    }

    public function authEndPoint($endPoint)
    {
        $result = [];
        $oAuth = new OAuth();
        $response = $oAuth->checkOAuth();
        if (in_array('OK', $response)) {
            $result['access'] = in_array($endPoint, $oAuth->getClaims()) ? 'OK' : 'DENIED';
        } else {
            $result = $response;
        }
        return new Response(
            $result,
            in_array('OK', $result) ? 200 : 403,
            [
                'Content-Type' => 'application/json'
            ]
        );
    }
}
