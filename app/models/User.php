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
