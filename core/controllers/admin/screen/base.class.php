<?php
namespace O10n;

/**
 * Admin Screen Options Base Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class AdminScreenOptionsBase extends Controller implements AdminScreenOptions_Controller_Interface
{
    protected $template_data = array(); // template data

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
    }

    public function save(&$user, &$options)
    {
    }

    /**
     * Set template data
     *
     * @param array $data Template data
     */
    final public function set_data($data)
    {
        $this->template_data = $data;
    }

    /**
     * Get template data
     *
     * @param array $data Template data
     */
    final public function get_data()
    {
        return $this->template_data;
    }
}

/**
 * Admin View Controller interface
 */
interface AdminScreenOptions_Controller_Interface
{
    public static function load(Core $Core); // the method to instantiate the controller
    public function save(&$user, &$options); // save settings
}
