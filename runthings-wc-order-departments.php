<?php

/*
 * Plugin Name: WooCommerce Order Departments
 * Plugin URI: https://runthings.dev
 * Description: Split WooCommerce Orders by Departments
 * Version: 0.1.0
 * Author: runthingsdev
 * Author URI: https://runthings.dev/
 * Requires Plugins: woocommerce
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

define('RUNTHINGS_WC_ORDER_DEPARTMENTS_VERSION', '0.1.0');
define('RUNTHINGS_WC_ORDER_DEPARTMENTS_URL', plugin_dir_url(__FILE__));
define('RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR', plugin_dir_path(__FILE__));

require_once RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR . 'lib/automatewoo-integration.php';

class RunthingsWCOrderDepartments
{
    public function __construct()
    {
        add_action('init', [$this, 'register_order_department_taxonomy']);
        add_action('restrict_manage_posts', [$this, 'add_admin_filter_dropdown']);
        add_action('admin_menu', [$this, 'add_department_management_menu'], 99); // Use priority to place it near the bottom

        new AutomateWooIntegration();
    }

    public function register_order_department_taxonomy(): void
    {
        register_taxonomy('order_department', 'shop_order', [
            'label' => 'Department',
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'hierarchical' => false,
            'rewrite' => false,
        ]);
    }

    public function add_admin_filter_dropdown(string $post_type): void
    {
        if ($post_type !== 'shop_order') return;

        $taxonomy = 'order_department';
        $selected = $_GET[$taxonomy] ?? '';

        wp_dropdown_categories([
            'show_option_all' => 'All Departments',
            'taxonomy' => $taxonomy,
            'name' => $taxonomy,
            'orderby' => 'name',
            'selected' => $selected,
            'hierarchical' => false,
            'depth' => 1,
            'show_count' => false,
            'hide_empty' => false,
            'value_field' => 'slug', // use slug so wp built in taxonomy filter works with it
        ]);
    }

    public function add_department_management_menu(): void
    {
        add_submenu_page(
            'woocommerce',
            'Order Departments',
            'Order Departments',
            'manage_woocommerce',
            'edit-tags.php?taxonomy=order_department&post_type=shop_order'
        );
    }
}

// Initialize
new RunthingsWCOrderDepartments();
