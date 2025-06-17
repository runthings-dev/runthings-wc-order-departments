<?php

namespace RunthingsWCOrderDepartments\Variables;

use AutomateWoo\Variable;

if (!defined('WPINC')) {
    die;
}

/**
 * AutomateWoo Variable - Order Departments Names
 *
 * Returns a list of all department names assigned to an order.
 */
class Order_Departments_Names extends Variable
{
    /**
     * Load admin details
     */
    public function load_admin_details()
    {
        $this->description = __('Displays a list of all department names assigned to the order.', 'runthings-wc-order-departments');

        $this->add_parameter_text_field(
            'separator',
            __('The separator to use between department names. Default is ", " (comma space).', 'runthings-wc-order-departments'),
            false,
            ', '
        );

        $this->add_parameter_text_field(
            'prefix',
            __('Text to add before each department name. Example: "Dept: " would turn "Sales" into "Dept: Sales".', 'runthings-wc-order-departments'),
            false,
            ''
        );

        $this->add_parameter_text_field(
            'suffix',
            __('Text to add after each department name. Example: " Dept" would turn "Sales" into "Sales Dept".', 'runthings-wc-order-departments'),
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

        // Extract department names and apply prefix/suffix
        $prefix = isset($parameters['prefix']) ? $parameters['prefix'] : '';
        $suffix = isset($parameters['suffix']) ? $parameters['suffix'] : '';

        $department_names = array_map(function($term) use ($prefix, $suffix) {
            return $prefix . $term->name . $suffix;
        }, $department_terms);

        // Get separator from parameters, default to ", "
        $separator = isset($parameters['separator']) ? $parameters['separator'] : ', ';

        // Return separated list
        return implode($separator, $department_names);
    }
}
