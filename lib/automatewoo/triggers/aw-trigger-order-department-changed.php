<?php

namespace RunthingsWCOrderDepartments\Triggers;

use AutomateWoo\Trigger;

/**
 * Trigger for when order departments are changed (any modification)
 */
class Order_Department_Changed extends Trigger
{
    public $supplied_data_items = ['order'];

    /**
     * Setup the trigger details
     */
    public function load_admin_details()
    {
        $this->title = __('Order Department Changed', 'runthings-wc-order-departments');
        $this->description = __('Triggers when order departments are modified in any way.', 'runthings-wc-order-departments');
        $this->group = __('Order', 'runthings-wc-order-departments');
    }

    /**
     * No fields needed for this trigger
     */
    public function load_fields()
    {
        // No fields needed - this triggers on any department change
    }

    /**
     * Validate the trigger
     */
    public function validate_workflow($workflow)
    {
        $order = $workflow->data_layer()->get_order();
        return $order ? true : false;
    }

    /**
     * Register hooks for this trigger
     */
    public function register_hooks()
    {
        add_action('runthings_wc_order_departments_changed', [$this, 'handle_departments_changed'], 10, 1);
    }

    /**
     * Handle the departments changed event
     */
    public function handle_departments_changed($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $this->maybe_run([
            'order' => $order,
        ]);
    }
}
