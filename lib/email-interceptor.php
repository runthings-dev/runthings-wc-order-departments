<?php

namespace RunthingsWCOrderDepartments;

use RunthingsWCOrderDepartments\Utils\DepartmentMatcher;
use RunthingsWCOrderDepartments\Email\CustomerEmailInterceptor;
use RunthingsWCOrderDepartments\Email\AdminEmailInterceptor;

if (!defined('WPINC')) {
    die;
}

// Include email interceptor classes
require_once RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR . 'lib/email/customer-email-interceptor.php';
require_once RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR . 'lib/email/admin-email-interceptor.php';

/**
 * Manager class that coordinates customer and admin email interception
 */
class EmailInterceptor
{
    private $department_matcher;

    private $settings;

    private $customer_interceptor;

    private $admin_interceptor;

    public function __construct($taxonomy = 'order_department', $settings = null)
    {
        $this->department_matcher = new DepartmentMatcher($taxonomy);
        $this->settings = $settings;

        // Initialize specialized interceptors
        $this->customer_interceptor = new CustomerEmailInterceptor($this->department_matcher, $this->settings);
        $this->admin_interceptor = new AdminEmailInterceptor($this->department_matcher);
    }
}