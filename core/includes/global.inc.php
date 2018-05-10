<?php
namespace O10n;

/**
 * Global functions
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */

// Add search replace filter
function search_replace($search, $replace, $regex = false)
{
    Core::get('output')->add_search_replace($search, $replace, $regex);
}

// Disable optimization
function disable($state = true)
{
    Core::get('env')->disable($state);
}


// load cron related methods
require O10N_CORE_PATH . 'includes/cron.inc.php';
