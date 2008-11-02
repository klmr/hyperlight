<?php

class CppLanguage extends HyperLanguage {
    public function __construct() {
        $this->setInfo(array(
            parent::NAME => 'C++',
            parent::VERSION => '0.2',
            parent::AUTHOR => array(
                parent::NAME => 'Konrad Rudolph',
                parent::WEBSITE => 'madrat.net',
                parent::EMAIL => 'konrad_rudolph@madrat.net'
            )
        ));

        $this->addStates(array(
            'init' => array(
                'preprocessor',
                'string',
                'char',
                'number',
                'comment',
                'keyword' => array('', 'type', 'literal', 'operator'),
                'identifier'
            ),
        ));

        $this->addRules(array(
            'preprocessor' => '/#\w+(?:\\\\\n|[^\\\\])*?\n/s',
            'string' => Rule::C_DOUBLEQUOTESTRING,
            'char' => Rule::C_SINGLEQUOTESTRING,
            'number' => Rule::C_NUMBER,
            'comment' => Rule::C_COMMENT,
            'keyword' => array(
                array(
                    'asm', 'auto', 'break', 'case', 'catch', 'class', 'const',
                    'const_cast', 'continue', 'default', 'do', 'dynamic_cast',
                    'else', 'enum', 'explicit', 'export', 'extern', 'for',
                    'firend', 'goto', 'if', 'inline', 'mutable', 'namespace',
                    'operator', 'private', 'protected', 'public', 'register',
                    'reinterpret_cast', 'return', 'sizeof', 'static',
                    'static_cast', 'struct', 'switch', 'template', 'throw',
                    'try', 'typedef', 'typename', 'union', 'using', 'virtual',
                    'volatile', 'while'
                ),
                'type' => array(
                    'bool', 'char', 'double', 'float', 'int', 'long', 'short',
                    'signed', 'unsigned', 'void', 'wchar_t'
                ),
                'literal' => array(
                    'false', 'this', 'true'
                ),
                'operator' => array(
                    'and', 'and_eq', 'bitand', 'bitor', 'compl', 'delete',
                    'new', 'not', 'not_eq', 'or', 'or_eq', 'typeid', 'xor',
                    'xor_eq'
                ),
            ),
            'identifier' => Rule::C_IDENTIFIER,
        ));
    }
}

?>
