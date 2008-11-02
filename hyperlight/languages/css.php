<?php

class CssLanguage extends HyperLanguage {
    public function __construct() {
        $this->setInfo(array(
            parent::NAME => 'CSS',
            parent::VERSION => '0.1',
            parent::AUTHOR => array(
                parent::NAME => 'Konrad Rudolph',
                parent::WEBSITE => 'madrat.net',
                parent::EMAIL => 'konrad_rudolph@madrat.net'
            )
        ));

        $nmstart = '[a-z0-9]';
        $nmchar = '[a-z0-9-]';
        $hex = '[0-9a-f]';
        list($string, $strmod) = preg_strip(Rule::STRING);
        $strmod = implode('', $strmod);

        $this->addStates(array(
            'init' => array('comment', 'meta', 'id', 'class', 'block', 'string'),
            'block' => array('comment', 'identifier', 'string', 'color', 'number', 'uri'),
        ));

        $this->addRules(array(
            'comment' => Rule::C_MULTILINECOMMENT,
            'meta' => "/@$nmstart$nmchar*/i",
            'id' => "/#$nmstart$nmchar*/i",
            'class' => "/\.$nmstart$nmchar*/",
            'block' => new Rule('/\{/', '/\}/'),
            'identifier' => "/$nmstart$nmchar*/i",
            'string' => "/$string/$strmod",
            'color' => "/#$hex{3}(?:$hex{3})?/i",
            'number' => '/[+-]?(?:\d+|\d*\.\d+)(%|em|ex|px|pt|in|cm|mm|pc)?/',
            'uri' => "/url\(\s*(?:$string|[^\)]*)\s*\)/$strmod",
        ));

        $this->addMappings(array(
            'block' => '',
            'color' => 'string',
        ));
    }
}

?>
