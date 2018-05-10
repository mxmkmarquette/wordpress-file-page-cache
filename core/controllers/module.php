<?php
namespace O10n;

/**
 * Optimization module loader
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

// Module loader
final class Module implements Module_Interface
{
    // module info
    private $module_name;
    private $module_version;
    private $minimum_core_version; // required minimum core version

    // plugin file paths
    private $file;
    private $dir_path;
    private $dir_url;
    private $basename;

    // controllers
    private $controllers = array();
    private $module_controllers;
    private $admin_controllers;
    private $admin_global_controllers;

    // cache stores
    private $cache_store_index;
    private $cache_stores;

    // disable module
    private $disabled = false;

    /**
     * Load module
     *
     * @param string $module_key           Module key
     * @param string $module_name          Module name
     * @param string $module_version       Module version
     * @param string $minimum_core_version Minimum core version required by module
     * @param array  $controllers          Controllers to load
     * @param string $plugin_file          Module plugin file name
     */
    final public function __construct($module_key, $module_name, $module_version, $minimum_core_version, $controllers, $cache_store_index, $cache_stores, $plugin_file)
    {
        // module info
        $this->module_key = $module_key;
        $this->module_name = $module_name;
        $this->module_version = $module_version;

        // minimum core version required for module
        $this->minimum_core_version = $minimum_core_version;

        // plugin paths
        $this->file = $plugin_file;
        $this->dir_path = \plugin_dir_path($plugin_file);
        $this->dir_url = \plugin_dir_url($plugin_file);
        $this->basename = \plugin_basename($plugin_file);

        // cache stores
        $this->cache_store_index = (is_numeric($cache_store_index)) ? $cache_store_index : false;
        ;
        $this->cache_stores = ($this->cache_store_index && is_array($cache_stores) && !empty($cache_stores)) ? $cache_stores : false;

        // define controllers
        if ($controllers) {
            $this->controllers = $controllers;
            if (isset($controllers['core']) && !empty($controllers['core'])) {
                $this->module_controllers = & $controllers['core'];
            }
            if (isset($controllers['admin']) && !empty($controllers['admin'])) {
                $this->admin_controllers = & $controllers['admin'];
            }
            if (isset($controllers['admin_global']) && !empty($controllers['admin_global'])) {
                $this->admin_global_controllers = & $controllers['admin_global'];
            }
        }

        // load plugin core from module installation
        add_action('plugins_loaded', array($this,'load_core'), 1);

        // add module to core
        add_action('plugins_loaded', array($this,'load_module'), 5);
    }

    /**
     * Return module key
     */
    final public function module_key()
    {
        return $this->module_key;
    }

    /**
     * Return module name
     */
    final public function name()
    {
        return $this->module_name;
    }

    /**
     * Return module version
     */
    final public function version()
    {
        return $this->module_version;
    }

    /**
     * Return directory path
     */
    final public function dir_path()
    {
        return $this->dir_path;
    }

    /**
     * Return directory url
     */
    final public function dir_url()
    {
        return $this->dir_url;
    }

    /**
     * Return basename
     */
    final public function basename()
    {
        return $this->basename;
    }

    /**
     * Load core from module installation
     */
    final public function load_core()
    {
        if (!class_exists('\O10n\Core')) {

            // load the optimization core
            require $this->dir_path . 'core/loader.php';
        }
    }

    /**
     * Add module to core
     */
    final public function load_module()
    {

        // verify core version
        if (version_compare(O10N_CORE_VERSION, $this->minimum_core_version, '<')) {
            $this->disabled = true;

            // add admin notice
            add_action('admin_notices', create_function('', "echo '<div class=\"error\"><h1>".__($this->module_name . ' requires O10N Core version '.$this->minimum_core_version.' (loaded: '.O10N_CORE_VERSION.').</h1><p>'.$this->module_name.' is a module of a performance optimization package that uses the minimum amount of resources when used stand alone together with other stand alone optimization modules by sharing a single optimization core. This requires maintenance of a base level core version when using multiple optimization modules independently.</p><p><strong>Please upgrade optimization plugins with a core version lower than '.$this->minimum_core_version.'.</strong></p>', 'o10n') ."</h1></div>';"), (PHP_INT_MAX * -1));

            return;
        }

        // add module to core
        Core::load_module($this->module_key, $this);
    }
    
    /**
     * Load module controllers
     *
     * @param  bool  $is_admin     In WP admin panel
     * @param  bool  $is_logged_in Is logged in user
     * @return array Controllers to load
     */
    final public function controllers($is_admin, $is_logged_in)
    {
        // disabled
        if ($this->disabled) {
            return;
        }

        // controllers to load
        $controllers = array();

        if ($this->module_controllers) {
            $controllers = array_merge($controllers, $this->module_controllers);
        }

        if ($is_admin && $this->admin_controllers) {
            $controllers = array_merge($controllers, $this->admin_controllers);
        }

        if ($is_logged_in && $this->admin_global_controllers) {
            $controllers = array_merge($controllers, $this->admin_global_controllers);
        }

        return (!empty($controllers)) ? $controllers : false;
    }

    /**
     * Return cache store index
     */
    final public function cache_store_index()
    {
        // disabled
        if ($this->disabled) {
            return;
        }

        return $this->cache_store_index;
    }

    /**
     * Return cache stores
     */
    final public function cache_stores()
    {
        // disabled
        if ($this->disabled) {
            return;
        }

        return $this->cache_stores;
    }

    /**
     * Return admin view directory
     *
     * @return string Module view directory
     */
    final public function admin_view_directory()
    {
        // disabled
        if ($this->disabled) {
            return;
        }

        return $this->dir_path . 'controllers/admin/views/';
    }

    /**
     * Return admin tabs
     */
    final public function admin_tabs()
    {
        return Core::get('Admin' . ucfirst(str_replace('-', '', $this->module_key)))->admin_nav_tabs();
    }

    /**
     * Return admin base
     */
    final public function admin_base()
    {
        return Core::get('Admin' . ucfirst(str_replace('-', '', $this->module_key)))->admin_base();
    }

    /**
     * Autoload controller dependencies
     *
     * @param  string $class_name           Class name without namespace
     * @param  string $class_name_lowercase Lowercase class name without namespace
     * @param  string $ns_class_name        Class name with namespace
     * @return string Controller file path
     */
    final public function autoload($class_name, $class_name_lowercase, $ns_class_name)
    {
        // disabled
        if ($this->disabled) {
            return;
        }

        $class_file = false;

        if ($class_name_lowercase !== 'admin' && strpos($class_name_lowercase, 'admin') === 0) {
            
            // admin controller
            if ($this->admin_controllers && in_array($class_name, $this->admin_controllers)) {
                if (!is_admin()) {
                    throw new Exception('Admin controller loaded outside admin environment.', 'core');
                }
                $class_file = $this->dir_path . 'controllers/admin/'.substr($class_name_lowercase, 5).'.class.php';
            } elseif ($this->admin_global_controllers && in_array($class_name, $this->admin_global_controllers)) {
                // global admin controller
                $class_file = $this->dir_path . 'controllers/admin/'.substr($class_name_lowercase, 5).'.class.php';
            }
        } else {

            // module controller
            if ($this->module_controllers && in_array($class_name_lowercase, $this->module_controllers)) {
                $class_file = $this->dir_path . 'controllers/'.$class_name_lowercase.'.class.php';
            }
        }

        return ($class_file && file_exists($class_file)) ? $class_file : false;
    }

    // cloning is forbidden.
    final private function __clone()
    {
    }

    // unserializing instances of this class is forbidden.
    final private function __wakeup()
    {
    }
}


/**
 * Module loader interface
 */
interface Module_Interface
{
    public function controllers($is_admin, $is_logged_in); // module controllers
    public function admin_view_directory(); // admin view directory
    public function autoload($class_name, $class_name_lowercase, $ns_class_name); // autoload method
}
