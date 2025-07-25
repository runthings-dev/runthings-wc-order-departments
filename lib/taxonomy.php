<?php

namespace RunthingsWCOrderDepartments;

if (!defined('WPINC')) {
    die;
}

class Taxonomy
{
    private $taxonomy;

    private $meta_prefix = 'runthings_wc_od_';

    public function __construct($taxonomy)
    {
        $this->taxonomy = $taxonomy;

        add_action('init', [$this, 'register_taxonomy']);
        
        // Add hooks for the term form fields and saving
        add_action("{$taxonomy}_add_form_fields", [$this, 'add_term_fields']);
        add_action("{$taxonomy}_edit_form_fields", [$this, 'edit_term_fields']);
        add_action("created_{$taxonomy}", [$this, 'save_term_fields']);
        add_action("edited_{$taxonomy}", [$this, 'save_term_fields']);
        
        // Enqueue scripts and styles for admin
        add_action('admin_print_scripts-edit-tags.php', [$this, 'enqueue_admin_scripts']);
        add_action('admin_print_scripts-term.php', [$this, 'enqueue_admin_scripts']);

        // Add custom columns to screen options
        add_filter("manage_edit-{$taxonomy}_columns", [$this, 'add_custom_columns']);
        add_filter("manage_{$taxonomy}_custom_column", [$this, 'display_custom_column'], 10, 3);
        add_filter('default_hidden_columns', [$this, 'set_default_hidden_columns'], 10, 2);
    }

    public function register_taxonomy(): void
    {
        register_taxonomy($this->taxonomy, 'shop_order', [
            'label' => 'Department',
            'public' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'hierarchical' => false,
            'rewrite' => false,
            // HPOS support
            'object_type' => ['shop_order'],
            'supports' => ['custom_order_tables'],
        ]);
    }

    /**
     * Enqueue necessary scripts and styles for admin
     */
    public function enqueue_admin_scripts(): void
    {
        // Get the taxonomy
        $screen = get_current_screen();
        if (!$screen || $screen->taxonomy !== $this->taxonomy) {
            return;
        }

        // Ensure WooCommerce is available
        if (!function_exists('WC')) {
            return;
        }

        // Enqueue WooCommerce Select2 dependencies
        wp_enqueue_style('woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC()->version);
        wp_enqueue_style('select2', WC()->plugin_url() . '/assets/css/select2.css', array(), WC()->version);

        wp_enqueue_script('jquery');
        wp_enqueue_script('select2', WC()->plugin_url() . '/assets/js/select2/select2.full.min.js', array('jquery'), WC()->version, true);
        wp_enqueue_script('wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select.min.js', array('jquery', 'select2'), WC()->version, true);

        // Enqueue our custom admin styles
        wp_enqueue_style(
            'runthings-order-departments-admin',
            RUNTHINGS_WC_ORDER_DEPARTMENTS_URL . 'assets/css/admin.css',
            array('woocommerce_admin_styles'),
            RUNTHINGS_WC_ORDER_DEPARTMENTS_VERSION
        );

        // Enqueue our custom admin script
        wp_enqueue_script(
            'runthings-order-departments-admin',
            RUNTHINGS_WC_ORDER_DEPARTMENTS_URL . 'assets/js/admin.js',
            array('jquery', 'select2', 'wc-enhanced-select'),
            RUNTHINGS_WC_ORDER_DEPARTMENTS_VERSION,
            true
        );

        // Localize script with data needed by JavaScript
        wp_localize_script('runthings-order-departments-admin', 'runthingsOrderDepartments', array(
            'selectPlaceholder' => __('Select...', 'runthings-wc-order-departments'),
            'metaPrefix' => $this->meta_prefix,
        ));
    }
    
    /**
     * Add custom fields to the "Add New Term" form
     */
    public function add_term_fields(): void
    {
        ?>
        <div class="form-field">
            <label for="<?php echo esc_attr($this->meta_prefix); ?>department_emails"><?php esc_html_e('Email Addresses', 'runthings-wc-order-departments'); ?></label>
            <input type="text" name="<?php echo esc_attr($this->meta_prefix); ?>department_emails" id="<?php echo esc_attr($this->meta_prefix); ?>department_emails" value="" />
            <p class="description"><?php esc_html_e('Separate multiple emails with a semicolon (;)', 'runthings-wc-order-departments'); ?></p>
        </div>

        <div class="form-field">
            <label for="<?php echo esc_attr($this->meta_prefix); ?>department_categories"><?php esc_html_e('Department Categories', 'runthings-wc-order-departments'); ?></label>
            <select name="<?php echo esc_attr($this->meta_prefix); ?>department_categories[]" id="<?php echo esc_attr($this->meta_prefix); ?>department_categories" class="wc-enhanced-select runthings-select2-field" multiple="multiple" >
                <?php
                // Get product categories
                $product_categories = get_terms(array(
                    'taxonomy' => 'product_cat',
                    'hide_empty' => false,
                ));

                // Output options
                if (!empty($product_categories) && !is_wp_error($product_categories)) {
                    foreach ($product_categories as $category) {
                        echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
                    }
                }
                ?>
            </select>
            <p class="description"><?php esc_html_e('Select product categories associated with this department. Note: Only the specifically selected categories will be matched - subcategories are not automatically included.', 'runthings-wc-order-departments'); ?></p>
        </div>

        <div class="form-field">
            <label for="<?php echo esc_attr($this->meta_prefix); ?>selected_products"><?php esc_html_e('Department Products', 'runthings-wc-order-departments'); ?></label>
            <select name="<?php echo esc_attr($this->meta_prefix); ?>selected_products[]" id="<?php echo esc_attr($this->meta_prefix); ?>selected_products" class="wc-enhanced-select runthings-select2-field" multiple="multiple" >
                <?php
                // Query for products
                $products = wc_get_products(array(
                    'limit' => -1,
                    'status' => 'publish',
                ));

                if (!empty($products)) {
                    foreach ($products as $product) {
                        echo '<option value="' . esc_attr($product->get_id()) . '">' . esc_html($product->get_name()) . ' (#' . esc_html($product->get_id()) . ')</option>';
                    }
                }
                ?>
            </select>
            <p class="description"><?php esc_html_e('Select specific products associated with this department. Use this for products not covered by the selected categories above.', 'runthings-wc-order-departments'); ?></p>
        </div>


        <?php
    }
    
    /**
     * Add custom fields to the "Edit Term" form
     */
    public function edit_term_fields($term): void
    {
        $term_id = $term->term_id;
        $emails = get_term_meta($term_id, $this->meta_prefix . 'department_emails', true);
        $selected_categories = get_term_meta($term_id, $this->meta_prefix . 'department_categories', true);
        $selected_products = get_term_meta($term_id, $this->meta_prefix . 'selected_products', true);
        
        if (!is_array($selected_categories)) {
            $selected_categories = array();
        }
        
        if (!is_array($selected_products)) {
            $selected_products = array();
        }
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="<?php echo esc_attr($this->meta_prefix); ?>department_emails"><?php esc_html_e('Email Addresses', 'runthings-wc-order-departments'); ?></label>
            </th>
            <td>
                <input type="text" name="<?php echo esc_attr($this->meta_prefix); ?>department_emails" id="<?php echo esc_attr($this->meta_prefix); ?>department_emails" value="<?php echo esc_attr($emails); ?>" />
                <p class="description"><?php esc_html_e('Separate multiple emails with a semicolon (;)', 'runthings-wc-order-departments'); ?></p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="<?php echo esc_attr($this->meta_prefix); ?>department_categories"><?php esc_html_e('Department Categories', 'runthings-wc-order-departments'); ?></label>
            </th>
            <td>
                <select name="<?php echo esc_attr($this->meta_prefix); ?>department_categories[]" id="<?php echo esc_attr($this->meta_prefix); ?>department_categories" class="wc-enhanced-select runthings-select2-field" multiple="multiple" >
                    <?php
                    // Get product categories
                    $product_categories = get_terms(array(
                        'taxonomy' => 'product_cat',
                        'hide_empty' => false,
                    ));

                    // Output options
                    if (!empty($product_categories) && !is_wp_error($product_categories)) {
                        foreach ($product_categories as $category) {
                            $selected = in_array($category->term_id, $selected_categories) ? 'selected="selected"' : '';
                            echo '<option value="' . esc_attr($category->term_id) . '" ' . esc_attr($selected) . '>' . esc_html($category->name) . '</option>';
                        }
                    }
                    ?>
                </select>
                <p class="description"><?php esc_html_e('Select product categories associated with this department. Note: Only the specifically selected categories will be matched - subcategories are not automatically included.', 'runthings-wc-order-departments'); ?></p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="<?php echo esc_attr($this->meta_prefix); ?>selected_products"><?php esc_html_e('Department Products', 'runthings-wc-order-departments'); ?></label>
            </th>
            <td>
                <select name="<?php echo esc_attr($this->meta_prefix); ?>selected_products[]" id="<?php echo esc_attr($this->meta_prefix); ?>selected_products" class="wc-enhanced-select runthings-select2-field" multiple="multiple" >
                    <?php
                    // Query for products
                    $products = wc_get_products(array(
                        'limit' => -1,
                        'status' => 'publish',
                    ));

                    if (!empty($products)) {
                        foreach ($products as $product) {
                            $selected = in_array($product->get_id(), $selected_products) ? 'selected="selected"' : '';
                            echo '<option value="' . esc_attr($product->get_id()) . '" ' . esc_attr($selected) . '>' . esc_html($product->get_name()) . ' (#' . esc_html($product->get_id()) . ')</option>';
                        }
                    }
                    ?>
                </select>
                <p class="description"><?php esc_html_e('Select specific products associated with this department. Use this for products not covered by the selected categories above.', 'runthings-wc-order-departments'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save custom fields from term forms
     */
    public function save_term_fields($term_id): void
    {
        // Check user capabilities first
        if (!current_user_can('manage_product_terms')) {
            return;
        }

        // Skip processing for quick edit operations since we don't have quick edit fields
        if (isset($_POST['action']) && $_POST['action'] === 'inline-save-tax') {
            return;
        }

        // Verify nonce for security using WordPress standard method
        // WordPress uses different nonce actions for creating vs editing terms
        if (isset($_POST['action']) && $_POST['action'] === 'add-tag') {
            // For new terms: check 'add-tag' action
            check_admin_referer('add-tag', '_wpnonce_add-tag');
        } else {
            // For editing terms: check 'update-tag_ID' action
            check_admin_referer('update-tag_' . $term_id);
        }

        // Save email addresses
        if (isset($_POST[$this->meta_prefix . 'department_emails'])) {
            $emails = sanitize_text_field(wp_unslash($_POST[$this->meta_prefix . 'department_emails']));
            update_term_meta(
                $term_id,
                $this->meta_prefix . 'department_emails',
                $emails
            );
        }

        // Save department categories
        if (isset($_POST[$this->meta_prefix . 'department_categories'])) {
            $categories = array_map('absint', (array) wp_unslash($_POST[$this->meta_prefix . 'department_categories']));
            update_term_meta(
                $term_id,
                $this->meta_prefix . 'department_categories',
                $categories
            );
        } else {
            // If no categories are selected, save an empty array
            update_term_meta(
                $term_id,
                $this->meta_prefix . 'department_categories',
                array()
            );
        }

        // Save selected products
        if (isset($_POST[$this->meta_prefix . 'selected_products'])) {
            $products = array_map('absint', (array) wp_unslash($_POST[$this->meta_prefix . 'selected_products']));
            update_term_meta(
                $term_id,
                $this->meta_prefix . 'selected_products',
                $products
            );
        } else {
            // If no products are selected, save an empty array
            update_term_meta(
                $term_id,
                $this->meta_prefix . 'selected_products',
                array()
            );
        }
    }

    /**
     * Add custom columns to the taxonomy list table
     */
    public function add_custom_columns($columns)
    {
        // Only add columns for our taxonomy
        if (!$this->is_current_taxonomy_screen() && !$this->is_ajax_for_our_taxonomy()) {
            return $columns;
        }

        // Insert new columns before the 'posts' column
        $new_columns = array();
        foreach ($columns as $key => $value) {
            if ($key === 'posts') {
                $new_columns['department_emails'] = __('Email Addresses', 'runthings-wc-order-departments');
                $new_columns['department_categories'] = __('Categories', 'runthings-wc-order-departments');
                $new_columns['department_products'] = __('Products', 'runthings-wc-order-departments');
            }
            $new_columns[$key] = $value;
        }

        return $new_columns;
    }

    /**
     * Display content for custom columns
     */
    public function display_custom_column($content, $column_name, $term_id)
    {
        // Only handle our taxonomy
        if (!$this->is_current_taxonomy_screen() && !$this->is_ajax_for_our_taxonomy()) {
            return $content;
        }

        switch ($column_name) {
            case 'department_emails':
                return $this->display_emails_column($term_id);

            case 'department_categories':
                return $this->display_categories_column($term_id);

            case 'department_products':
                return $this->display_products_column($term_id);
        }

        return $content;
    }

    /**
     * Display emails column content
     */
    private function display_emails_column($term_id)
    {
        $emails = get_term_meta($term_id, $this->meta_prefix . 'department_emails', true);

        if (empty($emails)) {
            return '<span class="na">—</span>';
        }

        // Split emails and format them
        $email_list = array_map('trim', explode(';', $emails));
        $email_count = count($email_list);

        if ($email_count === 1) {
            return '<span title="' . esc_attr($emails) . '">' . esc_html($email_list[0]) . '</span>';
        }

        // Show first email with count if multiple
        $first_email = $email_list[0];
        $remaining = $email_count - 1;

        return sprintf(
            '<span title="%s">%s <span class="count">+%d more</span></span>',
            esc_attr($emails),
            esc_html($first_email),
            $remaining
        );
    }

    /**
     * Display categories column content
     */
    private function display_categories_column($term_id)
    {
        $category_ids = get_term_meta($term_id, $this->meta_prefix . 'department_categories', true);

        if (empty($category_ids) || !is_array($category_ids)) {
            return '<span class="na">—</span>';
        }

        $category_names = array();
        foreach ($category_ids as $cat_id) {
            $category = get_term($cat_id, 'product_cat');
            if ($category && !is_wp_error($category)) {
                $category_names[] = $category->name;
            }
        }

        if (empty($category_names)) {
            return '<span class="na">—</span>';
        }

        $category_count = count($category_names);

        if ($category_count === 1) {
            return esc_html($category_names[0]);
        }

        // Show first category with count if multiple
        $first_category = $category_names[0];
        $remaining = $category_count - 1;
        $all_categories = implode(', ', $category_names);

        return sprintf(
            '<span title="%s">%s <span class="count">+%d more</span></span>',
            esc_attr($all_categories),
            esc_html($first_category),
            $remaining
        );
    }

    /**
     * Display products column content
     */
    private function display_products_column($term_id)
    {
        $product_ids = get_term_meta($term_id, $this->meta_prefix . 'selected_products', true);

        if (empty($product_ids) || !is_array($product_ids)) {
            return '<span class="na">—</span>';
        }

        $product_count = count($product_ids);

        if ($product_count === 0) {
            return '<span class="na">—</span>';
        }

        // Get first product name for display
        $first_product = get_post($product_ids[0]);
        $first_product_name = $first_product ? $first_product->post_title : __('Unknown Product', 'runthings-wc-order-departments');

        if ($product_count === 1) {
            return esc_html($first_product_name);
        }

        // Show count with tooltip showing first product
        return sprintf(
            '<span title="%s">%s</span>',
            /* translators: %s: Product name */
            esc_attr(sprintf(__('First product: %s', 'runthings-wc-order-departments'), $first_product_name)),
            /* translators: %d: Number of products */
            sprintf(_n('%d product', '%d products', $product_count, 'runthings-wc-order-departments'), $product_count)
        );
    }

    /**
     * Set default hidden columns for our taxonomy
     */
    public function set_default_hidden_columns($hidden, $screen)
    {
        // Only apply to our taxonomy screen
        if ($screen && $screen->taxonomy === $this->taxonomy) {
            // Hide categories and products columns by default, show only emails
            $hidden = array_merge($hidden, array(
                'department_categories',
                'department_products'
            ));
        }

        return $hidden;
    }

    /**
     * Check if this is an AJAX request for our taxonomy
     */
    private function is_ajax_for_our_taxonomy()
    {
        // Only check during AJAX requests
        if (!wp_doing_ajax()) {
            return false;
        }

        // Safely check if this is for our taxonomy
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Only checking taxonomy context, not processing data
        return isset($_POST['taxonomy']) && $_POST['taxonomy'] === $this->taxonomy;
    }

    /**
     * Check if we're on the current taxonomy screen
     */
    private function is_current_taxonomy_screen()
    {
        $screen = get_current_screen();
        return $screen && $screen->taxonomy === $this->taxonomy;
    }
}

