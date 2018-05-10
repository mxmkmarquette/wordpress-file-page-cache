<?php
namespace O10n;

/**
 * Admin Ajax Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class AdminAjax extends Controller implements Controller_Interface
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
        return parent::construct($Core, array());
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
    }
    
    /**
     * Process AJAX request and return request object
     */
    final public function request()
    {
        return new AjaxRequestData($_POST);
    }
}

/**
 * AJAX request data
 */
class AjaxRequestData
{
    private $data; // $_POST data
    private $user; // user
    private $errors = array();

    public function __construct($post)
    {
        // remove output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // check security parameter
        if (!check_ajax_referer('o10n', 'security', false)) {
            $this->output_errors(__('AJAX request authentication failed. Please verify the o10n form security parameter.', 'o10n'));
        }

        // verify if user is logged in
        if (!$this->user = wp_get_current_user()) {
            $this->output_errors(__('AJAX request failed. You are not logged in.', 'o10n'));

            return;
        }

        // @link https://codex.wordpress.org/Function_Reference/stripslashes_deep
        $this->data = array_map('stripslashes_deep', $post);
    }

    /**
     * Return posted data
     *
     * @param  string $key Data key.
     * @return mixed  Posted data.
     */
    public function data($key = false, $default = false)
    {
        // return data by key
        if ($key) {
            return (isset($this->data[$key])) ? $this->data[$key] : $default;
        }

        return $this->data;
    }

    /**
     * Return user
     *
     * @return object WordPress user object
     */
    public function user()
    {
        return $this->user;
    }

    /**
     * Return user ID
     *
     * @return object WordPress user object
     */
    public function user_id()
    {
        return $this->user->ID;
    }

    /**
     * Return errors
     *
     * @return array Errors.
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Add error
     *
     * @param string $message Error message
     */
    public function add_error($message)
    {
        $this->errors[] = $message;
    }

    /**
     * Return if there are errors
     *
     * @return bool Request has errors
     */
    public function has_errors()
    {
        return !(empty($this->errors));
    }

    /**
     * Output error
     */
    public function output_errors($error = false)
    {
        // add error
        if ($error) {
            $this->add_error($error);
        }

        // output JSON
        $this->output(array('error' => implode("\n", $this->errors)), 400);
    }

    /**
     * Output OK
     */
    public function output_ok($message = false, $data = false)
    {
        $json = array('ok' => true);

        // add message
        if ($message) {
            $json['message'] = $message;
        }

        // add data
        if ($data) {
            $json['data'] = $data;
        }

        // output JSON
        $this->output($json);
    }

    /**
     * Output JSON
     */
    public function output($json, $status = 200)
    {
        // output json
        wp_send_json($json);
    }
}
