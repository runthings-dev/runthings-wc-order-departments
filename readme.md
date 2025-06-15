# WooCommerce Order Departments

Automatically assign orders to departments based on products and categories, with email routing and AutomateWoo integration.

## Description

WooCommerce Order Departments enables large organizations to automatically route orders to the appropriate departments based on the products or categories in each order. Perfect for companies with multiple departments handling different product lines.

## Features

- **Automatic Department Assignment**: Orders are automatically assigned to departments based on products/categories
- **Email Routing**: Route order emails to department-specific email addresses
- **Admin Filtering**: Filter orders by department in WooCommerce admin
- **Quick Access Menus**: Direct links to orders for each department
- **AutomateWoo Integration**: Complete set of actions, triggers, and rules for workflow automation
- **HPOS Compatible**: Full support for WooCommerce High-Performance Order Storage

## AutomateWoo Integration

### Actions

- **Set Order Department**: Replace all existing departments with one department
- **Add Order Department**: Add a department while keeping existing ones
- **Remove Order Department**: Remove a specific department from an order
- **Clear Order Departments**: Remove all departments from an order

### Triggers

- **Order Department Added**: Fires when a department is added to an order
- **Order Department Removed**: Fires when a department is removed from an order
- **Order Department Changed**: Fires when departments are modified in any way

### Rules

- **Order Has Department**: Check if order has specific department(s)
- **Order Department Count**: Check the number of departments assigned
- **Order Department Is**: Check if order's departments exactly match a set

## Use Cases

- **Sales & Technical Support**: Route orders containing software to Technical, hardware to Sales
- **Multi-location Fulfillment**: Route orders to appropriate warehouses/locations
- **Specialized Teams**: Route complex products to specialist departments
- **CRM Integration**: Trigger department-specific workflows in external systems

## Installation

1. Upload the plugin files to `/wp-content/plugins/runthings-wc-order-departments/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to WooCommerce > Order Departments to configure departments
4. Assign products/categories to departments
5. Set up email addresses for each department

## Configuration

### Setting Up Departments

1. Navigate to **WooCommerce > Order Departments**
2. Add new departments with:
   - Department name
   - Email addresses (semicolon-separated)
   - Associated product categories
   - Specific products

### Email Routing

Orders will automatically send emails to the department's configured email addresses based on the assigned department(s).

### AutomateWoo Workflows

Create workflows using the department triggers and rules to:

- Send notifications to external systems
- Create tickets in support systems
- Update CRM records
- Trigger fulfillment processes

## Requirements

- WordPress 5.0+
- WooCommerce 6.0+
- PHP 7.4+
- AutomateWoo (optional, for workflow automation)

## Changelog

### 1.0.0

- Initial release
- Automatic department assignment
- Email routing
- Admin filtering and quick access
- Complete AutomateWoo integration
- HPOS compatibility

## Support

For support and feature requests, please check out the github repository:

[]()

## License

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, see [http://www.gnu.org/licenses/gpl-3.0.html](http://www.gnu.org/licenses/gpl-3.0.html).
