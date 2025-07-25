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
        if (!$order instanceof \WC_Order) {
            throw new \Exception('Invalid order provided.');
        }

        // Clear all department taxonomy terms from the order
        wp_set_object_terms($order->get_id(), [], 'order_department', false);

        // Ensure the term cache is refreshed
        clean_post_cache($order->get_id());

        // Add order note (private note to avoid triggering other AutomateWoo workflows)
        /* translators: %s: Workflow ID */
        $note = sprintf(__('[AutomateWoo] Workflow #%s cleared all departments', 'runthings-wc-order-departments'), $this->workflow->get_id());
        $order->add_order_note($note, 0, false);

        // Fire trigger for departments changed
        do_action('runthings_wc_order_departments_changed', $order->get_id());
    }
}
