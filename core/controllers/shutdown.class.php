<?php
namespace O10n;

/**
 * PHP Shutdown Callback Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class Shutdown extends Controller implements Controller_Interface
{
    private $tasks = array(); // shutdown (background) tasks

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
        // register PHP shutdown callback
        register_shutdown_function(array($this,'on_shutdown'));
    }

    /**
     * Add shutdown callback task
     */
    final public function add($callable)
    {
        if (!is_callable($callable)) {
            if (is_array($callable)) {
                if (isset($callable[1]) && is_string($callable[1])) {
                    $callable = $callable[1];
                }
            }
            if (!is_string($callable)) {
                $callable = 'unknown';
            }
            throw new Exception('Shutdown task not callable (' . (string)$callable . ')', 'core');
        }
        $this->tasks[] = $callable;
    }

    /**
     * Shutdown callback handler
     */
    final public function on_shutdown()
    {

        // nothing to process
        if (empty($this->tasks)) {
            return;
        }

        // avoid abortion of PHP process
        ignore_user_abort(true);
        
        if (function_exists('session_id') && session_id()) {
            session_write_close();
        }

        // PHP running under FastCGI
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            if (!headers_sent()) {
                header("Connection: close");
            }

            // flush output
            while (ob_get_level()) {
                ob_end_flush();
            }
            flush();
        }

        // errors
        $errors = array();

        // execute shutdown (background) tasks
        $task = array_shift($this->tasks);
        while ($task) {
            if (!is_callable($task)) {
                $errors[] = 'Shutdown task not callable (' . (string)$task . ')';
            }

            call_user_func($task);

            $task = array_shift($this->tasks);
        }

        if (!empty($errors)) {
            throw new Exception('Error in shutdown handler: <ol><li>' . implode('</li><li>', $errors) . '</li></ol>', 'core');
        }
    }
}
