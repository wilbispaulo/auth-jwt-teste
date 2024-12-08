<?php

use app\controllers\AuthController;
use app\controllers\TesteController;
use app\controllers\TokenController;

$router->add('POST', '/auth', [AuthController::class, 'index']);
$router->add('POST', '/token', [TokenController::class, 'index']);
$router->add('POST', '/teste/teste1', [TesteController::class, 'index']);
