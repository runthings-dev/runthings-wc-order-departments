<?php

namespace RunthingsWCOrderDepartments\Email;

use RunthingsWCOrderDepartments\Utils\DepartmentMatcher;

if (!defined('WPINC')) {
    die;
}

/**
 * Handles admin email recipient modification based on order departments
 */
class AdminEmailInterceptor
{
    private $department_matcher;

    /**
     * List of admin-facing email IDs that should have recipients modified
     */
    private $admin_email_ids = [
        'new_order',
        'cancelled_order',
        'failed_order',
        'backorder'
    ];

    public function __construct($department_matcher)
    {
        $this->department_matcher = $department_matcher;

        // Apply filters to allow customization of email ID arrays
        $this->admin_email_ids = apply_filters('runthings_wc_order_departments_admin_email_ids', $this->admin_email_ids);

        // Hook into WooCommerce email recipient filters for admin notifications
        foreach ($this->admin_email_ids as $email_id) {
            add_filter("woocommerce_email_recipient_{$email_id}", [$this, 'modify_admin_email_recipient'], 10, 2);
        }
    }
    
    /**
     * Modify admin email recipients based on order department
     *
     * @param string $recipient Default recipient email(s)
     * @param \WC_Order $order Order object
     * @return string Modified recipient email(s)
     */
    public function modify_admin_email_recipient($recipient, $order)
    {
        // Make sure we have a valid order
        if (!$order instanceof \WC_Order) {
            return $recipient;
        }

        // Get unique department email addresses
        $unique_emails = $this->department_matcher->get_unique_department_emails($order);

        // If department emails were found, use them instead (format as CSV for admin emails)
        if (!empty($unique_emails)) {
            return implode(', ', $unique_emails);
        }

        // Otherwise return the original recipient
        return $recipient;
    }
}
