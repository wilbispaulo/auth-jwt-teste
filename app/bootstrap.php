<?php

use Dotenv\Dotenv;
use core\library\Router;

require './vendor/autoload.php';

Router::cors();

$dotenv = Dotenv::createImmutable('./core/');
$dotenv->load();

$router = new Router;
