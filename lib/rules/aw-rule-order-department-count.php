<?php

namespace RunthingsWCOrderDepartments\Rules;

use AutomateWoo\Rules\Rule;
use AutomateWoo\DataTypes\DataTypes;

/**
 * Rule to check the number of departments assigned to an order
 */
class Order_Department_Count extends Rule
{
    public $data_item = DataTypes::ORDER;

    public $type = 'number';

    /**
     * Setup the rule details
     */
    public function init()
    {
        $this->title = __('Order - Department Count', 'runthings-wc-order-departments');
        $this->compare_types = $this->get_integer_compare_types();
    }

    /**
     * Validate the rule
     */
    public function validate($order, $compare, $value)
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

        return $this->validate_number($actual_count, $compare, $value);
    }
}
