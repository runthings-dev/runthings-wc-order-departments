<?php

namespace RunthingsWCOrderDepartments\Variables;

use AutomateWoo\Variable;

if (!defined('WPINC')) {
    die;
}

/**
 * AutomateWoo Variable - Order Department Count
 * 
 * Returns the number of departments assigned to an order.
 */
class Order_Department_Count extends Variable
{
    /**
     * Load admin details
     */
    public function load_admin_details()
    {
        $this->description = __('Displays the number of departments assigned to the order. Useful for conditional logic.', 'runthings-wc-order-departments');
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
        // Parameters not used in this variable
        unset($parameters);

        if (!$order instanceof \WC_Order) {
            return '0';
        }

        // Get department terms assigned to the order
        $department_terms = wp_get_object_terms($order->get_id(), 'order_department');
        
        if (empty($department_terms) || is_wp_error($department_terms)) {
            return '0';
        }

        // Return count as string (AutomateWoo variables return strings)
        return (string) count($department_terms);
    }
}
