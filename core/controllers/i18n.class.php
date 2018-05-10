<?php
namespace O10n;

/**
 * Translation Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class I18n extends Controller implements Controller_Interface
{
    
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
        // load translation on WordPress init
        add_action('init', array($this,'load_textdomain'), $this->first_priority);
    }

    /**
     * Setup translation
     */
    final public function load_textdomain()
    {
        // load text domain
        load_plugin_textdomain(
            'o10n',
            false,
            O10N_CORE_PATH . 'languages/'
        );
    }
}
