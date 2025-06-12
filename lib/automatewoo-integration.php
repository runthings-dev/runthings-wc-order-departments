<?php

namespace RunthingsWCOrderDepartments;

class AutomateWooIntegration
{
    public function __construct()
    {
        // Register custom actions and triggers only if AutomateWoo is active
        add_action('automatewoo/actions', [$this, 'register_department_actions']);
        add_action('automatewoo/triggers', [$this, 'register_department_triggers']);
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

    /**
     * Register custom triggers for AutomateWoo
     */
    public function register_department_triggers($triggers)
    {
        if (!class_exists('AutomateWoo\Trigger')) {
            return $triggers;
        }

        require_once RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR . 'lib/triggers/aw-trigger-order-department-added.php';
        require_once RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR . 'lib/triggers/aw-trigger-order-department-removed.php';
        require_once RUNTHINGS_WC_ORDER_DEPARTMENTS_DIR . 'lib/triggers/aw-trigger-order-department-changed.php';

        $triggers['runthings_order_department_added'] = 'RunthingsWCOrderDepartments\Triggers\Order_Department_Added';
        $triggers['runthings_order_department_removed'] = 'RunthingsWCOrderDepartments\Triggers\Order_Department_Removed';
        $triggers['runthings_order_department_changed'] = 'RunthingsWCOrderDepartments\Triggers\Order_Department_Changed';

        return $triggers;
    }
}
