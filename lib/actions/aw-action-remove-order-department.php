<?php

namespace RunthingsWCOrderDepartments\Actions;

use AutomateWoo\Action;
use AutomateWoo\Fields;

/**
 * Action to remove a department taxonomy term from an order
 */
class Remove_Order_Department extends Action
{
    public $required_data_items = ['order'];

    /**
     * Setup the action details
     */
    public function load_admin_details()
    {
        $this->title = __('Remove Order Department', 'runthings-wc-order-departments');
        $this->description = __('Remove a department from the order.', 'runthings-wc-order-departments');
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

        // Remove the specific department from the order
        wp_remove_object_terms($order->get_id(), $term_id, 'order_department');

        // Ensure the term cache is refreshed
        clean_post_cache($order->get_id());

        // Fire trigger for department removed
        do_action('runthings_wc_order_department_removed', $order->get_id(), $term_id);
        do_action('runthings_wc_order_departments_changed', $order->get_id());
    }
}
