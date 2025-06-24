<?php

if (!defined('ROLES')) {
    define('ROLES', [
        'super_admin' => 'super_admin',
        'admin' => 'admin',
        'branch' => 'branch',
        'employee' => 'employee'
    ]);
}

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

if (!defined('ORDER_PAYMENT_STATUSES')) {
    define('ORDER_PAYMENT_STATUSES', [
        'full_paid' => 2,
        'advance_paid' => 1,
        'unpaid' => 0,
        'refunded' => -1,
    ]);
}

if (!defined('AUDITLOG_ACTIONS')) {
    define('AUDITLOG_ACTIONS', [
        'CREATE' => 'Create',
        'UPDATE' => 'Update',
        'DELETE' => 'Delete',

        'IMPORT' => 'Import',

        'ENABLE' => 'Enable',
        'DISABLE' => 'Disable',

        'DELIVERED' => 'Delivered',
        'CANCEL' => 'Cancel',
        'COMPLETE' => 'Complete'
    ]);
}

if (!defined('EMAIL_TYPES')) {
    define('EMAIL_TYPES', [
        'ORDER_CONFIRMATION' => 'order_confirmation',
        'ORDER_NOTIFICATION_TO_BRANCH' => 'order_notification_to_branch',
        'ORDER_DELIVERY' => 'order_delivery',
        'ORDER_CANCELLATION' => 'order_cancellation',
        'STOCK_SUMMARY' => 'stock_summary',
        'PASSWORD_RESET_OTP' => 'password_reset_otp',
        'PASSWORD_RESET_SUCCESS' => 'password_reset_success'
    ]);
}
