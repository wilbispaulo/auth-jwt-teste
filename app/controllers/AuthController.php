<?php

namespace app\controllers;

use app\models\User;
use core\library\Auth;
use core\library\OAuth;
use core\library\Request;
use core\library\Response;

class AuthController
{
    public function credentials()
    {
        $request = Request::create();
        $data = $request->getAll();

        $oAuth = new OAuth();
        $authUser = new Auth(new User);
        if (isset($data['PHP_AUTH_USER']) && isset($data['PHP_AUTH_PW'])) {
            $authUser->setCredentials($data['PHP_AUTH_USER'], $data['PHP_AUTH_PW'], 'email');
        } else if (isset($data['username']) && isset($data['password'])) {
            $authUser->setCredentials($data['username'], $data['password'], 'email');
        } else {
            $result['validation'] = 'USERNAME_PASSWORD_NOT_FOUND';
            return new Response(
                $result,
                200,
                [
                    'Content-Type' => 'application/json'
                ]
            );
        }

        $authVerify = $authUser->Auth();
        if (in_array('OK', $authVerify)) {
            if (!array_key_exists('client', $data)) {
                return new Response(
                    [
                        "status" => "CLIENT MISSING"
                    ],
                    400,
                    [
                        'Content-Type' => 'application/json'
                    ]
                );
            }

            return $oAuth->genCredentials($data['client']);
        } else {
            return new Response(
                $authVerify,
                403,
                [
                    'Content-Type' => 'application/json'
                ]
            );
        }
    }

    public function claims()
    {
        $request = Request::create();
        $data = $request->getAll();

        $oAuth = new OAuth();
        $authUser = new Auth(new User);
        if (isset($data['PHP_AUTH_USER']) && isset($data['PHP_AUTH_PW'])) {
            $authUser->setCredentials($data['PHP_AUTH_USER'], $data['PHP_AUTH_PW'], 'email');
        } else if (isset($data['username']) && isset($data['password'])) {
            $authUser->setCredentials($data['username'], $data['password'], 'email');
        } else {
            $result['validation'] = 'USERNAME_PASSWORD_NOT_FOUND';
            return new Response(
                $result,
                200,
                [
                    'Content-Type' => 'application/json'
                ]
            );
        }

        $authVerify = $authUser->Auth();
        if (in_array('OK', $authVerify)) {
            if (!array_key_exists('clientid', $data)) {
                return new Response(
                    [
                        "status" => "CLIENTID MISSING"
                    ],
                    400,
                    [
                        'Content-Type' => 'application/json'
                    ]
                );
            }
            if (!array_key_exists('claims', $data)) {
                return new Response(
                    [
                        "status" => "CLAIMS MISSING"
                    ],
                    400,
                    [
                        'Content-Type' => 'application/json'
                    ]
                );
            }
            if (!$oAuth->clientIdVerify($data['clientid'])) {
                return new Response(
                    [
                        "status" => "CLIENTID NOT FOUND"
                    ],
                    400,
                    [
                        'Content-Type' => 'application/json'
                    ]
                );
            }

            $result = $oAuth->setClaimsDB($data['clientid'], $data['claims']);
            return new Response(
                $result,
                in_array('OK', $result) ? 200 : 400,
                [
                    'Content-Type' => 'application/json'
                ]
            );
        } else {
            return new Response(
                $authVerify,
                403,
                [
                    'Content-Type' => 'application/json'
                ]
            );
        }
    }
}
