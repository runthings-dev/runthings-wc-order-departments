<?php

namespace RunthingsWCOrderDepartments;

use RunthingsWCOrderDepartments\Utils\DepartmentMatcher;

if (!defined('WPINC')) {
    die;
}

class EmailInterceptor
{
    private $department_matcher;

    public function __construct($taxonomy = 'order_department')
    {
        $this->department_matcher = new DepartmentMatcher($taxonomy);

        // Hook into WooCommerce email filters to modify recipients for admin notifications
        add_filter('woocommerce_email_recipient_new_order', [$this, 'modify_email_recipient'], 10, 2);
        add_filter('woocommerce_email_recipient_cancelled_order', [$this, 'modify_email_recipient'], 10, 2);
        add_filter('woocommerce_email_recipient_failed_order', [$this, 'modify_email_recipient'], 10, 2);
    }
    
    /**
     * Modify email recipients based on order department
     *
     * @param string $recipient Default recipient email(s)
     * @param \WC_Order $order Order object
     * @return string Modified recipient email(s)
     */
    public function modify_email_recipient($recipient, $order)
    {
        // Make sure we have a valid order
        if (!$order instanceof \WC_Order) {
            return $recipient;
        }

        // Get department recipients using the utility class
        $department_emails = $this->department_matcher->get_department_emails($order);

        // If department emails were found, use them instead
        if (!empty($department_emails)) {
            return $department_emails;
        }

        // Otherwise return the original recipient
        return $recipient;
    }
}