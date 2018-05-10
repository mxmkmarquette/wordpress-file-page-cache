<?php
namespace O10n;

/**
 * Optimization core loader
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 * @link       https://github.com/o10n-x/
 */

if (! defined('WPINC')) {
    die;
}

// abort loading during upgrades
if (defined('WP_INSTALLING') && WP_INSTALLING) {
    return;
}

// core already loaded
if (defined('O10N_CORE_VERSION')) {
    return;
}

define('O10N_CORE_VERSION', '0.0.48');
define('O10N_CORE_URI', \plugin_dir_url(__FILE__));
define('O10N_CORE_PATH', \plugin_dir_path(__FILE__));

// require PHP 5.4+
if (version_compare(PHP_VERSION, '5.4', '<')) {
    add_action('admin_notices', create_function('', "echo '<div class=\"error\"><p>".__('The Performance Optimization plugin requires PHP 5.4+ to function properly. Please upgrade PHP or deactivate the Performance Optimization plugin.', 'o10n') ."</p></div>';"));

    return;
} else {

    # cache directory
    if (!defined('O10N_CACHE_DIR')) {
        define('O10N_CACHE_DIR', \trailingslashit(WP_CONTENT_DIR) . 'cache/o10n/');
    }
    if (!defined('O10N_CACHE_URL')) {
        define('O10N_CACHE_URL', \trailingslashit(WP_CONTENT_URL) . 'cache/o10n/');
    }
    if (!defined('O10N_CACHE_CHMOD_FILE')) {
        define('O10N_CACHE_CHMOD_FILE', 0664);
    }
    if (!defined('O10N_CACHE_CHMOD_DIR')) {
        define('O10N_CACHE_CHMOD_DIR', 0755);
    }

    try {

        // load the core plugin class
        require O10N_CORE_PATH . 'controllers/core.class.php';
        
        // load core plugin controller
        Core::load();

        // catch plugin errors
    } catch (Exception $err) {

        // plugin failed to load
        if (is_admin()) {
            add_action('admin_notices', create_function('', "echo '<div class=\"error\"><h1>".__('Optimization plugin core failed to load', 'o10n') ."</h1><p>".$err->getMessage()."</p></div>';"), (PHP_INT_MAX * -1));
        }

        // write error to log
        error_log('Optimization plugin core failed to load on ' . parse_url($_SERVER['REQUEST_URI'] . ' | Error: '.$err->getMessage(), PHP_URL_PATH));

        return;

        // catch other exceptions (from dependencies, libraries etc.)
    } catch (\Exception $err) {

        // add admin notice
        if (is_admin()) {
            add_action('admin_notices', create_function('', "echo '<div class=\"error\"><h1>".__('Optimization plugin core experienced a problem while loading a dependency.', 'o10n') ."</h1><p>".$err->getMessage()."</p></div>';"), (PHP_INT_MAX * -1));
        }
        
        // write error to log
        error_log('Optimization plugin core failed to load a dependency on ' . parse_url($_SERVER['REQUEST_URI'] . ' | Error: '.$err->getMessage(), PHP_URL_PATH));

        return;
    }


    // load global method
    require O10N_CORE_PATH . 'includes/global.inc.php';
}
