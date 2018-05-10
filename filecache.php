<?php
namespace O10n;

/**
 * File Page Cache with PHP Opcache boost option
 *
 * Advanced file based page cache with PHP Opcache boost option (500x faster than Redis and Memcached)
 *
 * @link              https://github.com/o10n-x/
 * @package           o10n
 *
 * @wordpress-plugin
 * Plugin Name:       File Page Cache with PHP Opcache boost
 * Description:       Advanced file based page cache with PHP Opcache boost option (500x faster than Redis and Memcached).
 * Version:           0.0.1
 * Author:            Optimization.Team
 * Author URI:        https://optimization.team/
 * GitHub Plugin URI: https://github.com/o10n-x/wordpress-file-page-cache
 * Text Domain:       o10n
 * Domain Path:       /languages
 */

if (! defined('WPINC')) {
    die;
}

// abort loading during upgrades
if (defined('WP_INSTALLING') && WP_INSTALLING) {
    return;
}

// settings
$module_version = '0.0.1';
$minimum_core_version = '0.0.47';
$plugin_path = dirname(__FILE__);

// load the optimization module loader
if (!class_exists('\O10n\Module')) {
    require $plugin_path . '/core/controllers/module.php';
}

// load module
new Module(
    'filecache',
    'File Page Cache',
    $module_version,
    $minimum_core_version,
    array(
        'core' => array(
            'filecache'
        ),
        'admin' => array(
            'AdminFilecache'
        ),
        'admin_global' => array(
            'AdminGlobalfilecache'
        )
    ),
    9,
    array(
        'page' => array(
            'path' => 'page-cache/',
            'file_ext' => '.html',
            'alt_exts' => false,
            'expire' => false // @todo 259200 // expire after 3 days
        )
    ),
    __FILE__
);

// load public functions in global scope
require $plugin_path . '/includes/global.inc.php';
