<?php
namespace O10n;

/**
 * File page cache admin template
 *
 * @package    optimization
 * @subpackage optimization/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH') || !defined('O10N_ADMIN')) {
    exit;
}

// print form header
$this->form_start(__('File Page Cache', 'o10n'), 'filecache');

?>

<table class="form-table">
    <tr valign="top">
        <th scope="row">Page Cache</th>
        <td>
            
            <label><input type="checkbox" name="o10n[filecache.enabled]" data-json-ns="1" value="1"<?php $checked('filecache.enabled'); ?>> Enable
</label>
            <p class="description" style="margin-bottom:1em;">When enabled, HTML pages are cached using static files.</p>

            <div class="suboption" data-ns="filecache"<?php $visible('filecache'); ?>>
                <label><input type="checkbox" value="1" name="o10n[filecache.filter.enabled]" data-json-ns="1"<?php $checked('filecache.filter.enabled'); ?> /> Enable cache policy</label>
                <span data-ns="filecache.filter"<?php $visible('filecache.filter'); ?>>
                    <select name="o10n[filecache.filter.type]" data-ns-change="filecache.filter" data-json-default="<?php print esc_attr(json_encode('include')); ?>">
                        <option value="include"<?php $selected('filecache.filter.type', 'include'); ?>>Include by default</option>
                        <option value="exclude"<?php $selected('filecache.filter.type', 'exclude'); ?>>Exclude by default</option>
                    </select>
                </span>
                <p class="description">The cache policy filter enables to include or exclude pages from the cache.</p>

                <p class="suboption info_yellow">You can enable or disable the page cache using the method <code>\O10n\page_cache(true|false);</code>.</p>
            </div>
</td></tr>
    
    <tr valign="top" data-ns="filecache.filter"<?php $visible('filecache.filter'); ?>>
        <th scope="row">&nbsp;</th>
        <td style="padding-top:0px;">
            <h5 class="h">&nbsp;Cache Policy Filter</h5>
            <div id="filecache-filter-config"><div class="loading-json-editor"><?php print __('Loading JSON editor...', 'o10n'); ?></div></div>
            <input type="hidden" class="json" name="o10n[filecache.filter.config]" data-json-type="json-array" data-json-editor-height="auto" data-json-editor-init="1" value="<?php print esc_attr($json('filecache.filter.config')); ?>" />
            <p class="description">Enter a JSON array with objects. (<a href="javascript:void(0);" onclick="jQuery('#cache_policy_example').fadeToggle();">show example</a>)</p>
            <div class="info_yellow" id="cache_policy_example" style="display:none;"><strong>Example:</strong> <pre class="clickselect" title="<?php print esc_attr('Click to select', 'optimization'); ?>" style="cursor:copy;padding: 10px;margin: 0 1px;margin-top:5px;font-size: 13px;">[
    "page-url",
    "/other/page/url",
    {
        "match": "uri",
        "string": "/page-uri-(x|y)/",
        "regex": true
    },
    {
        "match": "condition",
        "method": "is_page",
        "arguments": [[1,6,19]]
    }
]</pre></div>

    
                <div class="suboption" data-ns="filecache"<?php $visible('filecache');  ?>>

                    <h5 class="h">&nbsp;Cache Expire</h5>
                    <input type="number" style="width:120px;" min="1" name="o10n[filecache.expire]" value="<?php $value('filecache.expire', 86400); ?>" placeholder="86400" />
                    <p class="description">Enter a time in seconds for the cache to expire. The default is <code>86400</code> seconds (1 day).</p>
                </div>

        </td>
    </tr>

    <tr valign="top" data-ns="filecache"<?php $visible('filecache'); ?>>
        <th scope="row">PHP Opcache Boost</th>
        <td>
            
            <label><input type="checkbox" name="o10n[filecache.opcache.enabled]" data-json-ns="1" value="1"<?php $checked('filecache.opcache.enabled'); ?>> Enable
</label>
            <p class="description" style="margin-bottom:1em;">When enabled, cache files are stored in PHP Opcache.</p>

            <div class="suboption" data-ns="filecache.opcache"<?php $visible('filecache.opcache'); ?>>
                <label><input type="checkbox" value="1" name="o10n[filecache.opcache.filter.enabled]" data-json-ns="1"<?php $checked('filecache.opcache.filter.enabled'); ?> /> Enable boost policy</label>
                <span data-ns="filecache.opcache.   filter"<?php $visible('filecache.opcache.filter'); ?>>
                    <select name="o10n[filecache.opcache.filter.type]" data-ns-change="filecache.opcache.filter" data-json-default="<?php print esc_attr(json_encode('include')); ?>">
                        <option value="include"<?php $selected('filecache.opcache.filter.type', 'include'); ?>>Include by default</option>
                        <option value="exclude"<?php $selected('filecache.opcache.filter.type', 'exclude'); ?>>Exclude by default</option>
                    </select>
                </span>
                <p class="description">Due to the limited size of PHP Opcache, it may be required to specifically define the pages that use PHP Opcache. The filter enables to include or exclude pages from PHP Opcache.</p>

                <p class="suboption info_yellow">You can enable or disable the PHP Opcache boost using the method <code>\O10n\page_cache_boost(true|false);</code>.</p>
            </div>
</td></tr>
    
    <tr valign="top" data-ns="filecache.opcache.filter"<?php $visible('filecache.opcache.filter'); ?>>
        <th scope="row">&nbsp;</th>
        <td style="padding-top:0px;">
            <h5 class="h">&nbsp;Cache Boost Policy</h5>
            <div id="filecache-opcache-filter-config"><div class="loading-json-editor"><?php print __('Loading JSON editor...', 'o10n'); ?></div></div>
            <input type="hidden" class="json" name="o10n[filecache.opcache.filter.config]" data-json-type="json-array" data-json-editor-height="auto" data-json-editor-init="1" value="<?php print esc_attr($json('filecache.opcache.filter.config')); ?>" />
            <p class="description">Enter a JSON array with objects. (<a href="javascript:void(0);" onclick="jQuery('#opcache_example').fadeToggle();">show example</a>)</p>
            <div class="info_yellow" id="opcache_example" style="display:none;"><strong>Example:</strong> <pre class="clickselect" title="<?php print esc_attr('Click to select', 'optimization'); ?>" style="cursor:copy;padding: 10px;margin: 0 1px;margin-top:5px;font-size: 13px;">[
    "page-url",
    "/other/page/url",
    {
        "match": "uri",
        "string": "/page-uri-(x|y)/",
        "regex": true
    },
    {
        "match": "condition",
        "method": "is_page",
        "arguments": [[1,6,19]]
    }
]</pre></div>
        </td>
    </tr>
    </table>


<h3 style="margin-bottom:0px;" id="searchreplace">Search &amp; Replace</h3>
<?php $searchreplace = $get('filecache.replace', array()); ?>
<p class="description">This option enables to replace strings in the HTML before the page is cached. Enter JSON objects <span class="dashicons dashicons-editor-help"></span>.</p>
<div id="filecache-replace"><div class="loading-json-editor"><?php print __('Loading JSON editor...', 'optimization'); ?></div></div>
<input type="hidden" class="json" name="o10n[filecache.replace]" data-json-type="json-array" data-json-editor-height="auto" data-json-editor-init="1" value="<?php print esc_attr($json('filecache.replace')); ?>" />

<div class="info_yellow"><strong>Example:</strong> <code id="html_search_replace_example" class="clickselect" data-example-text="show string" title="<?php print esc_attr('Click to select', 'optimization'); ?>" style="cursor:copy;">{"search":"string to match","replace":"newstring"}</code> (<a href="javascript:void(0);" data-example="html_search_replace_example" data-example-html="<?php print esc_attr(__('{"search":"|string to (match)|i","replace":"newstring $1","regex":true}', 'optimization')); ?>">show regular expression</a>) </div>

<p>To replace HTML before optimization, use the <a href="<?php print add_query_arg(array('page' => 'o10n', 'tab' => 'html'), admin_url('admin.php')); ?>">HTML Optimization</a> plugin.</p>
<p>You can also add a search and replace configuration using the PHP function <code>\O10n\search_replace_before_cache($search,$replace[,$regex])</code>. (<a href="javascript:void(0);" onclick="jQuery('#wp_html_search_replace_example').fadeToggle();">show example</a>)</p>

<div id="wp_html_search_replace_example" style="display:none;">
<pre style="padding:10px;border:solid 1px #efefef;">add_action('init', function () {

    /* String replace */
    \O10n\search_replace_before_cache('string', 'replace');

    /* Regular Expression */
    \O10n\search_replace_before_cache(array(
        '|regex (string)|i',
        '|regex2 (string)|i'
    ), array(
        '$1',
        'xyz'
    ), true);

}, 10);
</pre>
</div>

<hr />
<?php
    submit_button(__('Save'), 'primary large', 'is_submit', false);

// print form header
$this->form_end();
