<?php

namespace RunthingsWCOrderDepartments\Rules;

use AutomateWoo\Rules\Rule;
use AutomateWoo\DataTypes\DataTypes;

/**
 * Rule to check if an order has specific department(s)
 */
class Order_Has_Department extends Rule
{
    public $data_item = DataTypes::ORDER;

    public $type = 'select';

    /**
     * Setup the rule details
     */
    public function init()
    {
        $this->title = __('Order - Has Department', 'runthings-wc-order-departments');
        $this->compare_types = $this->get_multi_select_compare_types();
    }

    /**
     * Load select choices for rule
     */
    public function load_select_choices()
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
    public function validate($order, $compare, $value)
    {
        if (!$order instanceof \WC_Order) {
            return false;
        }

        // Get order's current departments
        $order_departments = wp_get_object_terms($order->get_id(), 'order_department', ['fields' => 'ids']);
        if (is_wp_error($order_departments)) {
            $order_departments = [];
        }

        // Convert to strings for comparison
        $order_departments = array_map('strval', $order_departments);
        $expected_departments = array_map('strval', (array) $value);

        switch ($compare) {
            case 'matches_any':
                return !empty(array_intersect($order_departments, $expected_departments));

            case 'matches_all':
                return empty(array_diff($expected_departments, $order_departments));

            case 'matches_none':
                return empty(array_intersect($order_departments, $expected_departments));

            default:
                return false;
        }
    }
}
