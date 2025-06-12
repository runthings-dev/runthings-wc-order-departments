<?php

namespace RunthingsWCOrderDepartments;

use RunthingsWCOrderDepartments\Utils\DepartmentMatcher;

if (!defined('WPINC')) {
    die;
}

/**
 * Automatically assigns department taxonomy terms to orders based on their products and categories
 */
class OrderDepartmentAssigner
{
    private $taxonomy;
    private $department_matcher;

    public function __construct($taxonomy = 'order_department')
    {
        $this->taxonomy = $taxonomy;
        $this->department_matcher = new DepartmentMatcher($taxonomy);
        
        // Hook into order creation and updates
        add_action('woocommerce_checkout_order_processed', [$this, 'assign_department_to_order'], 10, 1);
        add_action('woocommerce_new_order', [$this, 'assign_department_to_order'], 10, 1);
        
        // Also hook into order item changes in case products are modified after order creation
        add_action('woocommerce_saved_order_items', [$this, 'reassign_department_on_item_change'], 10, 2);
    }

    /**
     * Assign department(s) to an order based on its products and categories
     *
     * @param int|\WC_Order $order_id_or_object Order ID or WC_Order object
     */
    public function assign_department_to_order($order_id_or_object)
    {
        // Get the order object
        if (is_numeric($order_id_or_object)) {
            $order = wc_get_order($order_id_or_object);
        } elseif ($order_id_or_object instanceof \WC_Order) {
            $order = $order_id_or_object;
        } else {
            return;
        }

        // Make sure we have a valid order
        if (!$order || !($order instanceof \WC_Order)) {
            return;
        }

        // Get matching department term IDs
        $department_term_ids = $this->department_matcher->get_department_term_ids($order);

        // If we found matching departments, assign them to the order
        if (!empty($department_term_ids)) {
            // Set the department taxonomy terms for the order
            // Use false for $append to replace any existing terms
            wp_set_object_terms($order->get_id(), $department_term_ids, $this->taxonomy, false);
            
            // Ensure the term cache is refreshed
            clean_post_cache($order->get_id());
            
            // Log the assignment for debugging (optional)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    'RunthingsWCOrderDepartments: Assigned departments %s to order #%d',
                    implode(', ', $department_term_ids),
                    $order->get_id()
                ));
            }
        } else {
            // No matching departments found, remove any existing department assignments
            wp_set_object_terms($order->get_id(), [], $this->taxonomy, false);
            
            // Log for debugging (optional)
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    'RunthingsWCOrderDepartments: No matching departments found for order #%d, removed existing assignments',
                    $order->get_id()
                ));
            }
        }
    }

    /**
     * Reassign departments when order items are changed
     *
     * @param int $order_id Order ID
     * @param array $items Order items
     */
    public function reassign_department_on_item_change($order_id, $items)
    {
        // Re-run the department assignment logic
        $this->assign_department_to_order($order_id);
    }

    /**
     * Get the department matcher instance (for testing or external use)
     *
     * @return DepartmentMatcher
     */
    public function get_department_matcher()
    {
        return $this->department_matcher;
    }
}
