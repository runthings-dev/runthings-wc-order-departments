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

require_once RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR . 'lib/taxonomy.php';
require_once RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR . 'lib/email-interceptor.php';
require_once RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR . 'lib/automatewoo-integration.php';

class RunthingsWCOrderDepartments
{
    public $taxonomy = 'order_department';

    public function __construct()
    {
        add_action('restrict_manage_posts', [$this, 'add_admin_filter_dropdown']);
        add_action('admin_menu', [$this, 'add_department_quick_access_menus']);
        add_action('admin_menu', [$this, 'add_departments_management_menu'], 99);

        new Taxonomy($this->taxonomy);
        new EmailInterceptor($this->taxonomy);
        new AutomateWooIntegration();
    }

    public function add_admin_filter_dropdown(string $post_type): void
    {
        if ($post_type !== 'shop_order') return;

        $selected = $_GET[$this->taxonomy] ?? '';

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
        
        // Loop through each department and add a submenu link
        foreach ($departments as $department) {
            add_submenu_page(
                'woocommerce', // Parent slug
                'Orders - ' . $department->name, // Page title
                'Orders - ' . $department->name, // Menu title
                'manage_woocommerce', // Capability
                'edit.php?post_type=shop_order&'.$this->taxonomy.'=' . $department->slug, // URL with filter
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
