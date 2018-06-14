<?php
/**
 * Created by PhpStorm.
 * User: thuyenlv
 * Date: 6/7/18
 * Time: 3:52 PM
 */

namespace CodeBase;


class User
{
    public $code;
    public $name;
    public $division_code;

    function __construct($data)
    {
        $this->code = $data['code'];
        $this->name = $data['name'];
        $this->division_code = $data['division_code'];
    }
}