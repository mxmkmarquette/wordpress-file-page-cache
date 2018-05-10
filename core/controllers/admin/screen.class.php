<?php
namespace O10n;

/**
 * WordPress Admin Screen Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class AdminScreen extends Controller implements Controller_Interface
{
    private $screen; // active WP_Screen object
    private $screen_controllers = array(); // screen controllers

    /**
     * Load controller
     *
     * @param  Core       $Core Core controller instance.
     * @return Controller Controller instance.
     */
    public static function &load(Core $Core)
    {
        // instantiate controller
        return parent::construct($Core, array());
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
        // process screen
        add_action('current_screen', array( $this, 'init'), $this->first_priority);

        // add screen options
        add_filter('screen_settings', array( $this, 'show_screen_options'), 10, 2);

        // save screen options
        add_filter('set-screen-option', array( $this, 'set_screen_options'), 10, 3);
    }

    final public function init()
    {
        // not in admin panel
        if (!is_admin()) {
            return;
        }

        // set active screen
        $this->screen = get_current_screen();
    }

    /**
     * Load screen options controller
     */
    final public function load_screen($name, $data = array())
    {

        // require base class
        require_once O10N_CORE_PATH . 'controllers/admin/screen/base.class.php';

        // include screen template
        $controller_file = O10N_CORE_PATH . 'controllers/admin/screen/'.$name.'.class.php';
        if (!file_exists($controller_file)) {
            throw new Exception('Screen options controller missing: '.$this->file->safe_path($controller_file), 'admin');
        }
        $controller_classname = 'O10n\AdminScreenOptions' . ucfirst($name);

        // include controller file
        require_once $controller_file;

        // load controller
        $this->screen_controllers[$name] = $controller_classname::load($this->core);

        // store data for template
        $this->screen_controllers[$name]->set_data($data);
    }

    /**
     * Load screen options template
     */
    final public function load_template($name, &$controller = false)
    {
        if (!$user = wp_get_current_user()) {
            return;
        }

        // include screen template
        $template = O10N_CORE_PATH . 'admin/screen/'.$name.'.inc.php';
        if (!file_exists($template)) {
            throw new Exception('Screen options template missing: '.$this->file->safe_path($template), 'admin');
        }

        // load template HTML
        ob_start();
        require $template;
        $template_html = trim(ob_get_clean());

        // return HTML
        return $template_html;
    }

    /**
     * Display screen options menu
     *
     * @param  string $status HTML
     * @param  array  $args   Arguments
     * @return string HTML
     */
    final public function show_screen_options($status, $args)
    {
        if (!$user = wp_get_current_user()) {
            return;
        }

        // no screen options
        if (empty($this->screen_controllers)) {
            return $status;
        }

        // add header
        $status .= $this->load_template('form-head');

        foreach ($this->screen_controllers as $controller_name => $controller) {

            // add screen options
            $status .= $this->load_template($controller_name, $controller);
        }

        // add footer
        $status .= $this->load_template('form-foot');

        return $status;
    }

    /**
     * Save screen options
     *
     * @param string $status HTML
     * @param string $option Option key
     * @param mixed  $value  Option value
     */
    final public function set_screen_options($status, $option, $value)
    {

        // get user
        if (!$user = wp_get_current_user()) {
            return;
        }

        // options
        $options = (isset($_POST) && isset($_POST['o10n_screen'])) ? $_POST['o10n_screen'] : array();

        // save options
        if (!empty($this->screen_controllers)) {
            foreach ($this->screen_controllers as $controller) {
                $controller->save($user, $options);
            }
        }
    }
}
