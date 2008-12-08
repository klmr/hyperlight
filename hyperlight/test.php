<?php

require 'hyperlight.php';

$colorscheme = 'vibrant-ink';

if (__FILE__ === $_SERVER['SCRIPT_FILENAME']):

function hyperlight_test($file, $lang = null) {
    if ($lang === null)
        $lang = $file;
    $fname = 'tests/' . strtolower($file);
    $code = file_get_contents($fname);
    $hl = new Hyperlight($code, $lang);
    $pretty_name = $hl->language()->name();
    $title = $file === $lang ?
        "<h2>Test for language {$pretty_name}</h2>" :
        "<h2>Test with file “{$file}” for language {$pretty_name}</h2>";
    echo "$title\n";
    ?><pre class="source-code <?php echo strtolower($lang); ?>"><?php $hl->theResult(); ?></pre><?php
}

?><!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title>Hyperlight Syntax Highlighter</title>
    <link rel="stylesheet" type="text/css" href="colors/<?php echo $colorscheme; ?>.css"/>
<?php if (isset($_GET['debug'])): ?>
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
    <h1>Hyperlight tests</h1>

    <h2>A few small tests:</h2>

    <p>Look, ma: Inline code. Start off by writing <?php hyperlight('#include <iostream>', 'cpp', 'code'); ?> at the beginning of your newly-created <code>main.cpp</code> file.
    Then you can insert the following code below:</p>
    <?php hyperlight('
int main() {
    std::cout << "Hello, world!" << std::endl;
}
', 'cpp'); ?>
    <p>Next, let's compile this code and execute it. This is done easily on the console:</p>
    <pre>$ g++ -Wall -pedantic -o main main.cpp
$ ./main
Hello, world!</pre>
    Congratulations! You've just run your first C++ program.
<?php

hyperlight_test('simple.css', 'css');
hyperlight_test('preg_helper.php', 'php');
hyperlight_test('pizzachili_api.h', 'cpp');
hyperlight_test('VB');
hyperlight_test('XML');
hyperlight_test('style.css', 'css');

?>
<h2>Test runs</h2>
<?php

require 'tests.php';

?><pre><?php
Test::run('PregMerge');
?></pre>
</body></html><?php

endif;

?>
