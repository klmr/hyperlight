<?php require_once('../hyperlight.php'); ?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>‹? Hyperlight ?› Examples</title>
        <link rel="stylesheet" type="text/css" href="../style.css"/>
        <script type="text/javascript" src="../jquery-1.2.6.min.js"></script>
        <script type="text/javascript" src="theme_switcher.js"></script>
        <link rel="stylesheet" type="text/css" href="../colors/zenburn.css" id="theme"/>
    </head>

    <body>
        <div id="head">
            <div class="text">
                <h1>Examples</h1>
            </div>
        </div>
        <div id="content">
            <div id="swoosh"></div>
            <div class="text">
                <ul id="switch-buttons">
                    <li><a href="" class="active" id="theme-zenburn">Zenburn</a></li>
                    <li><a href="" id="theme-vibrant-ink">Vibrant Ink</a></li>
                </ul>

                <?php hyperlight(
'function preg_strip($expression) {
    $regex = \'/^(.)(.*)\\\\1([imsxeADSUXJu]*)$/s\';
    if (preg_match($regex, $expression, $matches) !== 1)
        return false;

    $delim = $matches[1];
    $sub_expr = $matches[2];
    if ($delim !== \'/\') {
        // Replace occurrences by the escaped delimiter by its unescaped
        // version and escape new delimiter.
        $sub_expr = str_replace("\\\\$delim", $delim, $sub_expr);
        $sub_expr = str_replace(\'/\', \'\\\\/\', $sub_expr);
    }
    $modifiers = $matches[3] === \'\' ?
                 array() : str_split(trim($matches[3]));

    return array($sub_expr, $modifiers);
}', 'iphp'); ?>
            </div>
        </div>
    </body>
</html>
<!-- vim:ft=html
-->
