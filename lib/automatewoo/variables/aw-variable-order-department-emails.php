<?php

namespace RunthingsWCOrderDepartments\Variables;

use AutomateWoo\Variable;

if (!defined('WPINC')) {
    die;
}

/**
 * AutomateWoo Variable - Order Department Emails
 * 
 * Returns department email addresses with flexible access options:
 * - By index (1-based): emails from specific department
 * - All: emails from all departments with separator
 * - Count: number of departments with emails
 */
class Order_Department_Emails extends Variable
{
    /**
     * Load admin details
     */
    public function load_admin_details()
    {
        $this->description = __('Displays department email addresses with flexible access options. Use index for specific departments, "all" for all departments, or "count" for the number of departments.', 'runthings-wc-order-departments');
        
        $this->add_parameter_select_field(
            'mode',
            __('How to access the department emails.', 'runthings-wc-order-departments'),
            [
                'index' => __('By Index (default)', 'runthings-wc-order-departments'),
                'all'   => __('All Departments', 'runthings-wc-order-departments'),
                'count' => __('Count of Departments', 'runthings-wc-order-departments'),
            ],
            false
        );
        
        $this->add_parameter_text_field(
            'index',
            __('The index of the department to retrieve (1-based). Default is 1 (first department). Only used when mode is "index".', 'runthings-wc-order-departments'),
            false,
            '1'
        );
        
        $this->add_parameter_text_field(
            'separator',
            __('The separator to use between email addresses. Default is ", " (comma space). Use ";" for semicolon separation.', 'runthings-wc-order-departments'),
            false,
            ', '
        );
        
        $this->add_parameter_text_field(
            'prefix',
            __('Text to add before each email address.', 'runthings-wc-order-departments'),
            false,
            ''
        );
        
        $this->add_parameter_text_field(
            'suffix',
            __('Text to add after each email address.', 'runthings-wc-order-departments'),
            false,
            ''
        );
    }

    /**
     * Get the variable value
     *
     * @param \WC_Order $order
     * @param array     $parameters
     * @return string
     */
    public function get_value($order, $parameters)
    {
        if (!$order instanceof \WC_Order) {
            return '';
        }

        // Get department terms assigned to the order
        $department_terms = wp_get_object_terms($order->get_id(), 'order_department');
        
        if (empty($department_terms) || is_wp_error($department_terms)) {
            return '';
        }

        // Get mode from parameters, default to 'index'
        $mode = isset($parameters['mode']) ? $parameters['mode'] : 'index';
        $meta_prefix = 'runthings_wc_od_';
        
        // Handle count mode
        if ($mode === 'count') {
            return (string) count($department_terms);
        }
        
        // Get prefix/suffix
        $prefix = isset($parameters['prefix']) ? $parameters['prefix'] : '';
        $suffix = isset($parameters['suffix']) ? $parameters['suffix'] : '';
        
        // Handle all mode
        if ($mode === 'all') {
            $all_emails = [];
            
            foreach ($department_terms as $term) {
                $emails_raw = get_term_meta($term->term_id, $meta_prefix . 'department_emails', true);
                
                if (!empty($emails_raw)) {
                    // Process email addresses - handle different line endings
                    $emails_raw = str_replace(["\r\n", "\r", "\n"], ';', $emails_raw);
                    $emails_array = explode(';', $emails_raw);
                    
                    // Trim and filter each email address
                    foreach ($emails_array as $email) {
                        $email = trim($email);
                        if (!empty($email)) {
                            $all_emails[] = $email;
                        }
                    }
                }
            }
            
            // Remove duplicates and filter out empty values
            $all_emails = array_unique(array_filter($all_emails));
            
            // Apply prefix/suffix to each email
            if (!empty($prefix) || !empty($suffix)) {
                $all_emails = array_map(function($email) use ($prefix, $suffix) {
                    return $prefix . $email . $suffix;
                }, $all_emails);
            }
            
            $separator = isset($parameters['separator']) ? $parameters['separator'] : ', ';
            return implode($separator, $all_emails);
        }
        
        // Handle index mode (default)
        $index = isset($parameters['index']) ? (int) $parameters['index'] : 1;
        
        // Convert to 0-based index
        $zero_based_index = $index - 1;
        
        // Check if the requested index exists
        if (!isset($department_terms[$zero_based_index]) || $zero_based_index < 0) {
            return '';
        }

        // Get the department at the specified index
        $department = $department_terms[$zero_based_index];
        
        // Get email addresses for the department
        $emails_raw = get_term_meta($department->term_id, $meta_prefix . 'department_emails', true);
        
        if (empty($emails_raw)) {
            return '';
        }

        // Process email addresses - handle different line endings
        $emails_raw = str_replace(["\r\n", "\r", "\n"], ';', $emails_raw);
        $emails_array = explode(';', $emails_raw);
        
        // Collect valid email addresses
        $valid_emails = [];
        foreach ($emails_array as $email) {
            $email = trim($email);
            if (!empty($email)) {
                $valid_emails[] = $prefix . $email . $suffix;
            }
        }
        
        // Return emails separated by the specified separator
        $separator = isset($parameters['separator']) ? $parameters['separator'] : ', ';
        return implode($separator, $valid_emails);
    }
}
