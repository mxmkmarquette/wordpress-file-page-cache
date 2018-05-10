<?php
namespace O10n;

/**
 * Uninstall Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class Uninstall
{
    private $module;

    /**
     * Load controller
     *
     * @param string $module Module to delete
     */
    public function __construct($module)
    {
        $this->module = $module;
    }

    /**
     * Uninstall settings
     *
     * @param mixed $paths Paths to delete from options
     */
    public function delete_settings($paths)
    {
        if (!is_array($paths)) {
            $paths = array($paths);
        }

        // get O10N config
        $options = get_option('o10n', false);
        if ($options) {
            foreach ($paths as $path) {
                if (empty($path)) {
                    continue;
                }
                foreach ($options as $key => $value) {
                    if ($key === $path || strpos($key, $path . '.') === 0) {
                        unset($options[$key]);
                    }
                }
            }

            // remove empty options
            if (empty($options)) {
                delete_option('o10n');

                // delete other options
                delete_option('o10n_cache_stats');
            }
        }
    }

    /**
     * Uninstall database tables
     *
     * @param array $hash_id_tables Hash index tables
     */
    public function delete_tables($hash_id_tables = false)
    {
        global $wpdb;

        // get loaded optimization modules
        $modules = (class_exists('\O10n\Core')) ? Core::get('modules') : false;

        // plugin is last remaining optimization module, delete cache table
        if (!$modules || count($modules) === 0 || (count($modules) === 1 && $modules[0] === $this->module)) {
            $cache_table = $wpdb->prefix . 'o10n__cache';
            $wpdb->query("DROP TABLE IF EXISTS {$cache_table}");
        }

        // delete module related hash tables
        if ($hash_id_tables) {
            if (!is_array($hash_id_tables)) {
                $hash_id_tables = array($hash_id_tables);
            }
            foreach ($hash_id_tables as $table) {
                $hash_index_table = $wpdb->prefix . 'o10n__cache_' . $table;
                $wpdb->query("DROP TABLE IF EXISTS {$hash_index_table}");
            }
        }
    }
}
