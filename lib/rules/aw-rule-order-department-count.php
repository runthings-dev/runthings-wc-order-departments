<?php

namespace RunthingsWCOrderDepartments\Rules;

use AutomateWoo\Rule;
use AutomateWoo\Fields;

/**
 * Rule to check the number of departments assigned to an order
 */
class Order_Department_Count extends Rule
{
    public $data_item = 'order';
    public $type = 'number';

    /**
     * Setup the rule details
     */
    public function init()
    {
        $this->title = __('Order Department Count', 'runthings-wc-order-departments');
        $this->group = __('Order', 'runthings-wc-order-departments');
    }

    /**
     * Add fields for the rule
     */
    public function load_fields()
    {
        $compare_field = new Fields\Select();
        $compare_field->set_name('compare');
        $compare_field->set_title(__('Compare', 'runthings-wc-order-departments'));
        $compare_field->set_options([
            'is' => __('is equal to', 'runthings-wc-order-departments'),
            'is_not' => __('is not equal to', 'runthings-wc-order-departments'),
            'greater_than' => __('is greater than', 'runthings-wc-order-departments'),
            'less_than' => __('is less than', 'runthings-wc-order-departments'),
            'greater_than_or_equal' => __('is greater than or equal to', 'runthings-wc-order-departments'),
            'less_than_or_equal' => __('is less than or equal to', 'runthings-wc-order-departments'),
        ]);
        $compare_field->set_required(true);

        $this->add_field($compare_field);

        $count_field = new Fields\Number();
        $count_field->set_name('count');
        $count_field->set_title(__('Count', 'runthings-wc-order-departments'));
        $count_field->set_min(0);
        $count_field->set_required(true);

        $this->add_field($count_field);
    }

    /**
     * Validate the rule
     */
    public function validate($order, $compare, $expected_count)
    {
        if (!$order instanceof \WC_Order) {
            return false;
        }

        // Get order's current departments count
        $order_departments = wp_get_object_terms($order->get_id(), 'order_department', ['fields' => 'ids']);
        if (is_wp_error($order_departments)) {
            $order_departments = [];
        }

        $actual_count = count($order_departments);
        $expected_count = intval($expected_count);

        switch ($compare) {
            case 'is':
                return $actual_count === $expected_count;

            case 'is_not':
                return $actual_count !== $expected_count;

            case 'greater_than':
                return $actual_count > $expected_count;

            case 'less_than':
                return $actual_count < $expected_count;

            case 'greater_than_or_equal':
                return $actual_count >= $expected_count;

            case 'less_than_or_equal':
                return $actual_count <= $expected_count;

            default:
                return false;
        }
    }
}
