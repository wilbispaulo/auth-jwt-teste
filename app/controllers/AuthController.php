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

        $oAuth = new OAuth(null, $_ENV['PRIVATE_PEM']);
        $authUser = new Auth(new User);
        if (isset($data['PHP_AUTH_USER']) && isset($data['PHP_AUTH_PW'])) {
            $authUser->setCredentials($data['PHP_AUTH_USER'], $data['PHP_AUTH_PW'], 'email');
            $authVerify = $authUser->Auth();
            if ($authVerify < 3) {
                $data = [
                    'password' => 'FAIL'
                ];
            } else if ($authVerify < 1) {
                $data = [
                    'username' => 'NOT_FOUND'
                ];
            } else {
                $credentials = $oAuth->genCredentials();
                if (!$credentials) {
                    $data = [
                        'username' => $data['PHP_AUTH_USER'],
                        'auth' => 'FAIL_IN_DB',
                    ];
                } else {
                    $data = [
                        'username' => $data['PHP_AUTH_USER'],
                        'auth' => 'OK',
                    ];
                    $data = array_merge($data, $credentials);
                }
            }
        } else {
            $data = [
                'auth' => 'USERNAME_PASSWORD_NOT_FOUND'
            ];
        }
        return new Response(
            $data,
            200,
            [
                'Content-Type' => 'application/json'
            ]
        );
    }
}
