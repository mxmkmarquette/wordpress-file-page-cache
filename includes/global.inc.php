<?php
namespace O10n;

/**
 * Global functions
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */


// Enable/disable page cache
function page_cache($state = true)
{
    Core::get('filecache')->enable($state);
}

// Enable/disable PHP Opcache boost
function page_cache_boost($state = true)
{
    Core::get('filecache')->boost($state);
}

// Set page cache expire
function page_cache_expire($timestamp)
{
    Core::get('filecache')->expire($timestamp);
}
