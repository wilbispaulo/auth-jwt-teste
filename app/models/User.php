<?php

namespace app\models;

use core\library\Model;
use core\library\Session;
use AllowDynamicProperties;

#[AllowDynamicProperties]
class User extends Model
{
    public function __construct()
    {
        $this->table = 'ap_usuario';
        $this->setDBAttributes([
            'host' => $_ENV['DB_HOST'],
            'port' => $_ENV['DB_PORT'],
            'dbname' => $_ENV['DB_NAME'],
            'username' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'],
        ]);
    }

    public function getUsername()
    {
        return $this->findBy('idusuario', self::getUserid())[0]['apelido'];
    }

    public static function getUserid(): int
    {
        $userid = Session::getUserCookie($_ENV['USER_COOKIE']);

        return ($userid === false) ? false : $userid['USERCOD'];
    }

    public function setUserObj()
    {
        return $this->findByObj('idusuario', self::getUserid());
    }
}
