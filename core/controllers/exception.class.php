<?php
namespace O10n;

/**
 * Error Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Exception Controller
 */
final class Exception extends \Exception
{
    protected $category = 'unknown';    // error category
    protected $admin_notice = -1;       // display notice in admin
    protected $log = -1;                // log error
    protected $fatal = false;           // fatal error
    
    /**
     * Construct the exception
     * @param string  $message  The error message.
     * @param string  $category The errpr category.
     * @param boolean $fatal    Is fatal error.
     * @param integer $admin    Display admin panel notice.
     * @param integer $log      Log error.
     */
    public function __construct($message, $category, $fatal = false, $admin = -1, $log = -1)
    {
        // process exception
        parent::__construct($message, 1);

        $this->category = $category; // error category
        $this->admin = $admin; // display admin notice
        $this->log = $log; // log error
        $this->fatal = $fatal; // error is fatal
        
        if (!class_exists('O10n\\Core')) {
            wp_die(__('Failed to load plugin. Please contact the administrator of this website.', 'o10n'));
        }

        // forward exception to error handler
        Core::forward_exception($this);
    }

    /**
     * Return error category
     *
     * @return string Error category
     */
    public function getCategory()
    {
        return $this->category;
    }

    // display admin notice
    public function isAdminNotice()
    {
        return ($this->admin_notice === false) ? false : $this->admin_notice;
    }

    // log error
    public function isLog()
    {
        return ($this->log === false) ? false : $this->log;
    }

    // fatal error
    public function isFatal()
    {
        return ($this->fatal) ? true : false;
    }
}
