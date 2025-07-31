<?php

namespace RunthingsWCOrderDepartments\Utils;

if (!defined('WPINC')) {
    die;
}

/**
 * Utility class for determining which department(s) an order should be assigned to
 * based on the products and categories in the order.
 */
class DepartmentMatcher
{
    private $taxonomy;

    private $meta_prefix = 'runthings_wc_od_';

    public function __construct($taxonomy = 'order_department')
    {
        $this->taxonomy = $taxonomy;
    }

    /**
     * Get all departments with their metadata
     *
     * @return array Departments with their metadata
     */
    public function get_all_departments()
    {
        $departments_data = [];
        
        $departments = get_terms([
            'taxonomy' => $this->taxonomy,
            'hide_empty' => false,
        ]);
        
        if (empty($departments) || is_wp_error($departments)) {
            return $departments_data;
        }
        
        foreach ($departments as $department) {
            $emails_raw = get_term_meta($department->term_id, $this->meta_prefix . 'department_emails', true);
            $categories = get_term_meta($department->term_id, $this->meta_prefix . 'department_categories', true);
            $products = get_term_meta($department->term_id, $this->meta_prefix . 'selected_products', true);
            
            // Process email addresses - ensure no newlines or extra whitespace
            $emails = [];
            if (!empty($emails_raw)) {
                $emails_raw = str_replace(["\r\n", "\r", "\n"], ';', $emails_raw);
                $emails_array = explode(';', $emails_raw);
                
                // Trim and filter each email address
                foreach ($emails_array as $email) {
                    $email = trim($email);
                    if (!empty($email)) {
                        $emails[] = $email;
                    }
                }
            }
            
            // Ensure categories and products are arrays
            $categories = is_array($categories) ? $categories : [];
            $products = is_array($products) ? $products : [];
            
            // Add to our data structure if department has routing criteria (either products or categories)
            if (!empty($categories) || !empty($products)) {
                $departments_data[] = [
                    'id' => $department->term_id,
                    'name' => $department->name,
                    'slug' => $department->slug,
                    'emails' => $emails,
                    'categories' => $categories,
                    'products' => $products,
                ];
            }
        }
        
        return $departments_data;
    }

    /**
     * Get product data from an order
     *
     * @param \WC_Order $order
     * @return array Product data including IDs and categories
     */
    public function get_order_product_data($order)
    {
        $product_data = [
            'product_ids' => [],
            'category_ids' => [],
        ];
        
        // Loop through order items
        foreach ($order->get_items() as $item) {
            // Make sure we're working with a product item
            if (!($item instanceof \WC_Order_Item_Product)) {
                continue;
            }
            
            // Get the product ID from the item
            $product_id = $item->get_product_id();
            
            // Add product ID
            $product_data['product_ids'][] = $product_id;
            
            // Get product categories
            $categories = get_the_terms($product_id, 'product_cat');
            if (!empty($categories) && !is_wp_error($categories)) {
                foreach ($categories as $category) {
                    $product_data['category_ids'][] = $category->term_id;
                }
            }
        }
        
        // Remove duplicates
        $product_data['product_ids'] = array_unique($product_data['product_ids']);
        $product_data['category_ids'] = array_unique($product_data['category_ids']);
        
        return $product_data;
    }

    /**
     * Check if an order should be routed to a department
     *
     * @param array $department Department data
     * @param array $product_data Order product data
     * @return bool Whether the order should be routed to this department
     */
    public function should_route_to_department($department, $product_data)
    {
        // Check if any product in the order is directly assigned to the department
        if (!empty($department['products']) && !empty($product_data['product_ids'])) {
            $matching_products = array_intersect($department['products'], $product_data['product_ids']);
            if (!empty($matching_products)) {
                return true;
            }
        }
        
        // Check if any product category in the order is assigned to the department
        if (!empty($department['categories']) && !empty($product_data['category_ids'])) {
            $matching_categories = array_intersect($department['categories'], $product_data['category_ids']);
            if (!empty($matching_categories)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get all matching departments for an order
     *
     * @param \WC_Order $order Order object
     * @return array Array of matching department data
     */
    public function get_matching_departments($order)
    {
        // Get departments with their data
        $departments = $this->get_all_departments();
        
        if (empty($departments)) {
            return [];
        }
        
        // Get order product data
        $product_data = $this->get_order_product_data($order);
        
        // Check departments for matches
        $matching_departments = [];
        
        foreach ($departments as $department) {
            if ($this->should_route_to_department($department, $product_data)) {
                $matching_departments[] = $department;
            }
        }
        
        return $matching_departments;
    }

    /**
     * Get unique department email addresses for an order
     *
     * @param \WC_Order $order Order object
     * @return array Array of unique email addresses
     */
    public function get_unique_department_emails($order)
    {
        $matching_departments = $this->get_matching_departments($order);

        // Collect emails from all matching departments
        $destination_emails = [];

        foreach ($matching_departments as $department) {
            if (!empty($department['emails'])) {
                $destination_emails = array_merge($destination_emails, $department['emails']);
            }
        }

        // Remove duplicates and filter out empty values
        return array_unique(array_filter($destination_emails));
    }

    /**
     * Get department term IDs for an order
     *
     * @param \WC_Order $order Order object
     * @return array Array of department term IDs
     */
    public function get_department_term_ids($order)
    {
        $matching_departments = $this->get_matching_departments($order);
        
        $term_ids = [];
        foreach ($matching_departments as $department) {
            $term_ids[] = $department['id'];
        }
        
        return $term_ids;
    }
}
