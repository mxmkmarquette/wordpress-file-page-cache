<?php
namespace O10n;

/**
 * Settings form header template
 *
 * @package    optimization
 * @subpackage optimization/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH') || !defined('O10N_ADMIN')) {
    exit;
}

?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php?action=o10n_update')); ?>" class="clearfix json-form">

    <div id="post-body" class="metabox-holder">
        <div id="post-body-content">
            <div class="postbox">
                <?php wp_nonce_field('save_settings'); ?>
                <input type="hidden" id="ajax_nonce" value="<?php print esc_attr(wp_create_nonce('o10n')); ?>" />
                <input type="hidden" name="view" value="<?php print esc_attr($view); ?>" />
                <?php 
                    // add tab
                    $tab = (isset($_REQUEST['tab'])) ? trim($_REQUEST['tab']) : false;
                    if ($tab) {
                        print '<input type="hidden" name="tab" value="'.esc_attr($tab).'" />';
                    }
                ?>

    <div class="inside form-contents form-visible">
