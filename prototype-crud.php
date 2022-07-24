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

$plugin = array(
    'name' => 'prototype_crud',
    'entity' => array(
        'name' => 'todo',
        'fields' => array(
            'activity' => array(),
            'status' => array (
                'options' => array(
                    array('text' => 'To Do', 'value' => 'todo'),
                    array('text' => 'Done', 'value' => 'done')
                )
            )
        )
    )
);

register_activation_hook(__FILE__, function () use ($plugin) {
    global $wpdb;
    $fields = '';
    foreach ($plugin['entity']['fields'] as $field => $attributes) $fields .= "`$field` varchar(255) NOT NULL,";
    $ddl = "
        CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}{$plugin['entity']['name']}` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
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
    global $plugin;
    $wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}{$plugin['entity']['name']}`");
});

function form ($plugin) {
    $endpoint = site_url("wp-json/{$plugin['name']}/v1/create_{$plugin['entity']['name']}");

    $fields = '';
    foreach ($plugin['entity']['fields'] as $field => $attributes) {
        $label = isset($attributes['label']) ? $attributes['label'] : ucfirst($field);
        $fields .= "<label for='{$attributes}'>{$label}</label>";
        $type = isset($attributes['type']) ? $attributes['type'] : 'text';
        if (isset($attributes['options'])) {
            $options = '';
            foreach ($attributes['options'] as $option) $options .= "<option value='{$option['value']}' >{$option['text']}</option>";
            $fields .= "<select name='{$field}'>{$options}</select>";
        } else $fields .= "<input type='{$type}' name='{$field}'>";
    }
    $fields .= "<button id='{$plugin['name']}_submit_button' onclick='submit_{$plugin['entity']['name']}()'>Submit</button>";

    return "
        <style type='text/css'>
        #{$plugin['name']}_container input[type=text],
        #{$plugin['name']}_container select,
        #{$plugin['name']}_container textarea {
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
        #{$plugin['name']}_container button {
            background-color: #04AA6D;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        /* Add a background color and some padding around the form */
        #{$plugin['name']}_container {
            border-radius: 5px;
            background-color: #f2f2f2;
            padding: 20px;
        }
        </style>
        <div id='{$plugin['name']}_container'>
            {$fields}
        </div>
        <script type='text/javascript'>
            function submit_{$plugin['entity']['name']}() {
                var {$plugin['name']}_submit_button = document.getElementById('{$plugin['name']}_submit_button')
                {$plugin['name']}_submit_button.style.backgroundColor = '#cccccc';
                {$plugin['name']}_submit_button.removeAttribute('onclick')
                {$plugin['name']}_submit_button.innerHTML = 'Thank You!'

                var {$plugin['name']}_postparam = []
                var {$plugin['name']}_container = document.getElementById('{$plugin['name']}_container')
                var {$plugin['name']}_fields = {$plugin['name']}_container.querySelectorAll('input, select, textarea')
                for (var field of {$plugin['name']}_fields) {$plugin['name']}_postparam.push(field.name+'='+field.value)

                const {$plugin['name']}_xhr = new XMLHttpRequest();
                {$plugin['name']}_xhr.open('POST', '$endpoint', true);
                {$plugin['name']}_xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                {$plugin['name']}_xhr.send({$plugin['name']}_postparam.join('&'));
            }
        </script>
    ";
}

add_shortcode("{$plugin['name']}_{$plugin['entity']['name']}_form", function () use ($plugin) {
    return form($plugin);
});

add_action('rest_api_init', function () use ($plugin) {
    register_rest_route("{$plugin['name']}/v1", "/world", [
        "methods" => "GET",
        "permission_callback" => "__return_true",
        "callback" => function () use ($plugin) {
            return site_url("wp-json/" . "{$plugin['name']}/v1" . "/world");
        }
    ]);
    register_rest_route("{$plugin['name']}/v1", "/create_{$plugin['entity']['name']}", [
        "methods" => "POST",
        "permission_callback" => "__return_true",
        "callback" => function () use ($plugin) {
            global $wpdb;

            $data = array();
            foreach ($plugin['entity']['fields'] as $field => $attributes) $data[] = "'{$_POST[$field]}'";
            $data = implode(',', $data);
            $query = "INSERT INTO `wp_{$plugin['entity']['name']}` (`id`, `createdAt`, `updatedAt`, `activity`, `status`)
            VALUES('', NOW(), NOW(), {$data})";
            $wpdb->query($query);
        }
    ]);
});