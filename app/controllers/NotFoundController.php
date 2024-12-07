<?php

namespace app\controllers;

use core\library\Response;

class NotFoundController
{
    public function index()
    {
        return new Response(
            [
                'error' => 'NotFound'
            ],
            404,
            [
                'Content-Type' => 'application/json'
            ]
        );
    }
}
