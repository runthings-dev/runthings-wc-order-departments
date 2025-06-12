<?php

namespace RunthingsWCOrderDepartments\Actions;

use AutomateWoo\Action;

/**
 * Action to clear all department taxonomy terms from an order
 */
class Clear_Order_Departments extends Action
{
    public $required_data_items = ['order'];

    /**
     * Setup the action details
     */
    public function load_admin_details()
    {
        $this->title = __('Clear Order Departments', 'runthings-wc-order-departments');
        $this->description = __('Remove all departments from the order.', 'runthings-wc-order-departments');
        $this->group = __('Order', 'runthings-wc-order-departments');
    }

    /**
     * No fields needed for this action
     */
    public function load_fields()
    {
        // No fields needed - this action clears all departments
    }

    /**
     * Run the action
     */
    public function run()
    {
        $order = $this->workflow->data_layer()->get_order();
        if (!$order) {
            return;
        }

        // Clear all department taxonomy terms from the order
        wp_set_object_terms($order->get_id(), [], 'order_department', false);
        
        // Ensure the term cache is refreshed
        clean_post_cache($order->get_id());
    }
}
