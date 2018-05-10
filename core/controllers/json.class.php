<?php
namespace O10n;

/**
 * JSON Controller
 *
 * @package    optimization
 * @subpackage optimization/controllers
 * @author     Optimization.Team <info@optimization.team>
 */
if (!defined('ABSPATH')) {
    exit;
}

class Json extends Controller implements Controller_Interface
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
        return parent::construct($Core);
    }

    /**
     * Setup controller
     */
    protected function setup()
    {
    }

    /**
     * Parse JSON
     *
     * @param string $json JSON string to parse.
     * @param   object               Parsed object.
     */
    final public function parse($json, $assoc = false)
    {
        // verify
        if (!is_string($json)) {
            throw new \Exception('JSON not string');
        }

        // parse JSON string
        $json = @json_decode($json, $assoc);

        // invalid file contents
        if ((!$assoc && !is_object($json)) || ($assoc && !is_array($json))) {

            // get JSON parser error (PHP5.5+)
            if (function_exists('json_last_error_msg')) {
                $error = json_last_error_msg();
            } elseif (function_exists('json_last_error')) {

                // detect JSON parser error
                switch (json_last_error()) {
                    case JSON_ERROR_NONE:
                        $error = 'No error';
                    break;
                    case JSON_ERROR_DEPTH:
                        $error = 'Maximum stack depth exceeded';
                    break;
                    case JSON_ERROR_STATE_MISMATCH:
                        $error = 'State mismatch (invalid or malformed JSON)';
                    break;
                    case JSON_ERROR_CTRL_CHAR:
                        $error = 'Control character error, possibly incorrectly encoded';
                    break;
                    case JSON_ERROR_SYNTAX:
                        $error = 'Syntax error, malformed JSON';
                    break;
                    case JSON_ERROR_UTF8:
                        $error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                    break;
                    default:
                        $error = 'Unknown error';
                    break;
                }
            } else {
                $error = 'Unknown error';
            }

            throw new \Exception($error);
        }

        return $json; // return parsed JSON
    }

    /**
     * Beautify JSON
     *
     * @param  string $json JSON string to encode.
     * @return string Beautified JSON.
     */
    final public function beautify($json)
    {

        // PHP 5.4+
        if (defined('JSON_PRETTY_PRINT')) {
            if (is_string($json)) {
                $json = json_decode($json);
            }

            return json_encode($json, JSON_PRETTY_PRINT);
        }

        /**
         * Polyfill for JSON_PRETTY_PRINT
         */
        if (!is_string($json)) {
            $json = json_encode($json);
        }
        
        $result = '';
        $pos = 0;
        $strLen = strlen($json);
        $indentStr = '  ';
        $newLine = "\n";
        $prevChar = '';
        $outOfQuotes = true;

        for ($i = 0; $i <= $strLen; $i++) {

            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;

                // If this character is the end of an element,
            // output a new line and indent the next line.
            } elseif (($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos --;
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos ++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        return $result;
    }
}
