<?php

require_once 'php.php';

class IphpLanguage extends PhpLanguage {
    public function __construct() {
        parent::__construct();
        $this->removeState('init');
        $this->addStates(array('init' => $this->getState('php')));
    }
}

?>
