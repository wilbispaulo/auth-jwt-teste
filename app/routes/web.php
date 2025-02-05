<?php

use app\controllers\AuthController;
use app\controllers\TesteController;
use app\controllers\TokenController;

$router->add('POST', '/credentials', [AuthController::class, 'credentials']);
$router->add('POST', '/claims', [AuthController::class, 'claims']);
$router->add('POST', '/token', [TokenController::class, 'token']);
$router->add('GET', '/token/auth/{endPoint:[a-z0-9A-Z+\/=]+}', [TokenController::class, 'authEndPoint']);
