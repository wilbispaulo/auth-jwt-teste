<?php

namespace app\controllers;

use app\models\User;
use core\library\Auth;
use core\library\OAuth;
use core\library\Request;
use core\library\Response;

class AuthController
{
    public function index()
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
        if ($authVerify < 3) {
            $result = [
                'password' => 'FAIL'
            ];
        } else if ($authVerify < 1) {
            $result = [
                'username' => 'NOT_FOUND'
            ];
        } else {
            $credentials = $oAuth->genCredentials($authUser->getUserid());
            if (!$credentials) {
                $result = [
                    'username' => $authUser->getUserid(),
                    'auth' => 'FAIL_IN_DB',
                ];
            } else {
                $result = [
                    'username' => $authUser->getUserid(),
                    'auth' => 'OK',
                ];
                $result = array_merge($result, $credentials);
            }
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
