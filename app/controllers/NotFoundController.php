<?php

namespace App\controllers;

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
