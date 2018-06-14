<?php

namespace CodeBase;

/**
 * Constant for all API service. Belong to API codebase
 *
 * Class Constant
 * @package Website
 */
class CommonConstant
{
    const SUCCESS = 200;
    const UNAUTHORIZED = 401;
    const FORBIDDEN = 403;

    const INVALID_AUTH = [
        'code'    => 'INVALID_AUTH',
        'message' => "Thông tin đăng nhập không hợp lệ",
    ];
}
