<?php

namespace RunthingsWCOrderDepartments;

if (!defined('WPINC')) {
    die;
}

class EmailInterceptor
{
    private $taxonomy;

    private $meta_prefix = 'runthings_wc_od_';
    
    public function __construct($taxonomy = 'order_department')
    {
        $this->taxonomy = $taxonomy;
        
        // Hook into WooCommerce email filters to modify recipients for admin notifications
        add_filter('woocommerce_email_recipient_new_order', [$this, 'modify_email_recipient'], 10, 2);
        add_filter('woocommerce_email_recipient_cancelled_order', [$this, 'modify_email_recipient'], 10, 2);
        add_filter('woocommerce_email_recipient_failed_order', [$this, 'modify_email_recipient'], 10, 2);
    }
    
    /**
     * Modify email recipients based on order department
     *
     * @param string $recipient Default recipient email(s)
     * @param \WC_Order $order Order object
     * @return string Modified recipient email(s)
     */
    public function modify_email_recipient($recipient, $order)
    {
        // Make sure we have a valid order
        if (!$order instanceof \WC_Order) {
            return $recipient;
        }
        
        // Get department recipients
        $department_emails = $this->get_department_recipients($order);
        
        // If department emails were found, use them instead
        if (!empty($department_emails)) {
            return $department_emails;
        }
        
        // Otherwise return the original recipient
        return $recipient;
    }
    
    /**
     * Get all departments with their metadata
     *
     * @return array Departments with their metadata
     */
    private function get_all_departments()
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
            
            // Add to our data structure if department has routing criteria (emails + either products or categories)
            if (!empty($emails) && (!empty($categories) || !empty($products))) {
                $departments_data[] = [
                    'id' => $department->term_id,
                    'name' => $department->name,
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
    private function get_order_product_data($order)
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
    private function should_route_to_department($department, $product_data)
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
     * Get department email addresses for an order
     *
     * @param \WC_Order $order Order object
     * @return string Email addresses separated by commas
     */
    private function get_department_recipients($order)
    {
        // Get departments with their data
        $departments = $this->get_all_departments();
        
        if (empty($departments)) {
            return '';
        }
        
        // Get order product data
        $product_data = $this->get_order_product_data($order);
        
        // Check departments for matches and collect emails
        $destination_emails = [];
        
        foreach ($departments as $department) {
            if ($this->should_route_to_department($department, $product_data)) {
                $destination_emails = array_merge($destination_emails, $department['emails']);
            }
        }
        
        // Remove duplicates and filter out empty values
        $destination_emails = array_unique(array_filter($destination_emails));
        
        // Return comma-separated list of emails (WordPress email format)
        return implode(', ', $destination_emails);
    }
}