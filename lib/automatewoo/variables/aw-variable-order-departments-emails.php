<?php

namespace RunthingsWCOrderDepartments\Variables;

use AutomateWoo\Variable;

if (!defined('WPINC')) {
    die;
}

/**
 * AutomateWoo Variable - Order Departments Emails
 *
 * Returns a list of all department email addresses assigned to an order.
 * Automatically deduplicates email addresses if multiple departments share the same email.
 */
class Order_Departments_Emails extends Variable
{
    /**
     * Load admin details
     */
    public function load_admin_details()
    {
        $this->description = __('Displays a list of all department email addresses assigned to the order. Useful for the "To" field in email actions.', 'runthings-wc-order-departments');

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

        // Collect all email addresses from assigned departments
        $all_emails = [];
        $meta_prefix = 'runthings_wc_od_';

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
        $prefix = isset($parameters['prefix']) ? $parameters['prefix'] : '';
        $suffix = isset($parameters['suffix']) ? $parameters['suffix'] : '';

        if (!empty($prefix) || !empty($suffix)) {
            $all_emails = array_map(function($email) use ($prefix, $suffix) {
                return $prefix . $email . $suffix;
            }, $all_emails);
        }

        // Get separator from parameters, default to ", "
        $separator = isset($parameters['separator']) ? $parameters['separator'] : ', ';

        // Return separated list of emails
        return implode($separator, $all_emails);
    }
}
