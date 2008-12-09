<?php require '../../hyperlight.php'; ?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>‹? Hyperlight ?› Documentation – User Guide</title>
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
                <h2>User Guide</h2>
                <ul>
                    <li><a href="#introduction">Introduction</a></li>
                    <li><a href="#getting-started">Getting Started</a></li>
                    <li><a href="#creating-themes">Creating Themes</a></li>
                    <li><a href="#creating-syntax-definitions">Creating Syntax Definitions</a></li>
                </ul>
                <a id="introduction"></a><h3>Introduction</h3>
                <p>The user guide aims to ease the first steps in using Hyperlight. Luckily, the interface is really easy to use, mainly because it’s also really small (remember: this is a <em>good</em> thing). Most of the configuration goes on behind the scenes or in the <acronym>CSS</acronym>.</p>
                <p>The first part will focus on the end user. However, most users will probably want to customize behaviour in one way or another. We will therefore also discuss how to modify or create themes and syntax definitions. And so, without further ado …</p>

                <a id="getting-started"></a><h3>Getting Started</h3>
                <p>At this point, let’s assume that you have already downloaded and unzipped the package into its target location because let’s face it, who wants to have an umpteenth description of how to unzip an archive?</p>
                <p>To use Hyperlight, all you have to do is to include the main file into your <acronym>PHP</acronym> source code and invoke the highlighting function. To highlight a source code, this is all you need to do:</p>
                <?php hyperlight('hyperlight($code, $language);', 'iphp'); ?>
                <p>To put this in some more context, imagine that you want to highlight the current file. Our program might not be self-replicating or self-modifying but it sure is self-enhancing.</p>
                <?php hyperlight('
<?php require \'path/to/hyperlight.php\'; ?>
<html>
    <head>
        <title>Very simple test for Hyperlight</title>
        <link rel="stylesheet" type="text/css" href="colors/zenburn.css">
    </head>
    <body>
        <?php hyperlight(file_get_contents(__FILE__), \'php\'); ?>
    </body>
</html>', 'php'); ?>
                <p>It really can’t get much simpler than that.</p>
                <p>Notice that we didn’t have to write down a tag to surround our code. <a href="../ref/#hyperlight-function"><code>hyperlight</code></a> does this for us. But don’t worry about lack of control. This function has two more optional arguments that you can use to control how these surrounding tags should look like. The first controls which surrounding tag to use and defaults to – what a surprise – <?php hyperlight('<pre>', 'xml', 'code'); ?>. The second argument controls the attributes that the tag should have (in addition to the <code>class</code>). For a detailed description of how to use these arguments, read the reference entry on the <a href="../ref/#hyperlight-function"><code>hyperlight</code></a> function.</p>
                <div class="notice">
                    <p>Regardless of the fourth argument, the <code>class</code> attribute is <strong>always</strong> present and can’t be removed – and shouldn’t be: it’s necessary for the <acronym>CSS</acronym> themes to work.</p>
                </div>
            </div>
        </div>
    </body>
</html>
<!-- vim:ft=html
-->
