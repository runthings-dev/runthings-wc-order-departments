<?php

namespace RunthingsWCOrderDepartments\Rules;

use AutomateWoo\Rules\Rule;
use AutomateWoo\DataTypes\DataTypes;

/**
 * Rule to check if an order's departments exactly match a set
 */
class Order_Department_Is extends Rule
{
    public $data_item = DataTypes::ORDER;

    public $type = 'select';

    /**
     * Setup the rule details
     */
    public function init()
    {
        $this->title = __('Order - Department Is', 'runthings-wc-order-departments');
        $this->compare_types = $this->get_is_or_not_compare_types();
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

        // Convert to strings and sort for exact comparison
        $order_departments = array_map('strval', $order_departments);
        $expected_departments = array_map('strval', (array) $value);

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
