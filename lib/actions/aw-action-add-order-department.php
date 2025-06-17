<?php

namespace RunthingsWCOrderDepartments\Actions;

use AutomateWoo\Action;
use AutomateWoo\Fields;

/**
 * Action to add a department taxonomy term to an order
 */
class Add_Order_Department extends Action
{
    public $required_data_items = ['order'];

    /**
     * Setup the action details
     */
    public function load_admin_details()
    {
        $this->title = __('Add Order Department', 'runthings-wc-order-departments');
        $this->description = __('Add a department to the order (keeps existing departments).', 'runthings-wc-order-departments');
        $this->group = __('Order', 'runthings-wc-order-departments');
    }

    /**
     * Add the fields for the action
     */
    public function load_fields()
    {
        $department_field = new Fields\Select();
        $department_field->set_name('department');
        $department_field->set_title(__('Department', 'runthings-wc-order-departments'));
        $department_field->set_required(true);
        $department_field->set_options($this->get_department_options());

        $this->add_field($department_field);
    }

    /**
     * Get available department options from the taxonomy
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
     * Run the action
     */
    public function run()
    {
        $order = $this->workflow->data_layer()->get_order();
        if (!$order instanceof \WC_Order) {
            throw new \Exception('Invalid order provided.');
        }

        $department_id = $this->get_option('department');
        if (!$department_id) {
            return;
        }

        // Make sure we're using an integer for the term ID
        $term_id = (int)$department_id;

        // Add the department taxonomy to the order (append, don't replace)
        wp_set_object_terms($order->get_id(), $term_id, 'order_department', true);

        // Ensure the term cache is refreshed
        clean_post_cache($order->get_id());

        // Get department name for the note
        $department_term = get_term($term_id, 'order_department');
        $department_name = $department_term && !is_wp_error($department_term) ? $department_term->name : "ID: $term_id";

        // Add order note (private note to avoid triggering other AutomateWoo workflows)
        /* translators: %1$s: Workflow ID, %2$s: Department name */
        $note = sprintf(__('[AutomateWoo] Workflow #%1$s added department: %2$s', 'runthings-wc-order-departments'), $this->workflow->get_id(), $department_name);
        $order->add_order_note($note, 0, false);

        // Fire trigger for department added
        do_action('runthings_wc_order_department_added', $order->get_id(), $term_id);
        do_action('runthings_wc_order_departments_changed', $order->get_id());
    }
}
