<?php

namespace app\models;

use core\library\Model;
use AllowDynamicProperties;

#[AllowDynamicProperties]
class User extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'ap_usuario';
    }
}
