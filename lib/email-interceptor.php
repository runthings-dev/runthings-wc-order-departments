<?php

namespace RunthingsWCOrderDepartments;

use RunthingsWCOrderDepartments\Utils\DepartmentMatcher;

if (!defined('WPINC')) {
    die;
}

class EmailInterceptor
{
    private $department_matcher;

    private $settings;

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

    public function __construct($taxonomy = 'order_department', $settings = null)
    {
        $this->department_matcher = new DepartmentMatcher($taxonomy);
        $this->settings = $settings;

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

        // Get unique department email addresses
        $unique_emails = $this->department_matcher->get_unique_department_emails($order);

        // If department emails were found, use them instead (format as CSV for admin emails)
        if (!empty($unique_emails)) {
            return implode(', ', $unique_emails);
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
        // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by WordPress filter signature

        // Only process customer-facing emails
        if (!in_array($email_id, $this->customer_email_ids)) {
            return $headers;
        }

        // Make sure we have a valid order
        if (!$object instanceof \WC_Order) {
            return $headers;
        }

        // Check if reply-to override is enabled
        if (!$this->is_reply_to_override_enabled()) {
            return $headers;
        }

        // Get unique department email addresses for this order
        $unique_emails = $this->department_matcher->get_unique_department_emails($object);

        // If no department emails found, return original headers
        if (empty($unique_emails)) {
            return $headers;
        }

        // Determine reply-to behavior based on number of unique emails and settings
        $reply_to_emails = $this->determine_reply_to_emails($unique_emails);

        // If we have reply-to emails to set, modify the headers
        if (!empty($reply_to_emails)) {
            // Normalize headers into array and remove any existing Reply-To headers
            $lines = preg_split('/\r\n|\n|\r/', $headers);
            $lines = array_filter($lines, fn($line) => stripos($line, 'Reply-To:') !== 0);

            // Add new Reply-To header
            $lines[] = 'Reply-To: ' . $reply_to_emails;

            // Rebuild headers with consistent line endings
            $headers = implode("\r\n", $lines) . "\r\n";
        }

        return $headers;
    }

    /**
     * Check if reply-to override is enabled in settings
     *
     * @return bool
     */
    private function is_reply_to_override_enabled()
    {
        // If no settings instance, assume enabled for backward compatibility
        if (!$this->settings) {
            return true;
        }

        $settings = $this->settings->get_settings();
        return !empty($settings['enable_reply_to_override']);
    }

    /**
     * Determine which reply-to emails to use based on settings and email count
     *
     * @param array $unique_emails Array of unique email addresses
     * @return string Email addresses for reply-to header (comma-separated) or empty string
     */
    private function determine_reply_to_emails($unique_emails)
    {
        // If only one unique email, always use it
        if (count($unique_emails) === 1) {
            return $unique_emails[0];
        }

        // Multiple unique emails - check settings for behavior
        if (!$this->settings) {
            // No settings instance - use all emails for backward compatibility
            return implode(', ', $unique_emails);
        }

        $settings = $this->settings->get_settings();
        $multi_dept_mode = $settings['multi_dept_mode'];

        if ($multi_dept_mode === 'use_all_emails') {
            // Use all unique department emails
            return implode(', ', $unique_emails);
        } else {
            // Fall back to WooCommerce default (return empty to let WC handle it)
            return '';
        }
    }
}