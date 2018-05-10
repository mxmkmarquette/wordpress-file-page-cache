<?php
namespace O10n;

/**
 * Admin Form Input Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers/admin
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class AdminForminput extends Controller implements Controller_Interface
{
    private $input; // posted settings
    private $verified_input = array(); // verified posted settings

    private $validator; // JSON validator

    private $errors = array();

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
            'json',
            'AdminOptions'
        ));
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
    }

    /**
     * Load post data
     */
    final public function load_post()
    {
        if (!isset($_POST['o10n'])) {
            return;
        }
        
        // @link https://codex.wordpress.org/Function_Reference/stripslashes_deep
        $this->input = stripslashes_deep($_POST['o10n']);
        if (!is_array($this->input)) {
            $this->input = array();
        }
    }

    /**
     * Create JSON validation object by dot notation string
     */
    final private function json_validation_object(&$root, $path, $value)
    {

        // default settings required by master.json schema
        $root = (object)array(
            'weight' => 1,
            'title' => 'Validation'
        );

        $keys = explode('.', $path);
        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($root->{$key})) {
                $root->{$key} = (object)array();
            }
            $root = &$root->{$key};
        }

        $key = reset($keys);
        $root->{$key} = $value;
    }

    /**
     * JSON schema validation
     *
     * @param  object $json JSON object to validate.
     * @return bool   JSON valid true/false.
     */
    final private function validate($json)
    {
        
        // load validator
        if (!$this->validator) {

            // autoloader
            require_once O10N_CORE_PATH . 'lib/vendor/autoload.php';
            $this->validator = new \JsonSchema\Validator();
        }

        // validate configuration
        $this->validator->validate(
            $json,
            (object)['$ref' => 'file://' . O10N_CORE_PATH . 'schemas/master.json'],
            0x00000002 // fuzzy type assoc array vs object
            );
        
        // invalid, handle error
        if (!$this->validator->isValid()) {

            // error message
            $this->errors = array_merge($this->errors, $this->validator->getErrors());

            return false;
        }

        return true;
    }

    /**
     * Verify input
     */
    final public function verify()
    {

        // apply input filters
        apply_filters('o10n_save_settings_verify_input', $this);
        
        return $this->verify_json($this->verified_input);
    }

    /**
     * Verify JSON against schema
     */
    final public function verify_json($input)
    {

        // Convert input to JSON
        require_once O10N_CORE_PATH . 'lib/Dot.php';
        $dot = new Dot;
        $dot->set($input);
        $json = $dot->toJSON();

        // parse JSON
        try {
            $json = $this->json->parse($json, true);
        } catch (Exception $e) {
            return false;
        }

        /// mimic schema for validation
        $json['title'] = 'Validation';
        $json['weight'] = 1;

        // validate JSON configuration against JSON schemacx
        if (!$this->validate($json)) {
            return false;
        }

        return true;
    }

    /**
     * Verify input by type
     */
    final public function type_verify($types)
    {
        foreach ($types as $path => $type) {
            $typedata = null;
            if (is_array($type)) {
                $typedata = $type;
                $type = $type[0];
            }
            switch ($type) {
                case "bool":
                    $this->verified_input[$path] = (!isset($this->input[$path]) || (!is_numeric($this->input[$path]) && !is_bool($this->input[$path])) || !$this->input[$path]) ? false : true;
                break;
                case "int":
                    if (isset($this->input[$path]) && is_numeric($this->input[$path])) {
                        $this->verified_input[$path] = floatval($this->input[$path]);
                    }
                break;
                case "int-empty":
                    if (isset($this->input[$path]) && is_numeric($this->input[$path])) {
                        $this->verified_input[$path] = floatval($this->input[$path]);
                    } else {
                        $this->verified_input[$path] = '';
                    }
                break;
                case "string":
                    $this->verified_input[$path] = (isset($this->input[$path]) && is_string($this->input[$path])) ? $this->input[$path] : '';
                break;
                case "newline_array":
                    $this->verified_input[$path] = (isset($this->input[$path])) ? $this->newline_array($this->input[$path]) : array();
                break;
                case "json":
                case "json-array":
                    if (isset($this->input[$path]) && trim($this->input[$path]) !== '') {
                        try {
                            $this->verified_input[$path] = $this->json->parse($this->input[$path], true);
                        } catch (\Exception $e) {
                            $this->error($path, $e->getMessage() . ' <pre>'.htmlentities($this->input[$path], ENT_COMPAT, 'utf-8').'</pre>');
                            $this->verified_input[$path] = (($type === 'json') ? json_decode('{}') : array());
                        }
                    } else {
                        $this->verified_input[$path] = (($type === 'json') ? json_decode('{}') : array());
                    }
                break;
                case "enum":
                    if (!is_array($typedata) || !isset($typedata[1])) {
                        throw new Exception('No enum options', 'settings');
                    }
                    if (isset($this->input[$path]) && !in_array($this->input[$path], $typedata[1])) {
                        throw new Exception('Invalid enum option', 'settings');
                    }
                break;
                default:
                    throw new Exception('Invalid input type "' . esc_html($type) . '"', 'settings');
                break;
            }
        }
    }

    /**
     * Return input
     */
    final public function get($path = false, $type = false, $default = false)
    {
        if (!$path) {
            return $this->input;
        }

        $input = (isset($this->verified_input[$path])) ? $this->verified_input[$path] : ((isset($this->input[$path])) ? $this->input[$path] : $default);
        if ($type) {
            switch ($type) {
                case "json":
                case "json-array":
                    if (is_string($input)) {
                        $input = ($input) ? $this->json->parse($input, (($type === 'json') ? false : true)) : (($type === 'json') ? json_decode('{}') : array());
                    }
                break;
                case "bool":
                    return ($input) ? true : false;
                break;
                default:
                    throw new Exception('Invalid input type to force', 'settings');
                break;
            }
        }

        return $input;
    }

    /**
     * Set form input
     */
    final public function set($path, $value)
    {
        $this->verified_input[$path] = $value;
    }

    /**
     * Return boolean value
     */
    final public function bool($path = false)
    {
        return $this->get($path, 'bool');
    }

    /**
     * Return newline array from string
     */
    public function newline_array($string, $data = array())
    {
        if (!is_array($data)) {
            $data = array();
        }

        $lines = array_filter(array_map('trim', explode("\n", trim($string))));
        if (!empty($lines)) {
            foreach ($lines as $line) {
                if ($line === '') {
                    continue;
                }
                $data[] = $line;
            }
            $data = array_unique($data);
        }

        return $data;
    }

    /**
     * Return input errors
     */
    public function error($path, $text)
    {
        if (is_a($text, 'Exception') || is_a($text, '\Exception')) {
            $text = $text->getMessage();
        }
        if (!isset($this->errors[$path])) {
            $this->errors[$path] = '';
        } else {
            $this->errors[$path] .= '<br />';
        }
        $this->errors[$path] .= $text;
    }

    /**
     * Return input errors
     */
    public function errors()
    {
        return ($this->errors && !empty($this->errors)) ? $this->errors : false;
    }

    /**
     * Save input
     */
    public function save()
    {
        if (!empty($this->errors)) {
            foreach ($this->errors as $path => $value) {
                if (isset($this->verified_input[$path])) {
                    unset($this->verified_input[$path]);
                }
            }
        }

        // store options
        $this->AdminOptions->save($this->verified_input);
    }
}
