<?php

namespace app\models;

use core\library\Model;

class Credential extends Model
{
    public function __construct()
    {
        $this->table = 'ap_credenciais';
    }
}
