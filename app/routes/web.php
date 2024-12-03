<?php

use App\controllers\AuthController;
use App\controllers\TokenController;

$router->add('POST', '/auth', [AuthController::class, 'index']);
$router->add('POST', '/token', [TokenController::class, 'index']);
