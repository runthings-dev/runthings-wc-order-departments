<?php

namespace RunthingsWCOrderDepartments\Triggers;

use AutomateWoo\Trigger;
use AutomateWoo\Fields;

/**
 * Trigger for when a department is removed from an order
 */
class Order_Department_Removed extends Trigger
{
    public $supplied_data_items = ['order', 'department'];

    /**
     * Setup the trigger details
     */
    public function load_admin_details()
    {
        $this->title = __('Order Department Removed', 'runthings-wc-order-departments');
        $this->description = __('Triggers when a department is removed from an order.', 'runthings-wc-order-departments');
        $this->group = __('Order', 'runthings-wc-order-departments');
    }

    /**
     * Add fields for the trigger
     */
    public function load_fields()
    {
        $department_field = new Fields\Select();
        $department_field->set_name('department');
        $department_field->set_title(__('Department', 'runthings-wc-order-departments'));
        $department_field->set_description(__('Leave blank to trigger for any department.', 'runthings-wc-order-departments'));
        $department_field->set_options($this->get_department_options());
        $department_field->set_required(false);

        $this->add_field($department_field);
    }

    /**
     * Get available department options
     */
    private function get_department_options()
    {
        $options = ['' => __('Any department', 'runthings-wc-order-departments')];
        
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
     * Validate the trigger
     */
    public function validate_workflow($workflow)
    {
        $order = $workflow->data_layer()->get_order();
        $department = $workflow->data_layer()->get_department();

        if (!$order || !$department) {
            return false;
        }

        // Check if specific department is required
        $required_department = $this->get_option('department');
        if ($required_department && $department->term_id != $required_department) {
            return false;
        }

        return true;
    }

    /**
     * Register hooks for this trigger
     */
    public function register_hooks()
    {
        add_action('runthings_wc_order_department_removed', [$this, 'handle_department_removed'], 10, 2);
    }

    /**
     * Handle the department removed event
     */
    public function handle_department_removed($order_id, $department_term_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $department = get_term($department_term_id, 'order_department');
        if (!$department || is_wp_error($department)) {
            return;
        }

        $this->maybe_run([
            'order' => $order,
            'department' => $department,
        ]);
    }
}
