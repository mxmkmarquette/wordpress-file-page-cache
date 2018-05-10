<?php
namespace O10n;

/**
 * HTTP GET/POST Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class Http extends Controller implements Controller_Interface
{
    private $is_ssl = null;
    private $user_agent_string; // user agent string

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
            'file'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
    }

    /**
     * Return user agent
     *
     * @return string User agent string
     */
    final private function user_agent()
    {
        if (!$this->user_agent_string) {

            // allow modification of outgoing user agent, e.g. "Website Crawler"
            $this->user_agent_string = apply_filters('o10n_http_useragent', 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.116 Safari/537.36');
        }
        
        return $this->user_agent_string;
    }

    /**
     * Remote request using wp_remote_get or wp_remote_head
     *
     * @param  string $type   GET or HEAD.
     * @param  string $url    URL to request.
     * @param  array  $config wp_remote_get config
     * @return object WP_HTTP response
     */
    final public function request($request_type, $url, $config = array())
    {
        // wp_remote_get config
        $config = array_merge(array(
            'timeout' => 60,
            'redirection' => 5,
            'sslverify' => false,

            // Chrome Generic Win10
            // @link https://techblog.willshouse.com/2012/01/03/most-common-user-agents/
            'user-agent' => $this->user_agent(),
        ), $config);

        // request headers
        if (!isset($config['headers']) || !is_array($config['headers'])) {
            $config['headers'] = array();
        }

        // disable keep-alive
        $config['headers']['Connection'] = "close";

        // start request
        if ($request_type === 'HEAD') {
            $response = wp_remote_head($url, $config);
        } else {
            $response = wp_remote_get($url, $config);
        }

        // convert WP_Error to exception
        //
        if (is_wp_error($response)) {
            throw new HTTPException($response->get_error_message(), $response->get_error_code(), wp_remote_retrieve_response_code($response), $response, $request_type);
        }

        // return WP_HTTP response
        return $response;
    }

    /**
     * GET request
     *
     * @param  string $url    URL to request.
     * @param  array  $config wp_remote_get config
     * @return string Result from request
     */
    final public function get($url, $config = array(), $returnBody = true)
    {
        // HTTP request
        $response = $this->request('GET', $url, $config);

        if ($returnBody) {

            // return HTTP code and body
            return array(
                wp_remote_retrieve_response_code($response), // HTTP code
                wp_remote_retrieve_body($response), // body
                $response
            );
        }

        // return WP_HTTP response
        return $response;
    }

    /**
     * HEAD request
     *
     * @param  string $url    URL to request.
     * @param  array  $config wp_remote_get config
     * @return string Result from request
     */
    final public function head($url, $config = array(), $returnHeaders = true)
    {
        // HTTP request
        $response = $this->request('HEAD', $url, $config);

        if ($returnHeaders) {

            // return HTTP code and headers
            return array(
                wp_remote_retrieve_response_code($response), // HTTP code
                wp_remote_retrieve_headers($response), // headers
                $response
            );
        }

        // return WP_HTTP response
        return $response;
    }
}

/**
 * HTTP Exception Controller
 */
final class HTTPException extends \Exception
{
    protected $request_type;   // HTTP request type (GET / HEAD)
    protected $http_status = '0';   // HTTP status
    protected $http_response;       // original WP_HTTP response
    
    /**
     * Construct the exception
     *
     * @param string $message     Error message.
     * @param string $http_status HTTP status
     */
    public function __construct($message, $error_code, $status, $response, $request_type)
    {
        $this->http_status = $http_status; // HTTP status
        $this->http_response = $response; // HTTP status
        $this->request_type = $request_type; // HTTP request type
        
        // process exception
        parent::__construct($message, 1); // $error_code
    }

    // return HTTP status
    public function getStatus()
    {
        return $this->http_status;
    }

    // return WP_HTTP response
    public function getResponse()
    {
        return $this->http_response;
    }

    // return request type
    public function getRequestType()
    {
        return $this->request_type;
    }
}
