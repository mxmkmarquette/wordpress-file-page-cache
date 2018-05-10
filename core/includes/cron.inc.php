<?php

/**
 * Cron related global functions
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */

// Cache prune cron (clear expired cache entries)
function o10n_cron_prune_cache()
{
    \O10n\Core::get('cache')->prune();
}
