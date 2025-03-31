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
        <?php
    }
    
    /**
     * Add custom fields to the "Edit Term" form
     */
    public function edit_term_fields($term): void
    {
        $term_id = $term->term_id;
        $emails = get_term_meta($term_id, $this->meta_prefix . 'department_emails', true);
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
        <?php
    }
    
    /**
     * Save custom fields from term forms
     */
    public function save_term_fields($term_id): void
    {
        if (isset($_POST[$this->meta_prefix . 'department_emails'])) {
            update_term_meta(
                $term_id,
                $this->meta_prefix . 'department_emails',
                sanitize_text_field($_POST[$this->meta_prefix . 'department_emails'])
            );
        }
    }
}

