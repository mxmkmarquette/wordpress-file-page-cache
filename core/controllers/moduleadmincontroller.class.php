<?php
namespace O10n;

/**
 * Module admin controller class
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

abstract class ModuleAdminController extends Controller
{
    

    // admin base
    protected $admin_base = 'admin.php';

    /**
     * Admin navigation tabs
     */
    public function admin_nav_tabs()
    {
        return $this->tabs;
    }
    
    /**
     * Admin base
     */
    public function admin_base()
    {
        return $this->admin_base;
    }
    
    /**
     * Activate hook
     */
    public function activate()
    {
        do_action('o10n_plugin_activate');
    }
    
    /**
     * Deactivate hook
     */
    public function deactivate()
    {
        do_action('o10n_plugin_deactivate');
    }
}

/**
 * Controller interface
 */
interface Module_Admin_Controller_Interface
{
    public static function load(Core $Core); // the method to instantiate the controller
}
