<?php

/**
 * Prototype CRUD
 *
 * @package     PrototypeCRUD
 * @author      Henri Susanto
 * @copyright   2022 Henri Susanto
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Prototype CRUD
 * Plugin URI:  https://github.com/susantohenri
 * Description: This plugin contains prototype for CRUD
 * Version:     1.0.0
 * Author:      Henri Susanto
 * Author URI:  https://github.com/susantohenri
 * Text Domain: prototype-crud
 * License:     GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

$entity = array(
    'name' => 'todo',
    'fields' => array(
        'activity',
        'status' => array (
            'label' => 'Status',
            'options' => array(
                array('text' => 'To Do', 'value' => 'todo'),
                array('text' => 'Done', 'value' => 'done')
            )
        )
    )
);

register_activation_hook(__FILE__, function () use ($entity) {
    global $wpdb;
    $fields = '';
    foreach ($entity['fields'] as $field) $fields .= "`$field` varchar(255) NOT NULL,";
    $ddl = "
        CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}{$entity['name']}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `uuid` varchar(36) NOT NULL,
            `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            $fields
            PRIMARY KEY (`id`)
        ) {$wpdb->get_charset_collate()} ;
    ";
    $wpdb->query($ddl);
});

register_deactivation_hook(__FILE__, function () {
    global $wpdb;
    global $entity;
    $wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}{$entity['name']}`");
});

function form () {

    global $entity;

    $fields = '';
    foreach ($entity['fields'] as $field) {
        $label = isset($field['label']) ? $field['label'] : ucfirst($field);
        $fields .= "<label for='{$field}'>{$label}</label>";
        $type = isset($field['type']) ? $field['type'] : 'text';
        if (isset($field['options'])) {
            $options = '';
            foreach ($field['options'] as $option) $options .= "<option value='{$option['value']}' >{$option['text']}</option>";
            $fields .= "<select id='country' name='country'>{$options}</select>";
        } else $fields .= "<input type='{$type}' name='{$field}'>";
    }

    return "
        <style type='text/css'>

        .prototype-crud-container input[type=text],
        .prototype-crud-container select,
        .prototype-crud-container textarea {
            width: 100%; /* Full width */
            padding: 12px; /* Some padding */ 
            border: 1px solid #ccc; /* Gray border */
            border-radius: 4px; /* Rounded borders */
            box-sizing: border-box; /* Make sure that padding and width stays in place */
            margin-top: 6px; /* Add a top margin */
            margin-bottom: 16px; /* Bottom margin */
            resize: vertical /* Allow the user to vertically resize the textarea (not horizontally) */
        }
        
        /* Style the submit button with a specific background color etc */
        .prototype-crud-container input[type=submit] {
            background-color: #04AA6D;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        /* When moving the mouse over the submit button, add a darker green color */
        .prototype-crud-container input[type=submit]:hover {
            background-color: #45a049;
        }
        
        /* Add a background color and some padding around the form */
        .prototype-crud-container {
            border-radius: 5px;
            background-color: #f2f2f2;
            padding: 20px;
        }
        </style>
        <div class='prototype-crud-container'>
            <form action=''>
        
                {$fields}
        
                <input type='submit' value='Submit'>
        
            </form>
        </div>
    ";
}

add_shortcode('prototype-crud-todo-form', 'form');

add_action('rest_api_init', function () use ($config) {
    register_rest_route('prototype-crud/v1', "/world", [
        "methods" => "GET",
        "permission_callback" => "__return_true",
        "callback" => function () {
        }
    ]);
});