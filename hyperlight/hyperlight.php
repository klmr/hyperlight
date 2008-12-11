<?php

require 'preg_helper.php';

/**
 * @internal
 * For internal debugging purposes.
 */
function dump($obj, $descr = null) {
    if ($descr !== null)
        echo "<h3>$descr</h3>";
    ob_start();
    var_dump($obj);
    $dump = ob_get_clean();
    ?><pre><?php echo htmlspecialchars($dump); ?></pre><?php
}

class NoMatchingRuleException extends Exception {
    public function __construct($states, $position, $code) {
        $state = array_pop($states);
        parent::__construct(
            "State '$state' has no matching rule at position $position:\n" .
            $this->errorSurrounding($code, $position)
        );
    }

    private function errorSurrounding($code, $pos) {
        $size = 10;
        $begin = $pos < $size ? 0 : $pos - $size;
        $end = $pos + $size > strlen($code) ? strlen($code) : $pos + $size;
        $offs = $pos - $begin;
        return substr($code, $begin, $end - $begin) . "\n" . sprintf("%{$offs}s", '^');
    }
}

class Rule {
    /**
     * Common rules.
     */

    const C_IDENTIFIER = '/[a-z_][a-z0-9_]*/i';
    const C_COMMENT = '#//.*?\n|/\*.*?\*/#s';
    const C_MULTILINECOMMENT = '#/\*.*?\*/#s';
    const DOUBLEQUOTESTRING = '/"(?:\\\\"|.)*?"/s';
    const SINGLEQUOTESTRING = "/'(?:\\\\'|.)*?'/s";
    const C_DOUBLEQUOTESTRING = '/L?"(?:\\\\"|.)*?"/s';
    const C_SINGLEQUOTESTRING = "/L?'(?:\\\\'|.)*?'/s";
    const STRING = '/"(?:\\\\"|.)*?"|\'(?:\\\\\'|.)*?\'/s';
    const C_NUMBER = '/
        (?: # Integer followed by optional fractional part.
            (?:
                0(?:
                    x[0-9a-f]+
                    |
                    [0-7]*
                )
                |
                \d+
            )
            (?:\.\d*)?
            (?:e[+-]\d+)?
        )
        |
        (?: # Just the fractional part.
            (?:\.\d*)
            (?:e[+-]\d+)?
        )
        /ix';

    private $_start;
    private $_end;

    public function __construct($start, $end = null) {
        $this->_start = $start;
        $this->_end = $end;
    }

    public function start() {
        return $this->_start;
    }

    public function end() {
        return $this->_end;
    }
}

abstract class HyperLanguage {
    private $_states = array();
    private $_rules = array();
    private $_mappings = array();
    private $_info = array();
    private $_caseInsensitive = false;

    /**
     * Indices for information.
     */

    const NAME = 1;
    const VERSION = 2;
    const AUTHOR = 10;
    const WEBSITE = 5;
    const EMAIL = 6;

    public function compile() {
        return new HyperlightCompiledLanguage(
            $this->_info,
            $this->_states,
            $this->_rules,
            $this->_mappings,
            $this->_caseInsensitive
        );
    }

    protected function setCaseInsensitive($value) {
        $this->_caseInsensitive = $value;
    }

    protected function addStates(array $states) {
        $this->_states = self::mergeProperties($this->_states, $states);
    }

    protected function getState($key) {
        return $this->_states[$key];
    }

    protected function removeState($key) {
        unset($this->_states[$key]);
    }

    protected function addRules(array $rules) {
        $this->_rules = self::mergeProperties($this->_rules, $rules);
    }

    protected function getRule($key) {
        return $this->_rules[$key];
    }

    protected function removeRule($key) {
        unset($this->_rules[$key]);
    }

    protected function addMappings(array $mappings) {
        // TODO Implement nested mappings.
        $this->_mappings = array_merge($this->_mappings, $mappings);
    }

    protected function getMapping($key) {
        return $this->_mappings[$key];
    }

    protected function removeMapping($key) {
        unset($this->_mappings[$key]);
    }

    protected function setInfo(array $info) {
        $this->_info = $info;
    }

    protected function addNestedLanguage(HyperLanguage $language, $hoistBackRules) {
        $prefix = get_class($language);
        if (!is_array($hoistBackRules))
            $hoistBackRules = array($hoistBackRules);

        $states = array();  // Step 1: states

        foreach ($language->_states as $stateName => $state) {
            $prefixedRules = array();

            if (strstr($stateName, ' ')) {
                $parts = explode(' ', $stateName);
                $prefixed = array();
                foreach ($parts as $part)
                    $prefixed[] = "$prefix$part";
                $stateName = implode(' ', $prefixed);
            }
            else
                $stateName = "$prefix$stateName";

            foreach ($state as $key => $rule) {
                if (is_string($key) and is_array($rule)) {
                    $nestedRules = array();
                    foreach ($rule as $nestedRule)
                        $nestedRules[] = ($nestedRule === '') ? '' :
                                         "$prefix$nestedRule";

                    $prefixedRules["$prefix$key"] = $nestedRules;
                }
                else
                    $prefixedRules[] = "$prefix$rule";
            }

            if ($stateName === 'init')
                $prefixedRules = array_merge($prefixedRules, $hoistBackRules);

            $states[$stateName] = $prefixedRules;
        }

        $rules = array();   // Step 2: rules
        // Mappings need to set up already!
        $mappings = array();

        foreach ($language->_rules as $ruleName => $rule) {
            if (is_array($rule)) {
                $nestedRules = array();
                foreach ($rule as $nestedName => $nestedRule) {
                    if (is_string($nestedName)) {
                        $nestedRules["$prefix$nestedName"] = $nestedRule;
                        $mappings["$prefix$nestedName"] = $nestedName;
                    }
                    else
                        $nestedRules[] = $nestedRule;
                }
                $rules["$prefix$ruleName"] = $nestedRules;
            }
            else {
                $rules["$prefix$ruleName"] = $rule;
                $mappings["$prefix$ruleName"] = $ruleName;
            }
        }

        // Step 3: mappings.

        foreach ($language->_mappings as $ruleName => $cssClass) {
            if (strstr($ruleName, ' ')) {
                $parts = explode(' ', $ruleName);
                $prefixed = array();
                foreach ($parts as $part)
                    $prefixed[] = "$prefix$part";
                $mappings[implode(' ', $prefixed)] = $cssClass;
            }
            else
                $mappings["$prefix$ruleName"] = $cssClass;
        }

        $this->addStates($states);
        $this->addRules($rules);
        $this->addMappings($mappings);

        return $prefix . 'init';
    }

    private static function mergeProperties(array $old, array $new) {
        foreach ($new as $key => $value) {
            if (is_string($key)) {
                if (isset($old[$key]) and is_array($old[$key]))
                    $old[$key] = array_merge($old[$key], $new);
                else
                    $old[$key] = $value;
            }
            else
                $old[] = $value;
        }

        return $old;
    }
}

class HyperlightCompiledLanguage {
    private $_info;
    private $_states;
    private $_rules;
    private $_mappings;
    private $_caseInsensitive;

    public function __construct($info, $states, $rules, $mappings, $caseInsensitive) {
        $this->_info = $info;
        $this->_caseInsensitive = $caseInsensitive;
        $this->_states = $this->compileStates($states);
        $this->_rules = $this->compileRules($rules);
        $this->_mappings = $mappings;
    }

    public function name() {
        return $this->_info[HyperLanguage::NAME];
    }

    public function autorName() {
        if (!array_key_exists(HyperLanguage::AUTHOR, $this->_info))
            return null;
        $author = $this->_info[HyperLanguage::AUTHOR];
        if (is_string($author))
            return $author;
        if (!array_key_exists(HyperLanguage::NAME, $author))
            return null;
        return $author[HyperLanguage::NAME];
    }

    public function authorWebsite() {
        if (!array_key_exists(HyperLanguage::AUTHOR, $this->_info) or
            !is_array($this->_info[HyperLanguage::AUTHOR]) or
            !array_key_exists(HyperLanguage::WEBSITE, $this->_info[HyperLanguage::AUTHOR]))
            return null;
        return $this->_info[HyperLanguage::AUTHOR][HyperLanguage::WEBSITE];
    }

    public function authorEmail() {
        if (!array_key_exists(HyperLanguage::AUTHOR, $this->_info) or
            !is_array($this->_info[HyperLanguage::AUTHOR]) or
            !array_key_exists(HyperLanguage::EMAIL, $this->_info[HyperLanguage::AUTHOR]))
            return null;
        return $this->_info[HyperLanguage::AUTHOR][HyperLanguage::EMAIL];
    }

    public function authorContact() {
        $email = $this->authorEmail();
        return $email !== null ? $email : $this->authorWebsite();
    }

    public function state($stateName) {
        return $this->_states[$stateName];
    }

    public function rule($ruleName) {
        return $this->_rules[$ruleName];
    }

    public function className($state) {
        if (array_key_exists($state, $this->_mappings))
            return $this->_mappings[$state];
        else if (strstr($state, ' ') === false)
            // No mapping for state.
            return $state;
        else {
            // Try mapping parts of nested state name.
            $parts = explode(' ', $state);
            $ret = array();

            foreach ($parts as $part) {
                if (array_key_exists($part, $this->_mappings))
                    $ret[] = $this->_mappings[$part];
                else
                    $ret[] = $part;
            }

            return implode(' ', $ret);
        }
    }

    private function compileStates($states) {
        $ret = array();

        foreach ($states as $name => $state) {
            $newstate = array();

            if (!is_array($state))
                $state = array($state);

            foreach ($state as $key => $elem) {
                if ($elem === null)
                    continue;
                if (is_string($key)) {
                    //$newstate[] = $key;

                    if (!is_array($elem))
                        $elem = array($elem);

                    foreach ($elem as $el2) {
                        if ($el2 === '')
                            $newstate[] = $key;
                        else
                            $newstate[] = "$key $el2";
                    }
                }
                else
                    $newstate[] = $elem;
            }

            $ret[$name] = $newstate;
        }

        return $ret;
    }

    private function compileRules($rules) {
        $tmp = array();

        // Preprocess keyword list and flatten nested lists:

        // End of regular expression matching keywords.
        $end = $this->_caseInsensitive ? ')\b/i' : ')\b/';

        foreach ($rules as $name => $rule) {
            if (is_array($rule)) {
                if (self::isAssocArray($rule)) {
                    // Array is a nested list of rules.
                    foreach ($rule as $key => $value) {
                        if (is_array($value))
                            // Array represents a list of keywords.
                            $value = '/\b(?:' . implode('|', $value) . $end;

                        if (!is_string($key) or strlen($key) === 0)
                            $tmp[$name] = $value;
                        else
                            $tmp["$name $key"] = $value;
                    }
                }
                else {
                    // Array represents a list of keywords.
                    $rule = '/\b(?:' . implode('|', $rule) . $end;
                    $tmp[$name] = $rule;
                }
            }
            else {
                $tmp[$name] = $rule;
            } // if (is_array($rule))
        } // foreach

        $ret = array();

        foreach ($this->_states as $name => $state) {
            $regex_rules = array();
            $regex_names = array();
            $nesting_rules = array();

            foreach ($state as $rule_name) {
                $rule = $tmp[$rule_name];
                if ($rule instanceof Rule)
                    $nesting_rules[$rule_name] = $rule;
                else {
                    $regex_rules[] = $rule;
                    $regex_names[] = $rule_name;
                }
            }

            $ret[$name] = array_merge(
                array(preg_merge('|', $regex_rules, $regex_names)),
                $nesting_rules
            );
        }

        return $ret;
    }

    private static function isAssocArray(array $array) {
        foreach($array as $key => $_)
            if (is_string($key))
                return true;
        return false;
    }
}

class Hyperlight {
    private $_code;
    private $_lang;
    private $_result;
    private $_omitSpans;

    private static $_languageCache = array();

    public function __construct($code, $lang) {
        // Normalize line breaks.
        $this->_code = preg_replace('/\r\n?/', "\n", $code);
        $this->_lang = $this->languageDefinition(strtolower($lang));
    }

    public function language() {
        return $this->_lang;
    }

    public function result() {
        if ($this->_result === null)
            $this->renderCode();

        return $this->_result;
    }

    public function theResult() {
        echo $this->result();
    }

    private function languageDefinition($lang) {
        if (!isset(self::$_languageCache[$lang])) {
            require_once "languages/$lang.php";
            $klass = ucfirst("{$lang}Language");
            $language = new $klass();
            self::$_languageCache[$lang] = $language->compile();
        }
        return self::$_languageCache[$lang];
    }

    private function renderCode() {
        $this->_omitSpans = array();
        $code = $this->_code;
        $pos = 0;
        $len = strlen($code);
        $this->_result = '';
        $state = 'init';
        $states = array($state);    // Stack of active states.

        $prev_pos = -1;             // Emergency break to catch faulty rules.

        while ($pos < $len) {
            // The token next to the current position, after the inner loop completes.
            $closest_hit = array('', $len);
            // The rule that found this token.
            $closest_rule = null;
            $rules = $this->_lang->rule($state);

            foreach ($rules as $name => $rule) {
                if ($rule instanceof Rule) {
                    $this->matchCloser($rule->start(), $name, $pos, $closest_hit, $closest_rule);
                }
                else {
                    if (preg_match($rule, $code, $matches, PREG_OFFSET_CAPTURE, $pos) == 1) {
                        // Search which of the sub-patterns matched.

                        foreach ($matches as $group => $match) {
                            if (!is_string($group))
                                continue;
                            if ($match[1] !== -1) {
                                $closest_hit = $match;
                                $closest_rule = str_replace('_', ' ', $group);
                                break;
                            }
                        }
                    }
                }
            }

            // If we're currently inside a rule …

            if (count($states) > 1) {
                $n = count($states) - 1;
                do {
                    $rule = $this->_lang->rule($states[$n - 1]);
                    $rule = $rule[$states[$n]];
                    --$n;
                    if ($n < 0)
                        throw new NoMatchingRuleException($states, $pos, $this->code);
                } while ($rule->end() === null);

                $this->matchCloser($rule->end(), $n + 1, $pos, $closest_hit, $closest_rule);
            }

            // We take the closest hit:

            if ($closest_hit[1] > $pos)
                $this->emit(substr($code, $pos, $closest_hit[1] - $pos));

            $prev_pos = $pos;
            $pos = $closest_hit[1] + strlen($closest_hit[0]);

            if ($prev_pos === $pos and is_string($closest_rule))
                throw new NoMatchingRuleException($states, $pos, $code);

            if ($closest_hit[1] === $len)
                break;
            else if (!is_string($closest_rule)) {
                // Pop state.
                if (count($states) <= $closest_rule)
                    throw new NoMatchingRuleException($states, $pos, $code);

                while (count($states) > $closest_rule + 1) {
                    array_pop($states);
                    $this->emitPop();
                }
                array_pop($states);
                $state = $states[count($states) - 1];
                $this->emitPop($closest_hit[0]);
            }
            else if (array_key_exists($closest_rule, $this->_lang->rule($state))) {
                // Push state.
                array_push($states, $closest_rule);
                $state = $closest_rule;
                $this->emitPartial($closest_hit[0], $closest_rule);
            }
            else {
                $this->emit($closest_hit[0], $closest_rule);
            }
        }
    }

    private function matchCloser($expr, $next, $pos, &$closest_hit, &$closest_rule) {
        $matches = array();
        if (preg_match($expr, $this->_code, $matches, PREG_OFFSET_CAPTURE, $pos) == 1) {
            if (
                (
                    // Two hits at same position -- compare length
                    $matches[0][1] == $closest_hit[1] and
                    strlen($matches[0][0]) > strlen($closest_hit[0])
                ) or
                $matches[0][1] < $closest_hit[1]
            ) {
                $closest_hit = $matches[0];
                $closest_rule = $next;
            }
        }
    }

    private function emit($token, $class = null) {
        #$token = self::htmlentities($token);
        $token = htmlspecialchars($token, ENT_NOQUOTES);
        if ($class === null)
            $this->write($token);
        else {
            $class = $this->_lang->className($class);
            $this->write("<span class=\"$class\">$token</span>");
        }
    }

    private function emitPartial($token, $class) {
        #$token = self::htmlentities($token);
        $token = htmlspecialchars($token, ENT_NOQUOTES);
        $class = $this->_lang->className($class);
        if ($class === '') {
            $this->write($token);
            array_push($this->_omitSpans, true);
        }
        else {
            $this->write("<span class=\"$class\">$token");
            array_push($this->_omitSpans, false);
        }
    }

    private function emitPop($token = '') {
        #$token = self::htmlentities($token);
        $token = htmlspecialchars($token, ENT_NOQUOTES);
        if (array_pop($this->_omitSpans))
            $this->write($token);
        else
            $this->write("$token</span>");
    }

    private function write($text) {
        $this->_result .= $text;
    }

    /*
     * DAMN! What did I need them for? Something to do with encoding …
     * but why not use the `$charset` argument on `htmlspecialchars`?
    private static function htmlentitiesCallback($match) {
        switch ($match[0]) {
            case '<': return '&lt;';
            case '>': return '&gt;';
            case '&': return '&amp;';
        }
    }

    private static function htmlentities($text) {
        return htmlspecialchars($text, ENT_NOQUOTES);
        return preg_replace_callback(
            '/[<>&]/', array('Hyperlight', 'htmlentitiesCallback'), $text
        );
    }
    */
}

/**
 * <var>echo</var>s a highlighted code.
 *
 * @param string $code The code.
 * @param string $lang The language of the code.
 * @param string $tag The surrounding tag to use. Optional.
 * @param array $attributes Attributes to decorate {@link $tag} with.
 *          If no tag is given, this argument can be passed in its place. This
 *          behaviour will be assumed if the third argument is an array.
 *          Attributes must be given as a hash of key value pairs.
 */
function hyperlight($code, $lang, $tag = 'pre', array $attributes = array()) {
    if (is_array($tag) and !empty($attributes))
        die("Can't pass array arguments for \$tag *and* \$attributes to `hyperlight`!");
    if ($tag === '')
        $tag = 'pre';
    $lang = strtolower($lang);
    $class = "source-code $lang";

    $attr = array();
    foreach ($attributes as $key => $value)
        if ($key == 'class')
            $class .= ' ' . htmlspecialchars($value);
        else
            $attr[] = htmlspecialchars($key) . '="' .
                      htmlspecialchars($value) . '"';

    $attr = empty($attr) ? '' : ' ' . implode(' ', $attr);

    $hl = new Hyperlight($code, $lang);
    echo "<$tag class=\"source-code $lang\"$attr>";
    $hl->theResult();
    echo "</$tag>";
}

?>
