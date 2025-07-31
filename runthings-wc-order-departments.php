<?php

/*
 * Plugin Name: Order Departments for WooCommerce
 * Plugin URI: https://runthings.dev/wordpress-plugins/runthings-wc-order-departments/
 * Description: Split WooCommerce orders by departments, with AutomateWoo support
 * Version: 1.0.1
 * Author: runthingsdev
 * Author URI: https://runthings.dev/
 * Requires at least: 6.3
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 8.2
 * WC tested up to: 9.9
 * Text Domain: runthings-wc-order-departments
 * Domain Path: /languages
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
/*
Copyright 2025 Matthew Harris

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

namespace RunthingsWCOrderDepartments;

if (!defined('WPINC')) {
    die;
}

define('RUNTHINGS_WC_ORDER_DEPARTMENTS_VERSION', '1.0.1');
define('RUNTHINGS_WC_ORDER_DEPARTMENTS_URL', plugin_dir_url(__FILE__));
define('RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR', plugin_dir_path(__FILE__));

require_once RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR . 'lib/taxonomy.php';
require_once RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR . 'lib/utils/department-matcher.php';
require_once RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR . 'lib/email-interceptor.php';
require_once RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR . 'lib/order-department-assigner.php';
require_once RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR . 'lib/automatewoo-integration.php';
require_once RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR . 'lib/settings.php';

class RunthingsWCOrderDepartments
{
    public $taxonomy = 'order_department';

    public function __construct()
    {
        // Declare HPOS compatibility
        add_action('before_woocommerce_init', [$this, 'declare_hpos_compatibility']);

        add_action('admin_init', [$this, 'setup_order_filters']);
        add_action('admin_menu', [$this, 'add_department_quick_access_menus']);
        add_action('admin_menu', [$this, 'add_departments_management_menu'], 99);

        new Taxonomy($this->taxonomy);
        new EmailInterceptor($this->taxonomy);
        new OrderDepartmentAssigner($this->taxonomy);
        new AutomateWooIntegration();
        new Settings();
    }

    public function declare_hpos_compatibility(): void
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    }

    public function setup_order_filters(): void
    {
        if ($this->is_hpos_enabled()) {
            // HPOS orders page
            add_action('woocommerce_order_list_table_restrict_manage_orders', [$this, 'add_hpos_filter_dropdown']);
            add_filter('woocommerce_order_list_table_prepare_items_query_args', [$this, 'filter_hpos_orders_by_department']);
        } else {
            // Classic post-based orders page
            add_action('restrict_manage_posts', [$this, 'add_admin_filter_dropdown']);
        }
    }

    private function is_hpos_enabled(): bool
    {
        // Check if WooCommerce is loaded and HPOS classes exist
        if (!class_exists('Automattic\WooCommerce\Utilities\OrderUtil')) {
            return false;
        }

        // Check if HPOS is actually enabled
        return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
    }

    public function add_admin_filter_dropdown($post_type): void
    {
        if ($post_type !== 'shop_order') {
            return;
        }

        $this->render_department_dropdown();
    }

    public function add_hpos_filter_dropdown(): void
    {
        $this->render_department_dropdown();
    }

    private function render_department_dropdown(): void
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is for admin filtering, not form submission
        $selected = isset($_GET[$this->taxonomy]) ? sanitize_text_field(wp_unslash($_GET[$this->taxonomy])) : '';

        wp_dropdown_categories([
            'show_option_all' => 'All Departments',
            'taxonomy' => $this->taxonomy,
            'name' => $this->taxonomy,
            'orderby' => 'name',
            'selected' => $selected,
            'hierarchical' => false,
            'depth' => 1,
            'show_count' => false,
            'hide_empty' => false,
            'value_field' => 'slug', // use slug so wp built in taxonomy filter works with it
        ]);
    }

    public function filter_hpos_orders_by_department($query_args)
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is for admin filtering, not form submission
        if (isset($_GET[$this->taxonomy]) && !empty($_GET[$this->taxonomy])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is for admin filtering, not form submission
            $department_slug = sanitize_text_field(wp_unslash($_GET[$this->taxonomy]));

            // Get the term by slug
            $term = get_term_by('slug', $department_slug, $this->taxonomy);
            if ($term && !is_wp_error($term)) {
                // Get order IDs that have this department taxonomy term
                $order_ids = get_objects_in_term($term->term_id, $this->taxonomy);

                if (!empty($order_ids)) {
                    // For HPOS queries, use 'id' instead of 'post__in'
                    $query_args['id'] = $order_ids;
                } else {
                    // No orders found with this department, return empty result
                    $query_args['id'] = [0]; // This will return no results
                }
            }
        }

        return $query_args;
    }

    public function add_department_quick_access_menus(): void
    {
        // Get all departments
        $departments = get_terms([
            'taxonomy' => $this->taxonomy,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        if (is_wp_error($departments) || empty($departments)) {
            return;
        }

        // Determine the correct URL based on HPOS status
        $base_url = $this->is_hpos_enabled()
            ? 'admin.php?page=wc-orders&'
            : 'edit.php?post_type=shop_order&';

        // Loop through each department and add a submenu link
        foreach ($departments as $department) {
            add_submenu_page(
                'woocommerce', // Parent slug
                'Orders - ' . $department->name, // Page title
                'Orders - ' . $department->name, // Menu title
                'manage_woocommerce', // Capability
                $base_url . $this->taxonomy . '=' . $department->slug, // URL with filter
                null // Callback function (null because we're just linking)
            );
        }
    }

    public function add_departments_management_menu(): void
    {
        add_submenu_page(
            'woocommerce',
            'Order Departments',
            'Order Departments',
            'manage_woocommerce',
            'edit-tags.php?taxonomy='.$this->taxonomy.'&post_type=shop_order'
        );
    }
}

// Initialize
new RunthingsWCOrderDepartments();
