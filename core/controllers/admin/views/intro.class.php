<?php
namespace O10n;

/**
 * Intro Admin View Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class AdminViewIntro extends AdminViewBase
{
    protected static $view_key = 'intro'; // reference key for view

    /**
     * Load controller
     *
     * @param  Core       $Core Core controller instance.
     * @return Controller Controller instance.
     */
    public static function &load(Core $Core)
    {
        //self::$view = $view;
        // instantiate controller
        return parent::construct($Core, array());
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
        // set view etc
        parent::setup();
    }

    /**
     * Setup view
     */
    public function setup_view()
    {
    }
}
