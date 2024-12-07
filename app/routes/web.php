<?php

use app\controllers\AuthController;
use app\controllers\TokenController;

$router->add('POST', '/auth', [AuthController::class, 'index']);
$router->add('POST', '/token', [TokenController::class, 'index']);
