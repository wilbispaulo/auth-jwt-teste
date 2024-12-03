<?php

use Dotenv\Dotenv;
use Core\library\Router;

require './vendor/autoload.php';

Router::cors();

$dotenv = Dotenv::createImmutable('./core/');
$dotenv->load();

$router = new Router;
