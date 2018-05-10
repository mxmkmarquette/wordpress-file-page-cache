<?php
namespace O10n;

/**
 * Admin Asset Editor Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class AdminEditor extends Controller implements Controller_Interface
{

    // asset types
    private $assetTypes = array(
        'css' => array(
            'ext' => '.css'
        ),
        'js' => array(
            'ext' => '.js'
        ));

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
           'file',
           'AdminClient',
           'AdminAjax'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {

        // load file contents
        add_action('wp_ajax_o10n_editor_load_file', array( $this, 'ajax_load_file'), 10);

        // save file contents
        add_action('wp_ajax_o10n_editor_save_file', array( $this, 'ajax_save_file'), 10);

        // create file contents
        add_action('wp_ajax_o10n_editor_create_file', array( $this, 'ajax_create_file'), 10);

        // delete file
        add_action('wp_ajax_o10n_editor_delete_file', array( $this, 'ajax_delete_file'), 10);
        
        // search files
        add_action('wp_ajax_o10n_editor_search_files', array( $this, 'ajax_search_files'), 10);

        // enqueue scripts
        add_action('admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), $this->first_priority);
    }
    /**
     * Enqueue scripts and styles
     */
    final public function enqueue_scripts()
    {
        // skip if user is not logged in
        if (!is_admin() || !is_user_logged_in()) {
            return;
        }

        // add phrases
        $this->AdminClient->set_lg(array(
            'loading_file' => __('Loading file...', 'o10n'),
            'saving_file' => __('Saving file...', 'o10n'),
            'file_saved' => __('File saved.', 'o10n'),
            'creating_file' => __('Creating file...', 'o10n'),
            'file_created' => __('File created.', 'o10n'),
            'deleting_file' => __('Deleting file...', 'o10n'),
            'file_deleted' => __('File deleted.', 'o10n'),
            'confirm_delete_file' => __('Are you sure that you want to delete this file?', 'o10n')
        ));
    }

    /**
     * Scan directory for assets of a specific type
     *
     * @param  string ` $dir       Directory to scan
     * @param  string   $assetType The asset type to scan
     * @return array    Array with details of assets in directory
     */
    final public function scandir($dir, $assetType)
    {

        // verify asset type
        if (!isset($this->assetTypes[$assetType])) {
            throw new Exception('Invalid asset type', 'admin');
        }
        $assetType = $this->assetTypes[$assetType];

        // verify directory (require files to be in ABSPATH of WordPress installation)
        if (strpos($dir, ABSPATH) !== 0) {
            return false;
        }

        $dir = $this->file->trailingslashit($dir);

        // directory contents
        $assets = array();

        // scan directory
        try {
            $extpos = strlen($assetType['ext']) * -1;
            $files = new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS);
            if (empty($files)) {
                return null;
            }
        } catch (\Exception $err) {
            return null;
        }

        // theme directory
        $theme_directory = $this->file->theme_directory();
        $theme_directoryname = basename($theme_directory);

        // abspath
        $abspath = $this->file->trailingslashit(ABSPATH);

        // extension position
        $extpos = strlen($assetType['ext']) * -1;

        // is theme directory?
        $is_theme_directory = (strpos($dir, $theme_directory) !== false) ? true : false;

        // read asset details
        $assets = array();
        foreach ($files as $fileinfo) {
            $asset = array();

            // filename
            $filename = $fileinfo->getFilename();

            // file path
            $file = $fileinfo->getPathname();

            // directory
            if ($fileinfo->isDir()) {
                $asset[$this->AdminClient->index('dir')] = 1;
                $asset[$this->AdminClient->index('filename')] = $filename . '/';

                if ($is_theme_directory) {
                    $asset[$this->AdminClient->index('filepath')] = $this->file->trailingslashit(str_replace($theme_directory, '', $file));
                    $asset[$this->AdminClient->index('theme')] = true;
                } else {
                    $asset[$this->AdminClient->index('filepath')] = $this->file->trailingslashit(str_replace($abspath, '/', $file));
                    $asset[$this->AdminClient->index('theme')] = false;
                }
                $asset[$this->AdminClient->index('time')] = 0;
                
                $assets[] = $asset;

                continue 1;
            }

            // check file extension
            if (substr($filename, $extpos) !== $assetType['ext']) {
                continue 1;
            }

            if ($is_theme_directory) {
                $asset[$this->AdminClient->index('filepath')] = str_replace($theme_directory, '', $file);
                $asset[$this->AdminClient->index('theme')] = true;
            } else {
                $asset[$this->AdminClient->index('filepath')] = str_replace($abspath, '/', $file);
                $asset[$this->AdminClient->index('theme')] = false;
            }

            // check file extension
            if (substr($file, (strlen($assetType['ext']) * -1)) !== $assetType['ext']) {
                continue 1;
            }

            $asset[$this->AdminClient->index('filename')] = $filename;
            $asset[$this->AdminClient->index('time')] = $fileinfo->getMTime();
            $asset[$this->AdminClient->index('date')] = sprintf(__('%s ago', 'o10n'), human_time_diff($asset[$this->AdminClient->index('time')]));
            $asset[$this->AdminClient->index('size')] = $fileinfo->getSize();

            $assets[] = $asset;
        }

        // return asset details
        return $assets;
    }

    /**
     * Load file content
     */
    final public function ajax_load_file()
    {
        // process AJAX request
        $request = $this->AdminAjax->request();

        // filename
        $filepath = $request->data('file');

        // verify input
        if (!$filepath) {
            $request->output_errors('no file');
        }
        
        // prevent relative path traversal
        // @todo investitate optimal protection
        // @link https://www.owasp.org/index.php/Path_Traversal
        if (strpos($filepath, '..') !== false) {
            $request->output_errors('double-dot relative paths are not allowed.');
        }

        // theme relative or absolute path?
        $theme_file = (substr($filepath, 0, 1) === '/') ? false : true;
       
        if ($theme_file) {
            $file = $this->file->theme_directory() . $filepath;
        } else {
            $file = $this->file->un_trailingslashit(ABSPATH) . $filepath;
        }

        // verify if file exists
        if (!file_exists($file)) {
            $request->output_errors(__('File does not exist: ' . $this->file->safe_path($file), 'o10n'));
        }

        // verify extension
        $valid = false;
        foreach ($this->assetTypes as $assetType) {
            if (substr($filepath, (strlen($assetType['ext']) * -1), strlen($assetType['ext'])) === $assetType['ext']) {
                $valid = true;
                break;
            }
        }
        if (!$valid) {
            $request->output_errors(__('Invalid file extension: ' . $this->file->safe_path($file), 'o10n'));
        }

        // data to return
        $data = array(
            $this->AdminClient->index('text') => file_get_contents($file),
            $this->AdminClient->index('size') => filesize($file)
        );

        // return file contents
        $request->output_ok(false, $data);
    }

    /**
     * Delete file
     */
    final public function ajax_delete_file()
    {
        // process AJAX request
        $request = $this->AdminAjax->request();

        // filename
        $filepath = $request->data('file');

        // verify input
        if (!$filepath) {
            $request->output_errors('no file');
        }
        
        // prevent relative path traversal
        // @todo investitate optimal protection
        // @link https://www.owasp.org/index.php/Path_Traversal
        if (strpos($filepath, '..') !== false) {
            $request->output_errors('double-dot relative paths are not allowed.');
        }

        // theme relative or absolute path?
        $theme_file = (substr($filepath, 0, 1) === '/') ? false : true;

        if ($theme_file) {
            $file = $this->file->theme_directory() . $filepath;
        } else {
            $file = $this->file->un_trailingslashit(ABSPATH) . $filepath;
        }

        // verify if file exists
        if (!file_exists($file)) {
            $request->output_errors(__('File does not exist: ' . $this->file->safe_path($file), 'o10n'));
        }

        // verify extension
        $valid = false;
        foreach ($this->assetTypes as $assetType) {
            if (substr($filepath, (strlen($assetType['ext']) * -1), strlen($assetType['ext'])) === $assetType['ext']) {
                $valid = true;
                break;
            }
        }
        if (!$valid) {
            $request->output_errors(__('Invalid file extension: ' . $this->file->safe_path($file), 'o10n'));
        }

        // delete file
        try {
            unlink($file);
        } catch (\Exception $err) {
            $request->output_errors($err->getMessage());
        }

        // verify
        if (file_exists($file)) {
            $request->output_errors('Failed to delete file.');
        }

        // return file contents
        $request->output_ok();
    }

    /**
     * Save file content
     */
    final public function ajax_save_file()
    {
        // process AJAX request
        $request = $this->AdminAjax->request();

        // filename
        $filepath = $request->data('file');

        // contents
        $text = $request->data('text');

        // verify input
        if (!$filepath) {
            $request->output_errors('no file');
        }
        
        // prevent relative path traversal
        // @todo investitate optimal protection
        // @link https://www.owasp.org/index.php/Path_Traversal
        if (strpos($filepath, '..') !== false) {
            $request->output_errors('double-dot relative paths are not allowed.');
        }

        // theme relative or absolute path?
        $theme_file = (substr($filepath, 0, 1) === '/') ? false : true;

        if ($theme_file) {
            $file = $this->file->theme_directory() . $filepath;
        } else {
            $file = $this->file->un_trailingslashit(ABSPATH) . $filepath;
        }

        // verify if file exists
        if (!file_exists($file)) {
            $request->output_errors(__('File does not exist: ' . $this->file->safe_path($file), 'o10n'));
        }

        // verify extension
        $valid = false;
        foreach ($this->assetTypes as $assetType) {
            if (substr($filepath, (strlen($assetType['ext']) * -1), strlen($assetType['ext'])) === $assetType['ext']) {
                $valid = true;
                break;
            }
        }
        if (!$valid) {
            $request->output_errors(__('Invalid file extension: ' . $this->file->safe_path($file), 'o10n'));
        }

        // save file
        try {
            $this->file->put_contents($file, $text);
        } catch (\Exception $err) {
            $request->output_errors($err->getMessage());
        }

        // return file contents
        $request->output_ok();
    }

    /**
     * Create file content
     */
    final public function ajax_create_file()
    {
        // process AJAX request
        $request = $this->AdminAjax->request();

        // filename
        $filepath = $request->data('file');

        // verify input
        if (!$filepath) {
            $request->output_errors('no file');
        }

        // theme relative or absolute path?
        $theme_file = (substr($filepath, 0, 1) === '/') ? false : true;
        
        // prevent relative path traversal
        // @todo investitate optimal protection
        // @link https://www.owasp.org/index.php/Path_Traversal
        if (strpos($filepath, '..') !== false) {
            $request->output_errors('double-dot relative paths are not allowed.');
        }

        if ($theme_file) {
            $file = $this->file->theme_directory() . $filepath;
        } else {
            $file = $this->file->un_trailingslashit(ABSPATH) . $filepath;
        }

        // verify if file exists
        if (file_exists($file)) {
            $request->output_errors(__('File exists.', 'o10n'));
        }

        // verify extension
        $valid = false;
        foreach ($this->assetTypes as $assetType) {
            if (substr($filepath, (strlen($assetType['ext']) * -1), strlen($assetType['ext'])) === $assetType['ext']) {
                $valid = true;
                break;
            }
        }
        if (!$valid) {
            $request->output_errors(__('Invalid file extension: ' . $this->file->safe_path($file), 'o10n'));
        }

        // save file
        try {
            $this->file->put_contents($file, ' ');
        } catch (\Exception $err) {
            $request->output_errors($err->getMessage());
        }

        // return file contents
        $data = array();
        $data[$this->AdminClient->index('filename')] = basename($file);
        $request->output_ok(false, $data);
    }

    /**
     * Search files
     */
    final public function ajax_search_files()
    {
        // process AJAX request
        $request = $this->AdminAjax->request();

        // query
        $query = $request->data('query');
        
        // asset type
        $assetType = $request->data('type');

        // verify input
        if (!$query) {
            $request->output_errors('no query');
        }

        // prevent relative path traversal
        // @todo investitate optimal protection
        // @link https://www.owasp.org/index.php/Path_Traversal
        if (strpos($query, '..') !== false) {
            $request->output_errors('double-dot relative paths are not allowed.');
        }

        if (!isset($this->assetTypes[$assetType])) {
            $request->output_errors('invalid asset type');
        }

        // theme relative or absolute path?
        $theme_query = (substr($query, 0, 1) === '/') ? false : true;

        if ($theme_query) {
            $path = $this->file->theme_directory() . $query;
        } else {
            $path = $this->file->un_trailingslashit(ABSPATH) . $query;
        }

        // scan directory of file
        if (!is_dir($path) && file_exists($path)) {
            $path = $this->file->trailingslashit(dirname($path));
        }

        // verify if directory
        if (is_dir($path)) {

            // scan directory
            $assets = $this->scandir($path, $assetType);

            $queryPath = str_replace(($theme_query) ? $this->file->theme_directory() : $this->file->un_trailingslashit(ABSPATH), '', $path);

            // return file contents
            $request->output_ok(false, array(
                $this->file->trailingslashit($queryPath),
                $assets
            ));
        } else {
            $request->output_ok();
        }
    }
}
