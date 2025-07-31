<?php

namespace RunthingsWCOrderDepartments;

if (!defined('WPINC')) {
    die;
}

/**
 * Handles the plugin settings page and options
 */
class Settings
{
    private $option_group = 'runthings_wc_order_departments';

    private $option_name = 'runthings_wc_order_departments_settings';

    private $page_slug = 'runthings-wc-order-departments-settings';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    /**
     * Add settings page to WordPress admin menu
     */
    public function add_settings_page()
    {
        add_options_page(
            __('Order Departments Settings', 'runthings-wc-order-departments'),
            __('Order Departments', 'runthings-wc-order-departments'),
            'manage_options',
            $this->page_slug,
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings, sections, and fields
     */
    public function register_settings()
    {
        // Register the main settings option
        register_setting(
            $this->option_group,
            $this->option_name,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => $this->get_default_settings(),
            ]
        );

        // Add main settings section
        add_settings_section(
            'email_reply_to_section',
            __('Customer Email Reply-To Settings', 'runthings-wc-order-departments'),
            [$this, 'render_email_reply_to_section'],
            $this->page_slug
        );

        // Enable/disable checkbox
        add_settings_field(
            'enable_reply_to_override',
            __('Override Reply-To with Department Emails', 'runthings-wc-order-departments'),
            [$this, 'render_enable_checkbox'],
            $this->page_slug,
            'email_reply_to_section'
        );

        // Multi-department handling radio buttons
        add_settings_field(
            'multi_dept_mode',
            __('For Multi-Department Orders', 'runthings-wc-order-departments'),
            [$this, 'render_multi_dept_mode'],
            $this->page_slug,
            'email_reply_to_section'
        );
    }

    /**
     * Get default settings
     */
    private function get_default_settings()
    {
        return [
            'enable_reply_to_override' => true,
            'multi_dept_mode' => 'use_all_emails',
        ];
    }

    /**
     * Get current settings with defaults
     */
    public function get_settings()
    {
        $settings = get_option($this->option_name, []);
        return wp_parse_args($settings, $this->get_default_settings());
    }

    /**
     * Sanitize settings before saving
     */
    public function sanitize_settings($input)
    {
        $sanitized = [];

        // Enable checkbox
        $sanitized['enable_reply_to_override'] = !empty($input['enable_reply_to_override']);

        // Multi-department mode
        $valid_modes = ['use_all_emails', 'fallback_to_wc'];
        $sanitized['multi_dept_mode'] = in_array($input['multi_dept_mode'], $valid_modes, true)
            ? $input['multi_dept_mode']
            : 'use_all_emails';

        return $sanitized;
    }

    /**
     * Render the main settings page
     */
    public function render_settings_page()
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->option_group);
                do_settings_sections($this->page_slug);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the email reply-to section description
     */
    public function render_email_reply_to_section()
    {
        $wc_settings_url = admin_url('admin.php?page=wc-settings&tab=email');
        echo '<p>' . sprintf(
            esc_html__('Configure how customer email replies are routed based on order departments. When disabled, emails use the %s.', 'runthings-wc-order-departments'),
            '<a href="' . esc_url($wc_settings_url) . '">' . esc_html__('WooCommerce email settings', 'runthings-wc-order-departments') . '</a>'
        ) . '</p>';
    }

    /**
     * Render the enable checkbox
     */
    public function render_enable_checkbox()
    {
        $settings = $this->get_settings();
        $checked = $settings['enable_reply_to_override'];
        ?>
        <label>
            <input type="checkbox"
                   name="<?php echo esc_attr($this->option_name); ?>[enable_reply_to_override]"
                   value="1"
                   <?php checked($checked); ?> />
            <?php esc_html_e('Override reply-to with department email addresses', 'runthings-wc-order-departments'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('When enabled, customer emails will use department email addresses in the reply-to field instead of the default WooCommerce setting.', 'runthings-wc-order-departments'); ?>
        </p>
        <?php
    }

    /**
     * Render the multi-department mode radio buttons
     */
    public function render_multi_dept_mode()
    {
        $settings = $this->get_settings();
        $current_mode = $settings['multi_dept_mode'];
        $wc_settings_url = admin_url('admin.php?page=wc-settings&tab=email');
        ?>
        <fieldset id="multi-dept-mode-fieldset">
            <legend class="screen-reader-text"><?php esc_html_e('Multi-Department Mode', 'runthings-wc-order-departments'); ?></legend>

            <label>
                <input type="radio"
                       name="<?php echo esc_attr($this->option_name); ?>[multi_dept_mode]"
                       value="use_all_emails"
                       <?php checked($current_mode, 'use_all_emails'); ?> />
                <?php esc_html_e('Use all department emails', 'runthings-wc-order-departments'); ?>
            </label>
            <p class="description">
                <?php esc_html_e('Add all unique department email addresses to the reply-to field. All departments will receive customer replies and need to coordinate who responds.', 'runthings-wc-order-departments'); ?>
            </p>
            <br>

            <label>
                <input type="radio"
                       name="<?php echo esc_attr($this->option_name); ?>[multi_dept_mode]"
                       value="fallback_to_wc"
                       <?php checked($current_mode, 'fallback_to_wc'); ?> />
                <?php esc_html_e('Skip override - fall back to WooCommerce default', 'runthings-wc-order-departments'); ?>
            </label>
            <p class="description">
                <?php
                printf(
                    esc_html__('Use the default reply-to address from %s instead of department emails when multiple unique email addresses are involved.', 'runthings-wc-order-departments'),
                    '<a href="' . esc_url($wc_settings_url) . '">' . esc_html__('WooCommerce email settings', 'runthings-wc-order-departments') . '</a>'
                );
                ?>
            </p>
        </fieldset>
        <p class="description" id="multi-dept-note">
            <strong><?php esc_html_e('Note:', 'runthings-wc-order-departments'); ?></strong>
            <?php esc_html_e('Multi-department is determined by the number of unique email addresses, not the number of departments. If multiple departments share the same email address, they are treated as a single department.', 'runthings-wc-order-departments'); ?>
        </p>
        <?php
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook)
    {
        // Only load on our settings page
        if ($hook !== 'settings_page_' . $this->page_slug) {
            return;
        }

        wp_enqueue_script(
            'runthings-wc-order-departments-settings',
            RUNTHINGS_WC_ORDER_DEPARTMENTS_URL . 'assets/js/settings.js',
            ['jquery'],
            RUNTHINGS_WC_ORDER_DEPARTMENTS_VERSION,
            true
        );
    }
}
