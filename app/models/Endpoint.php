<?php

namespace app\models;

use core\library\Model;

class Endpoint extends Model
{
    public function __construct()
    {
        $this->table = 'ap_endpoint';
    }
}
