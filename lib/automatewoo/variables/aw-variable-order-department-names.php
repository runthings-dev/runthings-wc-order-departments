<?php

namespace RunthingsWCOrderDepartments\Variables;

use AutomateWoo\Variable;

if (!defined('WPINC')) {
    die;
}

/**
 * AutomateWoo Variable - Order Department Names
 * 
 * Returns department names with flexible access options:
 * - By index (1-based): specific department
 * - All: all departments with separator
 * - Count: number of departments
 */
class Order_Department_Names extends Variable
{
    /**
     * Load admin details
     */
    public function load_admin_details()
    {
        $this->description = __('Displays department names with flexible access options. Use index for specific departments, "all" for all departments, or "count" for the number of departments.', 'runthings-wc-order-departments');
        
        $this->add_parameter_select_field(
            'mode',
            __('How to access the department names.', 'runthings-wc-order-departments'),
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
            __('The separator to use between department names when mode is "all". Default is ", " (comma space).', 'runthings-wc-order-departments'),
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

        // Get mode from parameters, default to 'index'
        $mode = isset($parameters['mode']) ? $parameters['mode'] : 'index';
        
        // Handle count mode
        if ($mode === 'count') {
            return (string) count($department_terms);
        }
        
        // Get prefix/suffix
        $prefix = isset($parameters['prefix']) ? $parameters['prefix'] : '';
        $suffix = isset($parameters['suffix']) ? $parameters['suffix'] : '';
        
        // Handle all mode
        if ($mode === 'all') {
            $department_names = array_map(function($term) use ($prefix, $suffix) {
                return $prefix . $term->name . $suffix;
            }, $department_terms);
            
            $separator = isset($parameters['separator']) ? $parameters['separator'] : ', ';
            return implode($separator, $department_names);
        }
        
        // Handle index mode (default)
        $index = isset($parameters['index']) ? (int) $parameters['index'] : 1;
        
        // Convert to 0-based index
        $zero_based_index = $index - 1;
        
        // Check if the requested index exists
        if (!isset($department_terms[$zero_based_index]) || $zero_based_index < 0) {
            return '';
        }

        // Return the name of the department at the specified index with prefix/suffix
        return $prefix . $department_terms[$zero_based_index]->name . $suffix;
    }
}
