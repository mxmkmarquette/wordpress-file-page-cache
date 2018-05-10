<?php
namespace O10n;

/**
 * Link Filter Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class AdminLinkFilter extends Controller implements Controller_Interface
{
    private $google_hl; // Google &hl= language code
    private $host; // host of wordpress installation
    private $serverhost; // host of server

    // Google Analytics tracking
    private $utm_string = 'utm_source=wordpress&amp;utm_medium=plugin&amp;utm_term=optimization&amp;utm_campaign=o10n';

    // hosts to skip utm for
    private $no_utm = array(
        'encrypted.google.com',
        'github.com'
    );

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
            'AdminCP',
            'AdminView'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
        // detect language for Google documentation links
        $lgcode = strtolower(get_locale());
        if (strpos($lgcode, '_') !== false) {
            $lgparts = explode('_', $lgcode);
            $this->google_hl = $lgparts[0];
        }
        if ($this->google_hl === 'en') {
            $this->google_hl = '';
        }

        // extract host of website to detect external links
        $this->host = str_replace('www.', '', parse_url(home_url(), PHP_URL_HOST));
        $this->serverhost = str_replace('www.', '', $_SERVER['HTTP_HOST']);

        // filter links by esc_url()
        add_filter('clean_url', array($this, 'filter_url'), 10, 3);
    }

    /**
     * Filter links
     *
     * @param  string $url          The cleaned URL to be returned.
     * @param  string $original_url The URL prior to cleaning.
     * @param  string $_context     If 'display', replace ampersands and single quotes only.
     * @return string Modified URL
     */
    final public function filter_url($url, $original_url, $_context)
    {
        // not in PageSpeed control panel
        if (!$this->AdminView->active()) {
            return $url;
        }

        // regex search & replace
        $s = $r = array();

        // google language links
        if (strpos($url, 'hl=') !== false) {
            $s[] = '#(\?|\&|;)hl=([a-z\-]{2,7})?#is';
            if ($this->google_hl) {
                $r[] = '$1hl=' . $this->google_hl;
            } else {
                $r[] = '$1';
            }
        }

        // search/replace
        if (!empty($s)) {
            $url = rtrim(preg_replace($s, $r, $url), '?&');
            if (strpos($url, '?&') !== false) {
                $url = preg_replace('|\?&([\#a-z0-9]{3,7};)?|is', '?', $url);
            }
        }

        // local url
        if (!preg_match('|^(http(s)?:)?//|Ui', $url)) {
            return $url;
        }

        // add Google Analytics tracking code to external links
        $urlhost = str_replace('www.', '', parse_url($url, PHP_URL_HOST));
        if (strpos($urlhost, $this->host) === false && strpos($urlhost, $this->serverhost) === false && strpos($url, 'utm_source') === false) {

            // verify if host matches no-utm list
            $no_utm = false;
            foreach ($this->no_utm as $str) {
                if (strpos($urlhost, $str) !== false) {
                    $no_utm = true;
                    break;
                }
            }

            if (!$no_utm) {
                if (strpos($url, '?') !== false) {
                    $url .= '&';
                } else {
                    $url .= '?';
                }
                $url .= $this->utm_string;
            }
        }
        
        return $url;
    }
}
