<?php
/**
 * Admin header tab template
 *
 * @package    optimization
 * @subpackage optimization/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

// admin base
$admin_base = $view->admin_base();

// tabs
$tabs = $view->tabs();

// active tab
list($base_tab, $active_tab, $active_subtab) = $view->active_tab();

?>
	<nav class="nav-tab-wrapper">
        <a class="nav-tab" href="<?php print esc_url(add_query_arg(array('page' => 'o10n'), admin_url('admin.php'))); ?>" style="float:right;" title="WordPress WPO"><span class="dashicons dashicons-dashboard"></span> WPO</a>
<?php
foreach ($tabs as $tabkey => $tabinfo) {
    $class = '';
    if (isset($tabinfo['pagekey']) && $tabinfo['pagekey']) {
        if (isset($_GET['page']) && $_GET['page'] === 'o10n-' . $tabinfo['pagekey']) {
            $class = ' nav-tab-active';

            $tabs[$tabkey]['selected'] = true;
        }
    } elseif (!isset($_GET['page']) || $_GET['page'] === $base_tab) {
        if ($tabkey === $active_tab) {
            $class = ' nav-tab-active';
            $tabs[$tabkey]['selected'] = true;
        }
    }
    
    if (isset($tabinfo['class'])) {
        $class .= ' ' . $tabinfo['class'];
    }

    $params = array();

    // WPO plugin
    if (isset($tabinfo['pagekey'])) {
        $params['page'] = 'o10n-'.$tabinfo['pagekey'];
    } elseif (defined('O10N_WPO_VERSION')) {
        switch ($tabkey) {
            case "intro":
                $params['page'] = $base_tab . '-' . $tabkey;
            break;
            default:

                if (isset($tabinfo['is_tab_of'])) {
                    $params['page'] = $base_tab . '-' . $tabkey . (($tabinfo['is_tab_of']) ? $tabinfo['is_tab_of'] : '');
                    $params['tab'] = $tabkey;
                } else {
                    $params['page'] = $base_tab . '-'.$tabkey;
                }
            break;
        }
    } else {
        $params['page'] = $base_tab;
        $params['tab'] = $tabkey;
    }

    $base = (isset($tabinfo['admin_base'])) ? $tabinfo['admin_base'] : $admin_base;

    if (isset($tabinfo['href'])) {
        $url = $tabinfo['href'];
    } else {
        $url = add_query_arg($params, admin_url((isset($tabinfo['base'])) ? $tabinfo['base'] : $base));
    }
    if (isset($tabinfo['csstitle'])) {
        $title = '';
    } else {
        $title = $tabinfo['title'];
    }
    if (isset($tabinfo['target'])) {
        $target = $tabinfo['target'];
    } else {
        $target = '';
    }
    print '<a class="nav-tab'.$class.'" href="'.esc_url($url).'" title="'.esc_attr((isset($tabinfo['title_attr'])) ? $tabinfo['title_attr'] : $tabinfo['title']).'"'.(($target) ? ' target="'.esc_attr($target).'"' : '').'>'.$title.'</a>';
}
?>

	</nav>
<?php
// print sub tabs

foreach ($tabs as $tabkey => $tabinfo) {
    if (isset($tabinfo['subtabs'])) {
        print '<nav class="nav-tab-wrapper nav-subtab-wrapper"'.((!isset($tabinfo['selected']) || !$tabinfo['selected']) ? ' style="display:none;"' : '').'>';

        foreach ($tabinfo['subtabs'] as $tabkey => $tabinfo) {
            $class = ($tabkey === $active_subtab) ? ' nav-tab-active' : '';

            $params = array();

            if (isset($tabinfo['pagekey'])) {
                $params['page'] = 'o10n-'.$tabinfo['pagekey'];
            } elseif (defined('O10N_WPO_VERSION')) { // WPO plugin
                switch ($active_tab_key) {
                    case "intro":
                        $params['page'] = $base_tab . '-' . $tabkey;
                    break;
                    default:

                        if (isset($tabinfo['is_tab_of'])) {
                            $params['page'] = $base_tab . '-' . $tabkey . (($tabinfo['is_tab_of']) ? $tabinfo['is_tab_of'] : '');
                            $params['tab'] = $tabkey;
                        } else {
                            $params['page'] = $base_tab . '-'.$tabkey;
                        }
                    break;
                }

                // add tab parameter
                if (!isset($tabinfo['primary']) || !$tabinfo['primary']) {
                    $params['tab'] = $tabkey;
                }
            } else {
                $params['page'] = $base_tab;
                $params['tab'] = $tabkey;
            }

            // custom href
            if (isset($tabinfo['href'])) {
                $url = $tabinfo['href'];
            } else {
                $url = add_query_arg($params, admin_url((isset($tabinfo['base'])) ? $tabinfo['base'] : $admin_base));
            }
            echo '<a class="nav-tab'.$class.'" href="'.esc_attr($url).'">'.$tabinfo['title'].'</a>';
        }

        print '</nav>';
    }
}

?>
