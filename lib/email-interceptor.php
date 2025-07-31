<?php

namespace RunthingsWCOrderDepartments;

use RunthingsWCOrderDepartments\Utils\DepartmentMatcher;

if (!defined('WPINC')) {
    die;
}

class EmailInterceptor
{
    private $department_matcher;

    /**
     * List of customer-facing email IDs that should have reply-to modified
     */
    private $customer_email_ids = [
        'customer_completed_order',
        'customer_cancelled_order',
        'customer_failed_order',
        'customer_on_hold_order',
        'customer_invoice',
        'customer_note',
        'customer_refunded_order',
        'customer_processing_order',
        'customer_new_account',
        'customer_reset_password'
    ];

    /**
     * List of admin-facing email IDs that should have recipients modified
     */
    private $admin_email_ids = [
        'new_order',
        'cancelled_order',
        'failed_order',
        'backorder'
    ];

    public function __construct($taxonomy = 'order_department')
    {
        $this->department_matcher = new DepartmentMatcher($taxonomy);

        // Apply filters to allow customization of email ID arrays
        $this->customer_email_ids = apply_filters('runthings_wc_order_departments_customer_email_ids', $this->customer_email_ids);
        $this->admin_email_ids = apply_filters('runthings_wc_order_departments_admin_email_ids', $this->admin_email_ids);

        // Hook into WooCommerce email recipient filters for admin notifications
        foreach ($this->admin_email_ids as $email_id) {
            add_filter("woocommerce_email_recipient_{$email_id}", [$this, 'modify_admin_email_recipient'], 10, 2);
        }

        // Hook into WooCommerce email headers to modify reply-to for customer emails
        add_filter('woocommerce_email_headers', [$this, 'modify_customer_email_headers'], 10, 4);
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

        // Get department recipients using the utility class
        $department_emails = $this->department_matcher->get_department_emails($order);

        // If department emails were found, use them instead
        if (!empty($department_emails)) {
            return $department_emails;
        }

        // Otherwise return the original recipient
        return $recipient;
    }

    /**
     * Modify email headers to set reply-to for customer emails based on order department
     *
     * @param string $headers Default email headers
     * @param string $email_id Email ID (e.g., 'customer_completed_order')
     * @param object $object Order object or other email object
     * @param \WC_Email $email Email instance
     * @return string Modified email headers
     */
    public function modify_customer_email_headers($headers, $email_id, $object, $email = null)
    {
        // Only process customer-facing emails
        if (!in_array($email_id, $this->customer_email_ids)) {
            return $headers;
        }

        // Make sure we have a valid order
        if (!$object instanceof \WC_Order) {
            return $headers;
        }

        // Get department emails using the utility class
        $department_emails = $this->department_matcher->get_department_emails($object);

        // If department emails were found, set them as reply-to
        if (!empty($department_emails)) {
            // Remove any existing Reply-to header
            $headers = preg_replace('/Reply-to:.*?\r\n/i', '', $headers);

            // Add new Reply-to header with department emails
            $headers .= 'Reply-to: ' . $department_emails . "\r\n";
        }

        return $headers;
    }
}