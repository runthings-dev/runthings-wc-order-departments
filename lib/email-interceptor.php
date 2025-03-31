<?php

namespace RunthingsWCOrderDepartments;

if (!defined('WPINC')) {
    die;
}

class EmailInterceptor
{
    private $taxonomy;

    private $meta_prefix = 'runthings_wc_od_';
    
    public function __construct($taxonomy = 'order_department')
    {
        $this->taxonomy = $taxonomy;
        
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
        
        // Get department recipients
        $department_emails = $this->get_department_recipients($order);
        
        // If department emails were found, use them instead
        if (!empty($department_emails)) {
            return $department_emails;
        }
        
        // Otherwise return the original recipient
        return $recipient;
    }
    
    /**
     * Get department email addresses for an order
     *
     * @param \WC_Order $order Order object
     * @return string Email addresses separated by commas
     */
    private function get_department_recipients($order)
    {
        // Placeholder implementation
        // 1. Get the order ID
        $order_id = $order->get_id();
        
        // 2. Get department terms assigned to the order
        $departments = get_the_terms($order_id, $this->taxonomy);
        
        if (!$departments || is_wp_error($departments)) {
            return '';
        }
        
        // 3. Get email addresses for each department
        $all_emails = [];
        
        foreach ($departments as $department) {
            $emails = get_term_meta($department->term_id, $this->meta_prefix . 'department_emails', true);
            
            if (!empty($emails)) {
                // Split emails by semicolon and add to our list
                $email_array = array_map('trim', explode(';', $emails));
                $all_emails = array_merge($all_emails, $email_array);
            }
        }
        
        // 4. Return unique emails as a comma-separated string (WP's email format)
        return implode(', ', array_unique(array_filter($all_emails)));
    }
}