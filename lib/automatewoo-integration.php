<?php

namespace RunthingsWCOrderDepartments;

class AutomateWooIntegration
{
    public function __construct()
    {
        // Register the custom action only if AutomateWoo is active
        add_action('automatewoo/actions', [$this, 'register_department_action']);
    }

    /**
     * Register a custom action for AutomateWoo
     */
    public function register_department_action($actions)
    {
        if (!class_exists('AutomateWoo\Action')) {
            return $actions;
        }

        require_once RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR . 'lib/actions/aw-action-set-order-department.php';

        $actions['runthings_set_order_department'] = 'RunthingsWCOrderDepartments\Actions\Set_Order_Department';

        return $actions;
    }
}
