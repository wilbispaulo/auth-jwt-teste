<?php

namespace app\controllers;

use app\models\User;
use core\library\Auth;
use core\library\Request;
use core\library\Response;

class TokenController
{
    public function index()
    {
        $request = Request::create();
        $data = $request->getAll();
        $authUser = new Auth(new User);
        if (isset($data['claims'])) {
            $authUser->setClaims($data['claims']);
        }
        if (isset($data['PHP_AUTH_USER']) && isset($data['PHP_AUTH_PW'])) {
            $authUser->setCredentials($data['PHP_AUTH_USER'], $data['PHP_AUTH_PW']);
            if ($authUser->verifyCredentials()) {
                $result['validation'] = 'OK';
                $result['private_key'] = $authUser->getPrivateKey();
            } else {
                $result['validation'] = 'INVALID';
            }
        } else if (isset($data['CLIENT_ID']) && isset($data['CLIENT_SECRET'])) {
            $authUser->setCredentials($data['CLIENT_ID'], $data['CLIENT_SECRET']);
            if ($authUser->verifyCredentials()) {
                $result['validation'] = 'OK';
                $result['token'] = $authUser->tokenJWS();

                $jws = $authUser->loadJWS($result['token']);

                $result['JWS'] = $jws;
            } else {
                $result['validation'] = 'INVALID';
            }
        } else {
            $result['validation'] = 'CREDENTIALS_NOT_SET';
        }
        return new Response(
            $result,
            200,
            [
                'Content-Type' => 'application/json'
            ]
        );
    }

    private function token() {}
}
