<?php

namespace App\controllers;

use core\library\Response;

class MethodNotAllowedController
{
    public function index()
    {
        return new Response(
            [
                'error' => 'MethodNotAllowed'
            ],
            200,
            [
                'Content-Type' => 'application/json'
            ]
        );
    }
}
