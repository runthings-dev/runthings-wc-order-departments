<?php

namespace RunthingsWCOrderDepartments;

class AutomateWooIntegration
{
    public function __construct()
    {
        // Register the custom action only if AutomateWoo is active
        add_action('automatewoo/actions', [$this, 'register_department_actions']);
    }

    /**
     * Register custom actions for AutomateWoo
     */
    public function register_department_actions($actions)
    {
        if (!class_exists('AutomateWoo\Action')) {
            return $actions;
        }

        require_once RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR . 'lib/actions/aw-action-set-order-department.php';
        require_once RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR . 'lib/actions/aw-action-add-order-department.php';
        require_once RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR . 'lib/actions/aw-action-remove-order-department.php';
        require_once RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR . 'lib/actions/aw-action-clear-order-departments.php';

        $actions['runthings_set_order_department'] = 'RunthingsWCOrderDepartments\Actions\Set_Order_Department';
        $actions['runthings_add_order_department'] = 'RunthingsWCOrderDepartments\Actions\Add_Order_Department';
        $actions['runthings_remove_order_department'] = 'RunthingsWCOrderDepartments\Actions\Remove_Order_Department';
        $actions['runthings_clear_order_departments'] = 'RunthingsWCOrderDepartments\Actions\Clear_Order_Departments';

        return $actions;
    }
}
