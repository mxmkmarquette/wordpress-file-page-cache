<?php
namespace O10n;

/**
 * Admin Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class Admin extends Controller implements Controller_Interface
{
    private $controllers = array(); // admin controllers
    private $is_admin = null;

    /**
     * Load controller
     *
     * @param  Core       $Core Core controller instance.
     * @return Controller Controller instance.
     */
    public static function &load(Core $Core)
    {

        // instantiate controller
        return parent::construct($Core);
    }

    /**
     * Setup controller
     */
    protected function setup()
    {

        // WordPress setup
        add_action('wp', array($this,'wp_setup'), $this->first_priority);
        add_action('admin_init', array($this,'wp_setup'), $this->first_priority);
    }

    /**
     * WordPress setup hook
     */
    final public function wp_setup()
    {
        // User is administrator with permission to modify optimization settings
        $this->is_admin = current_user_can('manage_options');
        if (!$this->is_admin) {
            // @todo dev IPs
        }
    }

    /**
     * User is administrator with permission to modify optimization settings
     */
    final public function is_admin()
    {
        return $this->is_admin;
    }

    /**
     * Get admin error notices
     */
    final public function get_notices()
    {
        return get_option('o10n_notices', array());
    }

    /**
     * Add admin error notice
     */
    final public function add_notice($message, $category = 'admin', $type = 'ERROR', $options = false)
    {
        // get notices
        $notices = $this->get_notices();

        // notice data
        $notice = (is_array($options)) ? $options : array();
        $notice['hash'] = md5($category . ':' . $message);
        $notice['text'] = $message;
        $notice['category'] = $category;
        $notice['type'] = $type;
        $notice['date'] = time();

        // verify if notice exists
        $updated_notices = array();
        foreach ($notices as $key => $item) {

            // notice exist, merge and push to front
            if (isset($item['hash']) && $item['hash'] === $notice['hash']) {
                $notice = array_merge($item, $notice);
                continue 1;
            }
            $updated_notices[] = $item;
        }

        // add stack trace for plugin development
        /*if ($this->dev->is_plugin_dev()) {
            $notice['trace'] = json_encode(debug_backtrace(), JSON_PRETTY_PRINT);
        }*/

        // push to front
        array_unshift($updated_notices, $notice);

        // sort by date
        usort($updated_notices, function ($a1, $a2) {
            return $a2['date'] - $a1['date'];
        });

        // limit amount of stored notices
        if (count($updated_notices) > 10) {
            $updated_notices = array_slice($updated_notices, -10, 10);
        }

        // save notices
        update_option('o10n_notices', $updated_notices, false);
    }
}
