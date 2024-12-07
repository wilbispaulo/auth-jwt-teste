<?php

namespace app\models;

use core\library\Model;
use AllowDynamicProperties;

#[AllowDynamicProperties]
class Grant extends Model
{
    public function __construct()
    {
        $this->table = 'ap_permissao';
    }
}
