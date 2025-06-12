<?php

namespace RunthingsWCOrderDepartments\Actions;

use AutomateWoo\Action;
use AutomateWoo\Fields;

/**
 * Action to set the department taxonomy term for an order
 */
class Set_Order_Department extends Action
{
    public $required_data_items = ['order'];

    /**
     * Setup the action details
     */
    public function load_admin_details()
    {
        $this->title = __('Set Order Department', 'runthings-wc-order-departments');
        $this->description = __('Set a department for the order.', 'runthings-wc-order-departments');
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
        if (!$order) {
            return;
        }

        $department_id = $this->get_option('department');
        if (!$department_id) {
            return;
        }

        // Make sure we're using an integer for the term ID
        $term_id = (int)$department_id;

        // Set the department taxonomy for the order (append, don't replace)
        wp_set_object_terms($order->get_id(), $term_id, 'order_department', true);

        // Ensure the term cache is refreshed
        clean_post_cache($order->get_id());
    }
}
