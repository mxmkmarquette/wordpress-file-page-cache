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
 * Error Controller
 */
class Error extends Controller implements Controller_Interface
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
        return parent::construct($Core, array(
            // controllers to bind
            'file',
            'admin'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
    }

    /**
     * Handle error exception
     */
    final public function handle(Exception $error)
    {
        $category = $error->getCategory();

        // display admin notice?
        $admin_notice = $error->isAdminNotice();
        if ($admin_notice === -1) {
            $admin_notice = true;
        }

        // admin notice
        if ($admin_notice) {
            $this->admin->add_notice($error->getMessage(), $category);
        }

        if (defined('O10N_DEBUG') && O10N_DEBUG && $category !== 'client') {
            $client = Core::get('client');
            if ($client) {
                $client->print_exception($category, $error->getMessage());
            }
        }
    }

    /**
     * Print fatal error
     *
     * @todo
     *
     * @param mixed $error Error to display.
     */
    final public function fatal($error)
    {
        // clear output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }

        // output SEO friendly header (temporary error)
        if (!headers_sent()) {
            header(($_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1' ? 'HTTP/1.1' : 'HTTP/1.0') . ' 503 Service Temporarily Unavailable', true, 503);
            header('Retry-After: 60');
        }

        // Exception
        if (is_a($error, 'O10n\\Exception')) {
            $error = $error->getMessage();
        }

        // try 503.php in theme directory
        $custom_errorpage = $this->file->theme_directory() . '503.php';
        if (file_exists($custom_errorpage)) {
            require $custom_errorpage;
            exit;
        }

        wp_die('<h1>Fatal Error</h1>' . $error);
    }
}
