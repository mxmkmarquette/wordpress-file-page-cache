<?php
namespace O10n;

/**
 * Admin Control-Panel Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class AdminCP extends Controller implements Controller_Interface
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
            'admin',
            'options',
            'AdminAjax',
            'AdminClient'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {

        // set admin flag
        define('O10N_ADMIN', true);

        // action links
        add_action('admin_post_optimization_clear_store', array($this,  'clear_store'));

        // error notices
        add_action('o10n_notices', array($this, 'show_notices'));

        // AJAX page search
        add_action('wp_ajax_o10n_page_search', array($this, 'ajax_page_search'));

        // enqueue hook / set client variables
        add_action('admin_enqueue_scripts', array( $this, 'enqueue' ), 10);

        // create optimization plugin group
        add_action('pre_current_active_plugins', array( $this, 'plugin_title'), 10);
    }

    /**
     * Enqueue scripts and styles
     */
    final public function enqueue()
    {
        // not in /wp-admin/
        if (!is_admin()) {
            return;
        }

        // add page search optgroups to client
        $this->AdminClient->set_config('page_search_optgroups', $this->page_search_optgroups());

        // add phrases
        $this->AdminClient->set_lg(array(
            'search_page_placeholder' => __('Search a post/page/category by ID, name or URL...', 'o10n')
        ));
    }

    /**
     * Plugin title modification
     */
    public function plugin_title()
    {
        ?><script>var o10n_toggle_plugins = function() {
    var state = jQuery('#o10n_plugins .check-column input').first().prop('disabled');
    if (state) {
        jQuery('#o10n_plugins .check-column input').prop('disabled', false);
        jQuery('#o10n_plugins_headline').fadeIn(250);
        jQuery('#o10n_plugins').show();
        jQuery('#o10n_plugins_collapsed').hide();
        jQuery('#o10n_toggle .arr').removeClass('dashicons-arrow-down-alt2');
        jQuery('#o10n_toggle .arr').addClass('dashicons-arrow-up-alt2');
    } else {
        jQuery('#o10n_plugins .check-column input').prop('disabled', 'disabled');
        jQuery('#o10n_plugins_headline').hide();
        jQuery('#o10n_plugins').hide();
        jQuery('#o10n_plugins_collapsed').show();
        jQuery('#o10n_toggle .arr').addClass('dashicons-arrow-down-alt2');
        jQuery('#o10n_toggle .arr').removeClass('dashicons-arrow-up-alt2');
    }
    
};
var o10n_select_all = function() {
    var state = jQuery('#o10n_plugins .check-column input').first().prop('checked');
    jQuery('#o10n_plugins .check-column input').prop('checked', (state) ? false : true);
};
jQuery(function($){

    // single plugin, ignore
    if ($('.plugin-version-author-uri a[href*="optimization.team"]', $('#bulk-action-form table.plugins tbody').first()).length <= 1) {
        return;
    }

    $('#bulk-action-form table.plugins').append('<thead><tr><td class="manage-column column-cb check-column" colspan="3"><h2 style="margin:0px;margin-left:5px;margin-bottom:5px;margin-top:5px;cursor:pointer;" id="o10n_toggle"><span class="dashicons dashicons-arrow-down-alt2 arr"></span> WordPress WPO Collection</h2></td></tr><tr id="o10n_plugins_collapsed"><td colspan="3" style="padding:0px;padding-left:10px;padding-top:5px;"><p style="color:#aaa;">The optimization plugins are hidden.</p></td></tr><tr id="o10n_plugins_headline" style="display:none;"><td colspan="3"><p><strong>Warning:</strong> The optimization plugins share a plugin core for single plugin performance. Make sure that you install all updates to ensure that all plugins are updated to the latest base core version.</p><p>The plugins are in beta. We will make updating the plugins more easy in the future.<p><button type="button" id="o10n_select_all" class="button button-small">Toggle Select All</button></p></td></tr></thead><tbody id="o10n_plugins" style="display:none;"></tbody>');

    $('.plugin-version-author-uri a[href*="optimization.team"]', $('#bulk-action-form table.plugins tbody').first()).each(function(index,a) {
        var slug = $(a).closest('tr').data('slug');
        $('tr[data-slug="'+slug+'"]').each(function(index,tr) {
            $('#o10n_plugins').append($(tr));
        });
    });
    $('#o10n_plugins .check-column input').prop('disabled', 'disabled');
    $('#o10n_toggle').on('click', o10n_toggle_plugins);
    $('#o10n_select_all').on('click', o10n_select_all);
});</script><?php
    }

    /**
     * Show admin notices
     */
    public function show_notices()
    {
        // get notices
        $notices = $this->admin->get_notices();

        $persisted_notices = array();
        $noticerows = array();
        foreach ($notices as $notice) {
            switch ($notice['type']) {
                case "ERROR":
                    $notice_class = 'error';
                break;
                case "SUCCESS":
                    $notice_class = 'updated';
                break;
                default:
                    $notice_class = 'notice';
                break;
            }

            $notice['flap-title'] = 'Test';
            if (date('Y/m/d', time()) === date('Y/m/d', $notice['date'])) {
                $datetext = date('H:i', $notice['date']);
            } else {
                $datetext = date_i18n(get_option('date_format'), $notice['date']);
            }

            $count = false;
            $loglink = '#';
            if (isset($notice['count']) && $notice['count'] > 1) {
                $count = ' (<a href="'.$loglink.'" class="log">'.$notice['count'].' errors</a>)';
            } else {
                $count = ' (<a href="'.$loglink.'" class="log">log</a>)';
            }

            switch ($notice['category']) {
                case "config":
                    $noticetext = '<div class="inline notice '.$notice_class.' notice-flap is-dismissible" rel="' . esc_attr($notice['hash']) . '">
<h4 class="flap">'.__('Config', 'o10n').'</h4>
<h4 class="flap-date">' . $datetext . '</h4>
<h4 class="flap-log">' . $count . '</h4>
<div class="clear"></div>';
                break;
                default:
                    $noticetext = '<div class="inline notice '.$notice_class.' is-dismissible" rel="' . esc_attr($notice['hash']) . '">';
                break;
            }

            $noticetext .= '<div class="notice-text">
                <p>
                    '.__($notice['text'], 'o10n').'
                </p>';

            // stack trace
            if (isset($notice['trace'])) {
                $noticetext .= '<textarea style="width:100%;height:30px;" onfocus="jQuery(this).css(\'height\',\'300px\');" onblur="jQuery(this).css(\'height\',\'30px\');">'.esc_html($notice['trace']).'</textarea>';
            }

            $noticetext .= '</div>
            </div>';

            $noticerows[] = $noticetext;
                
            // register notice views
            if (!isset($notice['views'])) {
                $notice['views'] = 0;
            }
            $notice['views']++;

            // persist notice
            if (isset($notice['persist'])) {
                $expired = false;

                switch ($notice['persist']) {
                    case "expire":

                        // specific expire date
                        if (isset($notice['expire_date'])) {
                            if (time() > $notice['expire_date']) {

                                // expired
                                $expired = true;
                            }
                        } else {

                            // expire when viewed more than 5 times and older than 5 minutes
                            if (isset($notice['date']) && $notice['date'] < ((time() - 60 * 5)) && $notice['views'] > 5) {

                                // expired
                                $expired = true;
                            }
                        }
                    break;
                }

                // notice has expired
                if ($expired) {
                    continue 1;
                }

                $persisted_notices[] = $notice;
            }
        } ?>
<div><?php print implode('', $noticerows); ?></div>
<?php

        update_option('o10n_notices', $persisted_notices, false);
    }


    /**
     * Return optgroup json for page search
     *
     * @return array Page search optgroups
     */
    final public function page_search_optgroups()
    {
        $optgroups = array();

        $optgroups[] = array(
            'value' => 'posts',
            'label' => __('Posts')
        );
        $optgroups[] = array(
            'value' => 'pages',
            'label' => __('Pages')
        );
        $optgroups[] = array(
            'value' => 'categories',
            'label' => __('Categories')
        );
        if (class_exists('WooCommerce')) {
            $optgroups[] = array(
                'value' => 'woocommerce',
                'label' => __('WooCommerce')
            );
        }

        // apply optgroups filter
        $optgroups = apply_filters('o10n_page_search_optgroups', $optgroups);

        return $optgroups;
    }

    /**
     * Return options for page selection menu
     */
    final public function ajax_page_search()
    {
        // parse request
        $request = $this->AdminAjax->request();

        // posted query
        $query = $request->data('query', '');
        $limit = intval($request->data('limit', 10));
        if (!is_numeric($limit) || $limit < 1 || $limit > 30) {
            $limit = 10;
        }

        // apply query filter
        $query = apply_filters('o10n_page_search_query', $query);

        // verify
        if (!$query) {
            $request->output_errors(__('You did not enter a search query.', 'o10n'));
        }

        // enable URL (slug) search
        // @Emilybkk
        if (preg_match('|^http(s)?://|Ui', $query) || substr($query, 0, 1) === '/') {
            $slug_query = array_pop(explode('/', trim(preg_replace('|^http(s)://[^/]+/|Ui', '', $query), '/')));
        } else {
            $slug_query = false;
        }

        // apply slug query filter
        $slug_query = apply_filters('o10n_page_search_slug_query', $slug_query, $query);

        // apply search config filters
        $skip_post_types = apply_filters('o10n_page_search_skip_post_types', array('revision','nav_menu_item'));

        // results
        $results = array();

        // query available post types
        $post_types = get_post_types();
        foreach ($post_types as $pt) {

            // skip post types
            if (in_array($pt, $skip_post_types)) {
                continue 1;
            }
            
            // stop processing
            if (count($results) >= $limit) {
                break;
            }

            // slug/URL query
            if ($slug_query) {
                $args = array( 'post_type' => $pt, 'posts_per_page' => $limit, 'name' => $slug_query );
            } else {
                $args = array( 'post_type' => $pt, 'posts_per_page' => $limit, 's' => $query );
            }
            
            // search posts
            query_posts($args);
            if (have_posts()) {
                while (have_posts()) {
                    the_post();

                    // apply search result filter
                    $custom_result = apply_filters('o10n_page_search_post_result', false, $pt, $wp_query->post, $query, $slug_query);
                    if ($custom_result && is_array($custom_result) && isset($custom_result['name']) && isset($custom_result['value'])) {
                        $results[] = $custom_result;
                    } else {
                        switch ($pt) {
                            case "post":
                                $results[] = array(
                                    'optgroup' => 'posts',
                                    'value' => get_permalink($wp_query->post->ID),
                                    'name' => get_the_ID() . '. ' . str_replace(home_url(), '', get_permalink(get_the_ID())) . ' - ' . get_the_title()
                                );
                            break;
                            case "product":
                                $results[] = array(
                                    'optgroup' => 'woocommerce',
                                    'value' => get_permalink(get_the_ID()),
                                    'name' => get_the_ID() . '. ' . str_replace(home_url(), '', get_permalink(get_the_ID())) . ' - ' . get_the_title()
                                );
                            break;
                            default:
                                $results[] = array(
                                    'optgroup' => 'pages',
                                    'value' => get_permalink(get_the_ID()),
                                    'name' => get_the_ID() . '. ' . str_replace(home_url(), '', get_permalink(get_the_ID())) . ' - ' . get_the_title()
                                );
                            break;
                        }
                    }
                }
            }
        }

        // apply search config filters
        $skip_taxonomies = apply_filters('o10n_page_search_skip_taxonomies', array());

        // search taxonomies
        if (count($results) < $limit) {

            // query available taxonomies
            $taxonomies = get_taxonomies();
            if (!empty($taxonomies)) {
                foreach ($taxonomies as $taxonomy) {

                    // skip post types
                    if (in_array($taxonomy, $skip_taxonomies)) {
                        continue 1;
                    }
            
                    // stop processing
                    if (count($results) >= $limit) {
                        break;
                    }

                    // apply search result filter
                    $term_query = apply_filters('o10n_page_search_term_query', false, $taxonomy, $query, $slug_query);
                    if (!$term_query) {
                        switch ($taxonomy) {
                            case "category":
                            case "post_tag":
                            case "product_cat":
                            case "product_brand":

                                // slug/URL query
                                if ($slug_query) {
                                    $term_query = array(
                                        'orderby' => 'title',
                                        'order' => 'ASC',
                                        'hide_empty' => false,
                                        'number' => $limit,
                                        'name' => $slug_query
                                    );
                                } else {
                                    $term_query = array(
                                        'orderby' => 'title',
                                        'order' => 'ASC',
                                        'hide_empty' => false,
                                        'number' => $limit,
                                        'name__like' => $query
                                    );
                                }
                            break;
                        }
                    }

                    // query terms
                    $terms = get_terms($taxonomy, $term_query);
                    if ($terms) {
                        foreach ($terms as $term) {
                                
                            // stop processing
                            if (count($results) >= $limit) {
                                break;
                            }

                            // apply search result filter
                            $custom_result = apply_filters('o10n_page_search_term_result', false, $taxonomy, $term, $query, $slug_query);
                            if ($custom_result && is_array($custom_result) && isset($custom_result['name']) && isset($custom_result['value'])) {
                                $results[] = $custom_result;
                            } else {
                                switch ($taxonomy) {
                                    case "product_cat":
                                    case "product_brand":
                                        $results[] = array(
                                            'class' => 'woocommerce',
                                            'value' => get_term_link($term->slug, $taxonomy),
                                            'name' => $term->term_id.'. ' . str_replace(home_url(), '', get_category_link($term->term_id)) . ' - ' . $term->name
                                        );
                                    break;
                                    default:
                                        $results[] = array(
                                            'class' => 'categories',
                                            'value' => get_category_link($term->term_id),
                                            'name' => $term->term_id.'. ' . str_replace(home_url(), '', get_category_link($term->term_id)) . ' - ' . $term->name
                                        );
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // OK
        $request->output_ok(false, $results);
    }
}
