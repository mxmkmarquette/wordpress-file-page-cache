<?php
namespace O10n;

/**
 * JSON Profile Editor Admin View Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class AdminViewSettings extends AdminViewBase
{
    protected static $view_key = 'settings'; // reference key for view

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
        return parent::construct($Core, array('AdminOptions'));
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
        // process form submissions
        add_action('o10n_save_settings_verify_input', array( $this, 'verify_input' ), 10, 1);
    }

    /**
     * Verify settings input
     *
     * @param  object   Form input controller object
     */
    final public function verify_input($forminput)
    {
        // JSON profile
        $json = $forminput->get('all', 'json-array');
        if ($json) {

            // @todo improve
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveArrayIterator($json),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            $path = [];
            $flatArray = [];

            $arrayVal = false;
            foreach ($iterator as $key => $value) {
                $path[$iterator->getDepth()] = $key;

                $dotpath = implode('.', array_slice($path, 0, $iterator->getDepth() + 1));
                if ($arrayVal && strpos($dotpath, $arrayVal) === 0) {
                    continue 1;
                }

                if (!is_array($value) || empty($value) || array_keys($value)[0] === 0) {
                    if (is_array($value) && (empty($value) || array_keys($value)[0] === 0)) {
                        $arrayVal = $dotpath;
                    } else {
                        $arrayVal = false;
                    }

                    $flatArray[$dotpath] = $value;
                }
            }

            // overwrite
            $this->AdminOptions->save($flatArray, true);
        }
    }
}
