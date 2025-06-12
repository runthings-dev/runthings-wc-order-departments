<?php

namespace RunthingsWCOrderDepartments\Rules;

use AutomateWoo\Rule;
use AutomateWoo\Fields;

/**
 * Rule to check if an order's departments exactly match a set
 */
class Order_Department_Is extends Rule
{
    public $data_item = 'order';

    /**
     * Setup the rule details
     */
    public function init()
    {
        $this->title = __('Order Department Is', 'runthings-wc-order-departments');
        $this->group = __('Order', 'runthings-wc-order-departments');
    }

    /**
     * Add fields for the rule
     */
    public function load_fields()
    {
        $department_field = new Fields\Select();
        $department_field->set_name('department');
        $department_field->set_title(__('Department(s)', 'runthings-wc-order-departments'));
        $department_field->set_description(__('Order must have exactly these departments (no more, no less).', 'runthings-wc-order-departments'));
        $department_field->set_multiple(true);
        $department_field->set_options($this->get_department_options());
        $department_field->set_required(true);

        $this->add_field($department_field);

        $compare_field = new Fields\Select();
        $compare_field->set_name('compare');
        $compare_field->set_title(__('Compare', 'runthings-wc-order-departments'));
        $compare_field->set_options([
            'is' => __('is exactly', 'runthings-wc-order-departments'),
            'is_not' => __('is not exactly', 'runthings-wc-order-departments'),
        ]);
        $compare_field->set_required(true);

        $this->add_field($compare_field);
    }

    /**
     * Get available department options
     */
    private function get_department_options()
    {
        $options = [];
        
        $terms = get_terms([
            'taxonomy' => 'order_department',
            'hide_empty' => false,
        ]);

        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                $options[$term->term_id] = $term->name;
            }
        }

        return $options;
    }

    /**
     * Validate the rule
     */
    public function validate($order, $compare, $expected_departments)
    {
        if (!$order instanceof \WC_Order) {
            return false;
        }

        // Get order's current departments
        $order_departments = wp_get_object_terms($order->get_id(), 'order_department', ['fields' => 'ids']);
        if (is_wp_error($order_departments)) {
            $order_departments = [];
        }

        // Convert expected departments to array if it's not already
        if (!is_array($expected_departments)) {
            $expected_departments = [$expected_departments];
        }

        // Convert to integers and sort for comparison
        $order_departments = array_map('intval', $order_departments);
        $expected_departments = array_map('intval', $expected_departments);
        
        sort($order_departments);
        sort($expected_departments);

        $is_exact_match = $order_departments === $expected_departments;

        switch ($compare) {
            case 'is':
                return $is_exact_match;

            case 'is_not':
                return !$is_exact_match;

            default:
                return false;
        }
    }
}
