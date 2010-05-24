<?php

require('hyperlight.php');

function hyperlight_test($file, $lang = null) {
    global $tests;
    if ($lang === null)
        $lang = $file;
    if (!empty($tests) and !in_array(strtolower($lang), $tests))
        return;
    $fname = 'tests/' . strtolower($file);
    $code = file_get_contents($fname);
    $hl = new Hyperlight($lang);
    $pretty_name = $hl->language()->name();
    $title = $file === $lang ?
        "<h2>Test for language {$pretty_name}</h2>" :
        "<h2>Test with file “{$file}” for language {$pretty_name}</h2>";
    echo "$title\n";
    #$lines = count(explode("\n", $code)) - 1;
    #echo '<ol class="line-numbers">';
    #for ($i = 0; $i < $lines; $i++)
    #    echo '<li><div>&nbsp;</div></li>';
    #echo '</ol>';
    ?><pre class="source-code <?php echo strtolower($lang); ?>"><?php $hl->renderAndPrint($code); ?></pre><?php
}

function write_prolog($title, $colorscheme, $debug) {
    ?><!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" type="text/css" href="colors/<?php echo $colorscheme; ?>.css"/>
    <style type="text/css">
        pre { padding: 0.5em; padding-left: 16px; }
        pre .fold-header { cursor: pointer; display: block; float: left; height: 1em; }
        pre .fold-header .dots { display: none; }
        pre .fold-header { padding-left: 1px; border: 1px solid transparent; }
        pre .fold-header.closed { border: 1px dotted; padding: 0 0.4em; padding-left: 1px; }
        pre .fold-header.closed .dots { display: inline; }
        pre .fold-header.closed .dots:after { content: '…'; }
    </style>

<?php if ($debug): ?>
    <style type="text/css">
        pre span[class]:before, pre span[class]:after {
            background: #FFC;
            color: black;
            font-family: Lucida Grande;
            font-weight: normal;
            font-style: normal;
            font-size: 0.6em;
        }
        pre span[class]:before { content: '‹' attr(class) '›'; }
        pre span[class]:after { content: '‹/' attr(class) '›'; }
    </style>
<?php endif; ?>
</head>
<body>
    <h1><?php echo $title; ?></h1>
<?php
}

function write_epilog() {
    echo '</body></html>';
}

if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])):

    $default_colorscheme = 'vibrant-ink';
    if (isset($_GET['style'])) {
        $colorscheme = $_GET['style'];
        if (!file_exists("colors/$colorscheme.css"))
            $colorscheme = $default_colorscheme;
    }
    else
        $colorscheme = $default_colorscheme;

    write_prolog('Hyperlight tests', $colorscheme, isset($_GET['debug']));
?>
    <h2>A few small tests:</h2>

    <p>Look, ma: Inline code. Start off by writing <?php hyperlight('#include <iostream>', 'cpp', 'code'); ?>
    at the beginning of your newly-created <code>main.cpp</code> file.
    Then you can insert the following code below:</p>
    <?php hyperlight('int main() {
    std::cout << "Hello, world!" << std::endl;
}', 'cpp'); ?>
    <p>Next, let's compile this code and execute it. This is done easily on the console:</p>
    <pre>$ g++ -Wall -pedantic -o main main.cpp
$ ./main
Hello, world!</pre>
    Congratulations! You've just run your first C++ program.
<?php


$args = array_diff_key($_GET, array('debug' => '', 'style' => ''));
$args = array_keys($args);

$tests = empty($args) ? array() : explode(',', implode(',', $args));

if (!empty($tests))
    echo '<p>Showing only test(s) <strong>' . implode(', ', $tests) . '</strong>.</p>';

hyperlight_test('python');
hyperlight_test('csharp');
hyperlight_test('VB');
hyperlight_test('simple.css', 'css');
hyperlight_test('../' . basename(__FILE__), 'php');
hyperlight_test('preg_helper.php', 'php');
hyperlight_test('pizzachili_api.h', 'cpp');
hyperlight_test('XML');
hyperlight_test('style.css', 'css');

?>
<h2>Test runs</h2>
<?php

require('tests.php');

?><pre><?php
Test::run('PregMerge');
?></pre><?php
    write_epilog();

endif; ?>
