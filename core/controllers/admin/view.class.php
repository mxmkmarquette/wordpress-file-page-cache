<?php
namespace O10n;

/**
 * Admin View Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class AdminView extends Controller implements Controller_Interface
{
    // available view controllers
    private $view_controllers = array();
    private $view_directories = array();

    // active view controller
    private $active_view = false; // view key
    private $active_view_controller = false; // controller object

    /**
     * Load controller
     *
     * @param  Core       $Core Core controller instance.
     * @return Controller Controller instance.
     */
    public static function &load(Core $Core)
    {
        // instantiate controller
        return parent::construct($Core, array(
            'AdminClient',
            'AdminForm',
            'file',
            'json',
            'options',
            'env'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
        // require base view controllers
        require_once O10N_CORE_PATH . 'controllers/admin/views/base.class.php';

        // add core view directory
        $this->view_directories[] = O10N_CORE_PATH . 'controllers/admin/views/';

        // load views after all controllers are loaded
        add_action('o10n_setup_completed', array($this,'load_views'), PHP_INT_MAX);
    }

    /**
     * Load views
     */
    final public function load_views()
    {
        // loaded view references
        $loaded_views = array(
            'base.class.php'
        );

        // add module view directories
        $modules = $this->core->modules();
        if ($modules) {
            foreach ($modules as $module) {
                $view_directory = $module->admin_view_directory();
                if ($view_directory && !in_array($view_directory, $this->view_directories)) {
                    $this->view_directories[] = $view_directory;
                }
            }
        }

        // load views
        foreach ($this->view_directories as $view_directory) {

            // read view directory
            $files = new \FilesystemIterator($view_directory, \FilesystemIterator::SKIP_DOTS);
            foreach ($files as $fileinfo) {

                // filename
                $filename = $fileinfo->getFilename();

                // view already loaded
                if (!$fileinfo->isFile() || in_array($filename, $loaded_views)) {
                    continue 1;
                }

                // class name
                if (strpos($filename, '-') !== false) {
                    $controller_classname = 'O10n\AdminView' . ucfirst(str_replace('.class.php', '', implode('', array_map('ucfirst', explode('-', $filename)))));
                } else {
                    $controller_classname = 'O10n\AdminView' . ucfirst(str_replace('.class.php', '', implode('', array_map('ucfirst', explode('-', $filename)))));
                }

                // include controller file
                require_once $view_directory . $filename;
                $loaded_views[] = $filename;

                // add controller key to index
                $this->view_controllers[$controller_classname::view_key()] = $controller_classname;
            }
        }

        // AJAX request
        if (defined('DOING_AJAX')) {

            // not a plugin related request
            if (!isset($_REQUEST['action']) || strpos($_REQUEST['action'], 'o10n_') !== 0) {
                return;
            }

            // AJAX requests use the view parameter
            if (!isset($_REQUEST['view']) || !isset($this->view_controllers[$_REQUEST['view']])) {
                throw new Exception('Invalid view reference in AJAX request.', 'admin');
            }

            // set active view
            $this->active_view = $_REQUEST['view'];
        } else {

            // save settings request
            if (isset($_POST['o10n'])) {
                if (isset($_REQUEST['view'])) {
                    if (isset($_REQUEST['tab']) && isset($this->view_controllers[$_REQUEST['view'] . '-' . $_REQUEST['tab']])) {
                        $this->active_view = $_REQUEST['view'] . '-' . $_REQUEST['tab'];
                    } elseif (isset($this->view_controllers[$_REQUEST['view']])) {
                        // set active view
                        $this->active_view = $_REQUEST['view'];
                    }
                }
                if (!$this->active_view) {
                    $view = (isset($_REQUEST['view'])) ? $_REQUEST['view'] : false;
                    throw new Exception('Invalid view in save settings request <strong>' . esc_html($view) . '</strong>', 'admin');
                }
            } else { // regular request

                // verify plugin origin
                if (!isset($_GET['page']) || strpos($_GET['page'], 'o10n') !== 0) {
                    return;
                }

                // extract view from page paramater
                $this->active_view = ($_GET['page'] === 'o10n') ? 'settings' : substr($_GET['page'], 5);

                if ($_GET['page'] === 'o10n' && isset($_GET['tab']) && !isset($this->view_controllers[$this->active_view . '-' . $_GET['tab']])) {
                    $this->active_view = 'installer';

                    // tab specific view controller
                } elseif (isset($_GET['tab']) && isset($this->view_controllers[$this->active_view . '-' . $_GET['tab']])) {
                    $this->active_view = $this->active_view . '-' . $_GET['tab'];
                } elseif (!isset($this->view_controllers[$this->active_view])) {
                    throw new Exception('Invalid view <strong>' . esc_html($this->active_view) . '</strong>', 'admin');
                }
            }
        }

        // setup view controller
        if ($this->active_view) {

            // load controller
            $controller = $this->view_controllers[$this->active_view];

            try {
                $this->active_view_controller = $controller::load($this->core);

                // setup controller
                $this->active_view_controller->setup_view();
            } catch (Exception $err) {
                wp_die($err->getMessage());
            }

            if ($this->active_view) {

                // client script config
                add_action('admin_init', array( $this, 'client_config'), $this->first_priority);

                // client script config
                add_action('send_headers', array( $this, 'nocache_headers'), PHP_INT_MAX);
            }
        }
    }

    /**
     * Send nocache headers
     */
    final public function nocache_headers()
    {
        // no cache headers
        $nocache = apply_filters('o10n_admin_nocache', true);
        if ($nocache) {
            nocache_headers();
        }
    }

    /**
     * Client script config
     */
    final public function client_config()
    {
        // get user
        if (!$user = wp_get_current_user()) {
            return;
        }

        // set active view
        $this->AdminClient->set_config('active_view', $this->active_view);

        // JSON schemas
        $schemas = $this->AdminForm->schemas();
        $schema_array = array();
        foreach ($schemas as $filename => $schemajson) {
            $schema_array[] = array(
                $filename,
                $schemajson
            );
        }

        $this->AdminClient->set_config('schemas', $schema_array);
    }

    /**
     * Print admin view template
     */
    final public function display()
    {

        // bind form methods to local variable
        $form = & $this->AdminForm;
        $methods = array('get','json','checked','visible','invisible','line_array','value','selected','advanced_options');
        foreach ($methods as $method) {
            $$method = function () use (&$form, $method) {
                return call_user_func_array(array($form,$method), func_get_args());
            };
        }

        // bind core methods
        $core = & $this->core;
        $methods = array('modules','module_loaded');
        foreach ($methods as $method) {
            $$method = function () use (&$core, $method) {
                return call_user_func_array(array($core,$method), func_get_args());
            };
        }
        
        // no view controller
        if (!$this->active_view || !$this->active_view_controller) {
            print __('<p class="warning_red">No view controller.</p>', 'o10n');
        } else {

            // bind view controller to $view
            $view = & $this->active_view_controller;

            // load templates
            try {

                // header
                $header_template = $this->active_view_controller->header_template();
                require $header_template;

                // view
                $view_template = $this->active_view_controller->template();
                require $view_template;

                // footer
                $footer_template = $this->active_view_controller->footer_template();
                require $footer_template;
            } catch (Exception $err) {
                print $err->getMessage();
            }
        }
    }

    /**
     * Print form header
     *
     * @param string $title Form title
     */
    final public function form_start($title, $view)
    {
        // active tab
        // $tab = $this->AdminMenuTabs->active();

        // tab info
        //$tabinfo = $this->AdminMenuTabs->info($tab);

        // form container start
        require O10N_CORE_PATH . 'admin/form-container-start.inc.php';
    }

    /**
     * Print form footer
     */
    final public function form_end()
    {

        // form container end
        require O10N_CORE_PATH . 'admin/form-container-end.inc.php';
    }

    /**
     * Return active view reference key
     *
     * @return string View reference key.
     */
    final public function active()
    {
        return $this->active_view;
    }

    /**
     * Return active view controller
     *
     * @return object View controller.
     */
    final public function active_controller()
    {
        return $this->active_view_controller;
    }

    /**
     * Return active admin tab
     *
     * @return array Active tab data
     */
    final public function active_tab()
    {
        return array($this->active_tab,$this->active_subtab);
    }

    /**
     * Return view path
     *
     * @param  bool   $first_param Return first part of JSON path.
     * @return string View path in dot notation
     */
    final public function json_path($first_param = false)
    {
        if ($this->active_view_controller) {
            $json_path = $this->active_view_controller->json_path();
            
            if ($first_param && $json_path && strpos($json_path, '.') !== false) {

                // get first part of path
                return explode('.', $json_path)[0];
            }

            return $json_path;
        }

        return '';
    }

    /**
     * Is the screen a settings form?
     */
    final public function is_settings($settings_param = false)
    {

        // @temp
        return true;
        //$this->is_plugin();

        if (!$this->settings_screen) {
            return false;
        }

        // verify if on settings form for JSON parameter, e.g. html
        if ($settings_param) {
            return ($this->settings_screen == $settings_param) ? true : false;
        }

        return true; // settings form
    }

    /**
     * Return active view controller
     *
     * @return object View controller.
     */
    final public function &get_controller()
    {
        return $this->active_view_controller;
    }
}
