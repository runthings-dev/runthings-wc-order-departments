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
        
        // Enqueue scripts and styles for admin - use more specific hooks
        add_action('admin_print_scripts-edit-tags.php', [$this, 'enqueue_admin_scripts']);
        add_action('admin_print_scripts-term.php', [$this, 'enqueue_admin_scripts']);
        add_action('admin_footer-edit-tags.php', [$this, 'admin_footer_scripts']);
        add_action('admin_footer-term.php', [$this, 'admin_footer_scripts']);
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

        // Enqueue Select2 scripts
        wp_enqueue_style('woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css');
        wp_enqueue_style('select2', WC()->plugin_url() . '/assets/css/select2.css');
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('select2', WC()->plugin_url() . '/assets/js/select2/select2.full.min.js', array('jquery'));
        wp_enqueue_script('wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select.min.js', array('jquery', 'select2'));
    }
    
    /**
     * Add inline scripts to the admin footer
     */
    public function admin_footer_scripts(): void
    {
        // Get the taxonomy from the URL
        $screen = get_current_screen();
        if (!$screen || $screen->taxonomy !== $this->taxonomy) {
            return;
        }
        
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                console.log('Initializing Select2 for department categories');
                
                // Force initialize Select2 after page load
                $('.wc-enhanced-select').each(function() {
                    if ($(this).data('select2')) {
                        $(this).select2('destroy');
                    }
                    
                    $(this).select2({
                        placeholder: '<?php echo esc_js(__('Select...', 'runthings-wc-order-departments')); ?>',
                        allowClear: true,
                        width: '100%'
                    });
                });
            });
        </script>
        <?php
    }
    
    /**
     * Add custom fields to the "Add New Term" form
     */
    public function add_term_fields(): void
    {
        ?>
        <div class="form-field">
            <label for="<?php echo $this->meta_prefix; ?>department_emails"><?php _e('Email Addresses', 'runthings-wc-order-departments'); ?></label>
            <input type="text" name="<?php echo $this->meta_prefix; ?>department_emails" id="<?php echo $this->meta_prefix; ?>department_emails" value="" />
            <p class="description"><?php _e('Separate multiple emails with a semicolon (;)', 'runthings-wc-order-departments'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="<?php echo $this->meta_prefix; ?>department_categories"><?php _e('Department Categories', 'runthings-wc-order-departments'); ?></label>
            <select name="<?php echo $this->meta_prefix; ?>department_categories[]" id="<?php echo $this->meta_prefix; ?>department_categories" class="wc-enhanced-select" multiple="multiple" style="width: 100%;">
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
            <p class="description"><?php _e('Select product categories associated with this department', 'runthings-wc-order-departments'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="<?php echo $this->meta_prefix; ?>selected_products"><?php _e('Department Products', 'runthings-wc-order-departments'); ?></label>
            <select name="<?php echo $this->meta_prefix; ?>selected_products[]" id="<?php echo $this->meta_prefix; ?>selected_products" class="wc-enhanced-select" multiple="multiple" style="width: 100%;">
                <?php
                // Query for products
                $products = wc_get_products(array(
                    'limit' => -1,
                    'status' => 'publish',
                ));
                
                if (!empty($products)) {
                    foreach ($products as $product) {
                        echo '<option value="' . esc_attr($product->get_id()) . '">' . esc_html($product->get_name()) . ' (#' . $product->get_id() . ')</option>';
                    }
                }
                ?>
            </select>
            <p class="description"><?php _e('Select specific products associated with this department', 'runthings-wc-order-departments'); ?></p>
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
                <label for="<?php echo $this->meta_prefix; ?>department_emails"><?php _e('Email Addresses', 'runthings-wc-order-departments'); ?></label>
            </th>
            <td>
                <input type="text" name="<?php echo $this->meta_prefix; ?>department_emails" id="<?php echo $this->meta_prefix; ?>department_emails" value="<?php echo esc_attr($emails); ?>" />
                <p class="description"><?php _e('Separate multiple emails with a semicolon (;)', 'runthings-wc-order-departments'); ?></p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="<?php echo $this->meta_prefix; ?>department_categories"><?php _e('Department Categories', 'runthings-wc-order-departments'); ?></label>
            </th>
            <td>
                <select name="<?php echo $this->meta_prefix; ?>department_categories[]" id="<?php echo $this->meta_prefix; ?>department_categories" class="wc-enhanced-select" multiple="multiple" style="width: 100%;">
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
                            echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                        }
                    }
                    ?>
                </select>
                <p class="description"><?php _e('Select product categories associated with this department', 'runthings-wc-order-departments'); ?></p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row">
                <label for="<?php echo $this->meta_prefix; ?>selected_products"><?php _e('Department Products', 'runthings-wc-order-departments'); ?></label>
            </th>
            <td>
                <select name="<?php echo $this->meta_prefix; ?>selected_products[]" id="<?php echo $this->meta_prefix; ?>selected_products" class="wc-enhanced-select" multiple="multiple" style="width: 100%;">
                    <?php
                    // Query for products
                    $products = wc_get_products(array(
                        'limit' => -1,
                        'status' => 'publish',
                    ));
                    
                    if (!empty($products)) {
                        foreach ($products as $product) {
                            $selected = in_array($product->get_id(), $selected_products) ? 'selected="selected"' : '';
                            echo '<option value="' . esc_attr($product->get_id()) . '" ' . $selected . '>' . esc_html($product->get_name()) . ' (#' . $product->get_id() . ')</option>';
                        }
                    }
                    ?>
                </select>
                <p class="description"><?php _e('Select specific products associated with this department', 'runthings-wc-order-departments'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save custom fields from term forms
     */
    public function save_term_fields($term_id): void
    {
        // Save email addresses
        if (isset($_POST[$this->meta_prefix . 'department_emails'])) {
            update_term_meta(
                $term_id,
                $this->meta_prefix . 'department_emails',
                sanitize_text_field($_POST[$this->meta_prefix . 'department_emails'])
            );
        }
        
        // Save department categories
        if (isset($_POST[$this->meta_prefix . 'department_categories'])) {
            $categories = array_map('absint', (array) $_POST[$this->meta_prefix . 'department_categories']);
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
            $products = array_map('absint', (array) $_POST[$this->meta_prefix . 'selected_products']);
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
}

