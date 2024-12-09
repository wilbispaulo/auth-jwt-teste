<?php

namespace app\models;

use core\library\Model;

class Credential extends Model
{
    public function __construct()
    {
        $this->table = 'ap_credenciais';
        $this->setDBAttributes([
            'host' => $_ENV['DB_HOST'],
            'port' => $_ENV['DB_PORT'],
            'dbname' => $_ENV['DB_NAME'],
            'username' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'],
        ]);
    }
}
