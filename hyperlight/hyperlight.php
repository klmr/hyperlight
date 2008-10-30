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
    const DOUBLEQUOTESTRING = '/L?"(?:\\\\"|.)*?"/';
    const SINGLEQUOTESTRING = "/L?'(?:\\\\'|.)*?'/";
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
    private $_states;
    private $_rules;
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

    public function name() {
        return $this->_info[self::NAME];
    }

    public function autorName() {
        if (!array_key_exists(self::AUTHOR, $this->_info))
            return null;
        $author = $this->_info[self::AUTHOR];
        if (is_string($author))
            return $author;
        if (!array_key_exists(self::NAME, $author))
            return null;
        return $author[self::NAME];
    }

    public function authorWebsite() {
        if (!array_key_exists(self::AUTHOR, $this->_info) or
            !is_array($this->_info[self::AUTHOR]) or
            !array_key_exists(self::WEBSITE, $this->_info[self::AUTHOR]))
            return null;
        return $this->_info[self::AUTHOR][self::WEBSITE];
    }

    public function authorEmail() {
        if (!array_key_exists(self::AUTHOR, $this->_info) or
            !is_array($this->_info[self::AUTHOR]) or
            !array_key_exists(self::EMAIL, $this->_info[self::AUTHOR]))
            return null;
        return $this->_info[self::AUTHOR][self::EMAIL];
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

    protected function setCaseInsensitive($value) {
        $this->_caseInsensitive = $value;
    }

    protected function setStates(array $states) {
        $this->_states = array();

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

            $this->_states[$name] = $newstate;
        }
    }

    protected function setRules(array $rules) {
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

        $this->_rules = array();

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

            $this->_rules[$name] = array_merge(
                array(preg_merge('|', $regex_rules, $regex_names)),
                $nesting_rules
            );
        }
    }

    protected function setMappings(array $mappings) {
        // TODO Implement nested mappings.
        $this->_mappings = $mappings;
    }

    protected function setInfo(array $info) {
        $this->_info = $info;
    }

    private static function isAssocArray(array $array) {
        foreach($array as $key => $_)
            if (is_string($key))
                return true;
        return false;
    }
}

class HyperLight {
    private $_code;
    private $_lang;
    private $_result;

    public function __construct($code, $lang) {
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
        require_once "languages/$lang.php";
        $klass = ucfirst("{$lang}Language");
        return new $klass();
    }

    private function renderCode() {
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

            /*
            $transitions = $this->_lang->state($state);

            foreach ($transitions as $next) {
                $rule = $this->_lang->rule($next);

                if ($rule instanceof Rule)
                    $rule = $rule->start();

                $this->matchCloser($rule, $next, $pos, $closest_hit, $closest_rule);
            }*/

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
            //else if ($this->_lang->rule($closest_rule) instanceof Rule) {
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
            /*
            // Zero-length hit: ignore.
            
            if (strlen($matches[0][0]) == 0)
                return;*/

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
        $token = self::htmlentities($token);
        if ($class === null)
            $this->write($token);
        else {
            $class = $this->_lang->className($class);
            $this->write("<span class=\"$class\">$token</span>");
        }
    }

    private function emitPartial($token, $class) {
        $token = self::htmlentities($token);
        $class = $this->_lang->className($class);
        $this->write("<span class=\"$class\">$token");
    }

    private function emitPop($token = '') {
        $token = self::htmlentities($token);
        $this->write("$token</span>");
    }

    private function write($text) {
        $this->_result .= $text;
        //echo $text;
    }

    private static function htmlentitiesCallback($match) {
        switch ($match[0]) {
            case '<': return '&lt;';
            case '>': return '&gt;';
            case '&': return '&amp;';
        }
    }

    private static function htmlentities($text) {
        return preg_replace_callback(
            '/[<>&]/', array('HyperLight', 'htmlentitiesCallback'), $text
        );
    }
}

function hyperlight_test($file, $lang = null) {
    if ($lang === null)
        $lang = $file;
    $fname = 'tests/' . strtolower($file);
    $code = file_get_contents($fname);
    $hl = new HyperLight($code, $lang);
    $pretty_name = $hl->language()->name();
    $title = $file === $lang ?
        "<h2>Test for language {$pretty_name}</h2>" :
        "<h2>Test with file “{$file}” for language {$pretty_name}</h2>";
    echo $title;
    ?><pre><?php $hl->theResult(); ?></pre><?php
}

?><!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title>HyperLight Syntax Highlighter</title>
    <style type="text/css">
.keyword { font-weight: bold; color: #008; }
.keyword.type { background: #EEF; }
.keyword.literal { background: #DFD; }
.keyword.operator { background: #EEE; }
.preprocessor { background: #DDD; }
.keyword.preprocessor { color: #448; }
.number { color: #880; }
.comment { background: #EFE; color: #080; }
.comment .doc { color: #888; font-weight: bold; }
.string { color: #800; }
.char { background: #FEE; color: #800; }
.date { color: #088; }
.identifier { color: #888; }

.tag { background: #DDD; }
.tag .name { font-weight: bold; }
.tag .preprocessor { color: #088; }
.tag .meta { color: #880; }
.tag .attribute { background: #AAA; }

.entity { color: #800; }
.cdata { font-style: italic; }
    </style>
</head>
<body><h1>HyperLight tests</h1><?php

hyperlight_test('pizzachili_api.h', 'cpp');
hyperlight_test('VB');
hyperlight_test('XML');

?>
</body></html>
