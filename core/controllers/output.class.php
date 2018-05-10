<?php
namespace O10n;

/**
 * Output Buffer Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class Output extends Controller implements Controller_Interface
{
    protected $allow_public = true;

    // output HTML search & replace filters
    private $search = array();
    private $replace = array();
    private $search_regex = array();
    private $replace_regex = array();
    
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
            'env'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
        // disabled
        if (!$this->env->is_optimization() || is_admin()) {
            return;
        }

        // start output buffer on WordPress init hook
        add_action('init', array( $this, 'ob_start'), PHP_INT_MAX);
    }

    /**
     * Start output buffer
     */
    final public function ob_start()
    {
        // disabled
        if (!$this->env->is_optimization()) {
            return;
        }

        // set output buffer
        ob_start(array($this, 'process_buffer'));
    }

    /**
     * Process output buffer returned from ob_start
     *
     * @param  string $buffer Output buffer
     * @return string Modified HTML.
     */
    final public function process_buffer($buffer)
    {
        // disabled
        if (!$this->env->is_optimization()) {
            return $buffer;
        }

        // apply pre HTML optimization filters
        try {
            $filtered = apply_filters('o10n_html_pre', $buffer);
        } catch (Exception $err) {
            $filtered = false;
        }

        // update buffer
        if ($filtered && $filtered !== $buffer) {
            $buffer = $filtered;
        }

        // apply search and replace
        $buffer = $this->apply_search_replace($buffer);

        // apply HTML filters
        try {
            $buffer = apply_filters('o10n_html', $buffer);
        } catch (Exception $err) {
            if ($err->isFatal()) {
                return $this->fatal_error($err);
            }
        }

        // apply search and replace
        $buffer = $this->apply_search_replace($buffer);

        // apply HTTP header filters
        try {
            apply_filters('o10n_headers', $buffer);
        } catch (Exception $err) {
            if ($err->isFatal()) {
                return $this->fatal_error($err);
            }
        }

        // apply final HTML filters
        try {
            $buffer = apply_filters('o10n_html_final', $buffer);
        } catch (Exception $err) {
            if ($err->isFatal()) {
                return $this->fatal_error($err);
            }
        }

        return $buffer;
    }

    /**
     * Apply search & replace on buffer
     *
     * @param  string $buffer Output buffer
     * @return string Modified HTML.
     */
    final private function apply_search_replace($buffer)
    {

        // apply search & replace
        if (!empty($this->search)) {
            $buffer = str_replace($this->search, $this->replace, $buffer);

            // reset
            $this->search = array();
            $this->replace = array();
        }

        // apply regex search & replace
        if (!empty($this->search_regex)) {
            try {
                $buffer = @preg_replace($this->search_regex, $this->replace_regex, $buffer);
            } catch (\Exception $err) {
                // @todo log error
            }

            // reset
            $this->search_regex = array();
            $this->replace_regex = array();
        }

        return $buffer;
    }

    /**
     * Add search & replace filter
     */
    final public function add_search_replace($search, $replace, $regex = false)
    {
        if (is_string($search)) {
            $search = array($search);
            $replace = array($replace);
        }

        if (is_array($search)) {
            if (!is_array($replace) || count($search) !== count($replace)) {
                throw new Exception('Invalid search & replace filter.', 'output');
            }
            foreach ($search as $index => $str) {
                if ($regex) {
                    $this->search_regex[] = $str;
                    $this->replace_regex[] = $replace[$index];
                } else {
                    $this->search[] = $str;
                    $this->replace[] = $replace[$index];
                }
            }
        }
    }

    /**
     * Return fatal error
     *
     * @return string Fatal error HTML
     */
    final private function fatal_error($error)
    {
        if (!headers_sent()) {
            $protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
            header($protocol . ' 503 Service Temporarily Unavailable', true, 503);
            header('Retry-After: 60', true);
            nocache_headers();
            header('Content-Type: text/html; charset=utf-8');
        }

        if ($this->env->is_debug()) {
            $message = $error->getMessage();
        } else {
            $message = 'Performance Optimization plugin failed to load. Please contact the administrator of this website.';
        }

        $have_gettext = function_exists('__');

        $message = "<p>$message</p>";

        if (empty($title)) {
            $title = $have_gettext ? __('Performance Optimization &rsaquo; Error') : 'Performance Optimization &rsaquo; Error';
        }

        $text_direction = 'ltr';
        if (isset($r['text_direction']) && 'rtl' == $r['text_direction']) {
            $text_direction = 'rtl';
        } elseif (function_exists('is_rtl') && is_rtl()) {
            $text_direction = 'rtl';
        }

        // based on wp_die() output
        // @todo
        $output = '
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" ' . ((function_exists('language_attributes') && function_exists('is_rtl')) ? get_language_attributes() : "dir='$text_direction'") . '>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width">
    <meta name="robots" content="noindex,follow" />
    <title>' . $title . '</title>
    <style type="text/css">
        html {
            background: #f1f1f1;
        }
        body {
            background: #fff;
            color: #444;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            margin: 2em auto;
            padding: 1em 2em;
            max-width: 700px;
            -webkit-box-shadow: 0 1px 3px rgba(0,0,0,0.13);
            box-shadow: 0 1px 3px rgba(0,0,0,0.13);
        }
        h1 {
            border-bottom: 1px solid #dadada;
            clear: both;
            color: #666;
            font-size: 24px;
            margin: 30px 0 0 0;
            padding: 0;
            padding-bottom: 7px;
        }
        #error-page {
            margin-top: 50px;
        }
        #error-page p {
            font-size: 14px;
            line-height: 1.5;
            margin: 25px 0 20px;
        }
        #error-page code {
            font-family: Consolas, Monaco, monospace;
        }
        ul li {
            margin-bottom: 10px;
            font-size: 14px ;
        }
        a {
            color: #0073aa;
        }
        a:hover,
        a:active {
            color: #00a0d2;
        }
        a:focus {
            color: #124964;
            -webkit-box-shadow:
                0 0 0 1px #5b9dd9,
                0 0 2px 1px rgba(30, 140, 190, .8);
            box-shadow:
                0 0 0 1px #5b9dd9,
                0 0 2px 1px rgba(30, 140, 190, .8);
            outline: none;
        }
        .button {
            background: #f7f7f7;
            border: 1px solid #ccc;
            color: #555;
            display: inline-block;
            text-decoration: none;
            font-size: 13px;
            line-height: 26px;
            height: 28px;
            margin: 0;
            padding: 0 10px 1px;
            cursor: pointer;
            -webkit-border-radius: 3px;
            -webkit-appearance: none;
            border-radius: 3px;
            white-space: nowrap;
            -webkit-box-sizing: border-box;
            -moz-box-sizing:    border-box;
            box-sizing:         border-box;

            -webkit-box-shadow: 0 1px 0 #ccc;
            box-shadow: 0 1px 0 #ccc;
            vertical-align: top;
        }

        .button.button-large {
            height: 30px;
            line-height: 28px;
            padding: 0 12px 2px;
        }

        .button:hover,
        .button:focus {
            background: #fafafa;
            border-color: #999;
            color: #23282d;
        }

        .button:focus  {
            border-color: #5b9dd9;
            -webkit-box-shadow: 0 0 3px rgba( 0, 115, 170, .8 );
            box-shadow: 0 0 3px rgba( 0, 115, 170, .8 );
            outline: none;
        }

        .button:active {
            background: #eee;
            border-color: #999;
            -webkit-box-shadow: inset 0 2px 5px -3px rgba( 0, 0, 0, 0.5 );
            box-shadow: inset 0 2px 5px -3px rgba( 0, 0, 0, 0.5 );
            -webkit-transform: translateY(1px);
            -ms-transform: translateY(1px);
            transform: translateY(1px);
        }

        ' . (('rtl' == $text_direction) ? 'body { font-family: Tahoma, Arial; }' : '') . '
    </style>
</head>
<body id="error-page">
    <h1>Performance Optimization</h1>
    <h3>Fatal Error</h3>
    ' . $message . '
</body>
</html>';
        
        return $output;
    }
}
