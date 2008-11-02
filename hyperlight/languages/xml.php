<?php

class XmlLanguage extends HyperLanguage {
    public function __construct() {
        $this->setInfo(array(
            parent::NAME => 'XML',
            parent::VERSION => '0.1',
            parent::AUTHOR => array(
                parent::NAME => 'Konrad Rudolph',
                parent::WEBSITE => 'madrat.net',
                parent::EMAIL => 'konrad_rudolph@madrat.net'
            )
        ));

        $inline = array('entity');
        $common = array('name', 'attribute' => array('double', 'single'));

        $this->addStates(array(
            'init' => array_merge(array('tag', 'cdata'), $inline),
            'tag' => array_merge(array('preprocessor', 'meta'), $common),
            'preprocessor' => $common,
            'meta' => $common,
            'attribute double' => $inline,
            'attribute single' => $inline,
        ));
        
        $this->addRules(array(
            'tag' => new Rule('/</', '/>/'),
            'cdata' => '/<!\[CDATA\[.*?\]\]>/',
            'name' => '/[a-z0-9:-]+/i',
            'preprocessor' => new Rule('/\?/'),
            'meta' => new Rule('/!/'),
            'attribute' => array(
                'double' => new Rule('/"/', '/"/'),
                'single' => new Rule("/'/", "/'/")
            ),
            'entity' => '/&.*?;/',
        ));
    }
}

?>
