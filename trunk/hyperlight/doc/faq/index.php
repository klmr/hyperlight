<?php require('../../hyperlight.php'); ?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>‹? Hyperlight ?› Documentation – FAQ</title>
        <link rel="stylesheet" type="text/css" href="../../style.css"/>
        <link rel="stylesheet" type="text/css" href="../../colors/zenburn.css" id="theme"/>
    </head>

    <body>
        <div id="head">
            <div class="text">
                <h1>Documentation</h1>
            </div>
        </div>
        <div id="content">
            <div id="swoosh"></div>
            <div class="text">
                <h2>Frequently Asked Questions</h2>
                <dl>
                    <dt><h3>The PHP highligher is broken, right?</h3></dt>
                    <dd>
                    <p>Yes – unfortunately. This is by design. In our defence, <em>no</em> correct <acronym>PHP</acronym> highligher exists. To see why, consider the following code fragment, which is completely valid <acronym>PHP</acronym>:</p>
                    <?php hyperlight('<p class="some-class<?php if (rand(0, 1) === 1) echo \'"\'; ?>>foo</p>', 'php'); ?>
                    <p>(On a related note, this code fragment actually breaks the editor this text was written in.)</p>
                    <p>Executing this code <em>might</em> produce valid <acronym>HTML</acronym> – or it might not, depending on the outcome of the coinflip. The editor (or, in our case, highlighter) has no idea about the outcome without executing the code. And even then, the result changes with every subsequent execution. But even if the above code were more predictable, it would have to be executed in order to determine how to highlight the code. This is not done by Hyperlight or any other highlighting engine. The output is therefore wrong in a few cases. Fortunately, such cases should be relatively rare.</p>
                    </dd>
                </dl>
            </div>
        </div>
    </body>
</html>
<!-- vim:ft=html
-->
