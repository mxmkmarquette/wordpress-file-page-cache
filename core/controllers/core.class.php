<?php 
namespace O10n;

/**
 * Performance Optimization core class.
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class Core
{
    // Core instance
    protected static $instance = null;

    protected $loaded_controllers = array(); // controller instances
    protected $loaded_modules = array(); // module instances

    // controllers
    protected $controllers = array(

        // core controllers
        'core' => array(
            'error',
            'env',
            'i18n',
            'json',
            'file',
            'db',
            'cache',
            'url',
            'options',
            'install',
            'shutdown',
            'output',
            'admin'
        ),

        // plugin admin controllers
        'admin' => array(
            'AdminCP',
            'AdminScreen',
            'AdminClient',
            'AdminOptions',
            'AdminForm',
            'AdminForminput',
            'AdminAjax',
            'AdminView',
            'AdminMenu',
            'AdminLinkFilter',
            'AdminHelp'
        ),

        // global admin controllers (admin bar etc.)
        'admin_global' => array(
            'AdminGlobal'
        )
    );

    /**
     * Instantiate core controller
     *
     * @static
     */
    public static function load()
    {
        // allow instantiation once
        if (!is_null(self::$instance)) {

            // developer debug message
            _doing_it_wrong(__FUNCTION__, __('Forbidden'), O10N_CORE_VERSION);

            // print error to regular users
            wp_die(__('The plugin is instantiated multiple times. This may indicate an attack. Please contact the administrator of this website.', 'o10n'));
        }

        // instantiate standalone core controller
        self::$instance = new self();

        // setup plugin core
        add_action('plugins_loaded', array(self::$instance, 'setup'), 10);
    }

    /**
     * Setup core controller
     */
    final public function setup()
    {
        // autoload controller class files
        spl_autoload_register(array($this,'autoload'));

        // active controllers
        $controllers = $this->controllers['core'];

        $is_admin = is_admin();
        $is_logged_in = ($is_admin) ? true : is_user_logged_in();

        // admin control panel controller
        if ($is_admin) {
            $controllers = array_merge($controllers, $this->controllers['admin']);
        }
        if ($is_logged_in) { // loggedin user
            $controllers = array_merge($controllers, $this->controllers['admin_global']);
        }

        // load controllers
        foreach ($controllers as $controller) {
            $this->load_controller($controller);
        }

        // load module controllers
        if (!empty($this->loaded_modules)) {
            foreach ($this->loaded_modules as $module) {
                $module_controllers = $module->controllers($is_admin, $is_logged_in);
                if ($module_controllers) {
                    foreach ($module_controllers as $controller) {

                        // verify if controller is already loaded
                        if (!in_array($controller, $controllers)) {
                            $controllers[] = $controller;
                            $this->load_controller($controller);
                        }
                    }
                }
            }
        }

        // setup completed
        try {
            do_action('o10n_setup_completed');
        } catch (Exception $e) {
                  
            // print fatal error to public
            $this->loading_failed($e->getMessage(), __FILE__, __LINE__);
        }
    }

    /**
     * Add module to core
     *
     * @static
     */
    final public static function load_module($module_key, Module $module)
    {
        // allow instantiation once
        if (isset(self::$instance->loaded_modules[$module_key])) {

            // print fatal error to public
            self::$instance->loading_failed(false, __FILE__, __LINE__);
        }

        // add module
        self::$instance->loaded_modules[$module_key] = $module;
    }

    /**
     * Return modules
     */
    final public function &modules($module_key = false)
    {
        if ($module_key) {
            return $this->loaded_modules[$module_key];
        }

        return $this->loaded_modules;
    }

    /**
     * Check if module is loaded
     */
    final public function module_loaded($module_key)
    {
        return isset($this->loaded_modules[$module_key]);
    }

    /**
     * Load controller
     *
     * @param string $controller_name Controller name.
     */
    final protected function load_controller($controller_name)
    {

        // class name
        $controller_classname = 'O10n\\' . ucfirst($controller_name);

        // load controller
        try {
            $this->loaded_controllers[$controller_name] = & $controller_classname::load($this);
        } catch (Exception $e) {
              
            // print fatal error to public
            $this->loading_failed($e->getMessage() . "<br />Controller: " . $controller_name . '<br />File: ' . str_replace(ABSPATH, '[ABSPATH]', $e->getFile()), __FILE__, __LINE__);
        }

        // controller loaded hook
        do_action('o10n_controller_loaded', $controller_name);
    }

    /**
     * Forward exception to error controller
     *
     * @param O10n\Exception $error Exception to forward.
     */
    final public static function forward_exception(Exception $error)
    {
        if (!isset(self::$instance->loaded_controllers['error'])) {
            wp_die($error->getMessage());
        }
        self::$instance->loaded_controllers['error']->handle($error);
    }

    /**
     * Autoload controller dependencies
     *
     * @param string $ns_class_name The namespaced class name for which to load dependencies.
     */
    final public function autoload($ns_class_name)
    {
        // restrict to namespace
        if (strpos($ns_class_name, 'O10n\\') === false) {
            return;
        }

        // already loaded
        if (class_exists($ns_class_name) || function_exists($ns_class_name)) {
            return;
        }

        // load class file
        $class_name = substr($ns_class_name, 5);
        $class_name_lowercase = strtolower($class_name);

        if ($class_name_lowercase !== 'admin' && strpos($class_name_lowercase, 'admin') === 0) {

            // admin controller
            $class_file = O10N_CORE_PATH . 'controllers/admin/'.substr($class_name_lowercase, 5).'.class.php';
        } else {
            $class_file = O10N_CORE_PATH . 'controllers/'.$class_name_lowercase.'.class.php';
        }

        // not found, try modules
        if (!file_exists($class_file)) {
            $class_file = false;


            if (!empty($this->loaded_modules)) {
                foreach ($this->loaded_modules as $module) {
                    $class_file = $module->autoload($class_name, $class_name_lowercase, $ns_class_name);
                    if ($class_file) {
                        break;
                    }
                }
            }

            // failed to load controller
            if (!$class_file) {
                if (substr_count($class_file, '\\') > 1) {
                    if (class_exists('O10n\\Exception')) {
                        throw new Exception('Class file does not exist ' . $ns_class_name, 'core');
                    }
                    $this->loading_failed(false, __FILE__, __LINE__);
                }

                return;
            }
        }

        // load controller file
        require_once $class_file;

        // loading failed
        if (!class_exists($ns_class_name)) {
            if (substr_count($class_file, '\\') > 1) {
                if (class_exists('O10n\\Exception')) {
                    throw new Exception('Failed to load class ' . $ns_class_name, 'core');
                }
                $this->loading_failed(false, __FILE__, __LINE__);
            }

            return;
        }

        return true;
    }

    // fatal error during loading
    final private function loading_failed($err = false, $file = false, $line = fale)
    {
        if (is_admin() || ($GLOBALS['pagenow'] === 'wp-login.php')) {
            $message = "<h1>". __('Optimization plugin core failed to load', 'o10n') ."</h1>";
            if ($err) {
                $message .= "<p>".$err."</p>";
            }
            if ($file) {
                $message .= "<p>File: ".esc_html(str_replace(ABSPATH, '[ABSPATH]', $file))." Line: ".esc_html($line)."</p>";
            }
            add_action('admin_notices', create_function('', "echo '<div class=\"error\">".str_replace('\'', '\\\'', $message)."</div>';"), (PHP_INT_MAX * -1));
        } else {
            wp_die(__('Failed to load optimization plugin core. Please contact the administrator of this website.', 'o10n'));
        }
    }

    // return controller instance
    final public function _get($controller)
    {
        // return loaded module keys
        if ($controller === 'modules') {
            $modules = $this->modules();

            return ($modules) ? array_keys($modules) : false;
        }
        if (isset($this->loaded_controllers[$controller])) {
            return $this->loaded_controllers[$controller];
        }

        return false;
    }

    // return controller instance
    final public static function get($controller)
    {
        return self::$instance->_get($controller);
    }

    // construction is forbidden
    final protected function __construct()
    {
        if (!in_array(get_called_class(), array('O10n\\Core'))) {
            wp_die(__('Core class extension is not allowed.', 'o10n'));
        }
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
