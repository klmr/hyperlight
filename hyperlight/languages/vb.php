<?php

class VbLanguage extends HyperLanguage {
    public function __construct() {
        $this->setInfo(array(
            parent::NAME => 'VB',
            parent::VERSION => '1.1',
            parent::AUTHOR => array(
                parent::NAME => 'Konrad Rudolph',
                parent::WEBSITE => 'madrat.net',
                parent::EMAIL => 'konrad_rudolph@madrat.net'
            )
        ));

        $this->setCaseInsensitive(true);

        $this->addStates(array(
            'init' => array(
                'string',
                'number',
                'comment' => array('', 'doc'),
                'keyword' => array('', 'type', 'literal', 'operator', 'preprocessor'),
                'date',
                'identifier',
            ),
            'string' => 'escaped',
            'comment doc' => 'doc',
        ));

        $this->addRules(array(
            'string' => new Rule('/"/', '/"c?/i'),
            'number' => '/(?:&[HO])?\d+(?:\.\d*)?(?:e[+-]?\d+)?U?[SILDFR%@!#&]?|\.\d+[FR!#]?/',
            'escaped' => '/""/',
            'keyword' => array(
                array(
                    'addhandler', 'addressof', 'alias', 'as', 'byref', 'byval',
                    'call', 'case', 'catch', 'cbool', 'cbyte', 'cchar',
                    'cdate', 'cdec', 'cdbl', 'cint', 'class', 'clng', 'cobj',
                    'const', 'continue', 'csbyte', 'cshort', 'csng', 'cstr',
                    'ctype', 'cuint', 'culng', 'cushort', 'declare', 'default',
                    'delegate', 'dim', 'directcast', 'do', 'each', 'else',
                    'elseif', 'end', 'endif', 'enum', 'erase', 'error',
                    'event', 'exit', 'finally', 'for', 'friend', 'function',
                    'get', 'gettype', 'getxmlnamespace', 'global', 'gosub',
                    'goto', 'handles', 'if', 'implements', 'imports', 'in',
                    'inherits', 'interface', 'let', 'lib', 'loop', 'module',
                    'mustinherit', 'mustoverride', 'namespace', 'narrowing',
                    'next', 'notinheritable', 'notoverridable', 'of', 'on',
                    'operator', 'option', 'optional', 'overloads',
                    'overridable', 'overrides', 'paramarray', 'partial',
                    'private', 'property', 'protected', 'public', 'raiseevent',
                    'readonly', 'redim', 'removehandler', 'resume', 'return',
                    'select', 'set', 'shadows', 'shared', 'static', 'step',
                    'stop', 'structure', 'sub', 'synclock', 'then', 'throw',
                    'to', 'try', 'trycast', 'wend', 'using', 'when', 'while',
                    'widening', 'with', 'withevents', 'writeonly'
                ),
                'type' => array(
                    'boolean', 'byte', 'char', 'date', 'decimal', 'double',
                    'long', 'integer', 'object', 'sbyte', 'short', 'single',
                    'string', 'variant', 'uinteger', 'ulong', 'ushort'
                ),
                'literal' => array(
                    'false', 'me', 'mybase', 'myclass', 'nothing', 'true'
                ),
                'operator' => array(
                    'and', 'andalso', 'is', 'isnot', 'like', 'mod', 'new',
                    'not', 'or', 'orelse', 'typeof', 'xor'
                ),
                'preprocessor' => '/#(?:const|else|elseif|end if|end region|if|region)/i'
            ),
            'comment' => array(
                "/(?:'{1,2}[^']|rem\s).*/i",
                'doc' => new Rule("/'''/", '/$/m')
            ),
            'date' => '/#.+?#/',
            'identifier' => '/[a-z_][a-z_0-9]*|\[.+?\]/i',
            'doc' => '/<(?:".*?"|\'.*?\'|[^>])*>/',
        ));

        $this->addMappings(array(
            'date' => 'tag',
        ));
    }
}

?>

