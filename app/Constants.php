<?php

if (!defined('DEFAULT_EMPLOYEE_STATUS')) {
    define('DEFAULT_EMPLOYEE_STATUS', 1);
}

if (!defined('DEFAULT_ITEM_STATUS')) {
    define('DEFAULT_ITEM_STATUS', 1);
}

if (!defined('DEFAULT_PASSWORD')) {
    define('DEFAULT_PASSWORD', 'password');
}

if (!defined('DEFAULT_USERNAME_PREFIX')) {
    define('DEFAULT_USERNAME_PREFIX', 'TBMS');
}

if (!defined('DEFAULT_STATUSES')) {
    define('DEFAULT_STATUSES', [
        'active' => 1,
        'inactive' => 0,
        'deleted' => -1,
    ]);
}

if (!defined('ORDER_STATUSES')) {
    define('ORDER_STATUSES', [
        'delivered' => 1,
        'pending' => 0,
        'cancelled' => -1,
    ]);
}