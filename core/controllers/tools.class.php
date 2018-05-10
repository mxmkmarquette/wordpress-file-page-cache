<?php
namespace O10n;

/**
 * Tools/helpers Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class Tools extends Controller implements Controller_Interface
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
    }

    /**
     * Match string against filter list
     *
     * @param  string $string     String to match against
     * @param  string $filterType Include/exclude list
     * @param  array  $filterList Array with match strings
     * @return bool   Filter match true/false
     */
    final public function filter_list_match($string, $filterType, $filterList)
    {
        
        // match filter list
        foreach ($filterList as $match_string) {

            // match URL
            if (strpos($string, $match_string) !== false) {
                if ($filterType === 'include') {
                    return true;
                } else { // exclude list
                    return false;
                }
            }
        }

        return ($filterType === 'exclude') ? true : false;
    }

    /**
     * Match string against filter config objects
     *
     * @param  string $string       String to match against
     * @param  string $filterConfig Config objects
     * @param  array  $defaultMatch Include/exclude
     * @return bool   Filter match true/false
     */
    final public function filter_config_match($string, $filterConfig, $defaultMatch = 'exclude')
    {
        // default filter match
        $match = ($defaultMatch === 'exclude') ? false : true;

        // applicable filter config
        $config = false;
        
        // match filter list
        foreach ($filterConfig as $filter) {

            // verify config
            if (!isset($filter['match'])) {
                continue;
            }

            // regex
            if (isset($filter['regex']) && $filter['regex']) {
                $regex_match = false;
                try {
                    if (@preg_match($filter['match'], $string)) {
                        $regex_match = true;
                    }
                } catch (\Exception $err) {
                    $regex_match = false;
                }

                // filter match
                if ($regex_match) {
                    $match = true;
                    $config = $filter;
                    break;
                }
            } else {

                // match URL
                if (strpos($string, $filter['match']) !== false) {
                    $match = true;
                    $config = $filter;
                    break;
                }
            }
        }

        return ($match && $config) ? $config : $match;
    }

    /**
     * Merge indexed arrays
     */
    final public function merge_indexed_arrays($arrays)
    {
        $merged_array = array();
        foreach ($arrays as $array) {
            foreach ($array as $value) {
                $merged_array[] = $value;
            }
        }

        return $merged_array;
    }
}
