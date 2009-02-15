<?php require('../hyperlight.php'); ?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>‹? Hyperlight ?› Documentation</title>
        <link rel="stylesheet" type="text/css" href="../style.css"/>
        <link rel="stylesheet" type="text/css" href="../colors/zenburn.css" id="theme"/>
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
                <h2>Content</h2>
                <ul>
                    <li><a href="#preface">Preface</a></li>
                    <li>
                        <a href="user-guide/">User Guide</a>
                        <ul>
                            <li><a href="user-guide/#introduction">Introduction</a></li>
                            <li><a href="user-guide/#getting-started">Getting Started</a></li>
                            <li><a href="user-guide/#creating-themes">Creating Themes</a></li>
                            <li><a href="user-guide/#creating-syntax-definitions">Creating Syntax Definitions</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="">Reference</a>
                        <ul>
                            <li><a href="">Class <code>Hyperlight</code></a></li>
                            <li><a href="">Function <code>hyperlight</code></a></li>
                            <li><a href="">Function <code>hyperlight_file</code></a></li>
                            <li><a href="">Class <code>HyperLanguage</code></a></li>
                            <li><a href="">Class <code>HyperlightCompiledLanguage</code></a></li>
                            <li><a href="">Class <code>Rule</code></a></li>
                            <li><a href="">Class <code>NoMatchingRuleException</a></li>
                        </ul>
                    </li>
                    <li><a href="faq/">Frequently Asked Questions</a></li>
                </ul>

                <a id="preface"></a><h2>Preface</h2>
                <blockquote>
                    Does anybody know anything cool to put here?
                </blockquote>
                <p>Good syntax highlighting is crucial for many different kinds content providers. Therefore, syntax highlighting libraries for the web have always been a central part of web development. However, requirements have changed. With more sophistication in web design came the demand for libraries that create not only high-quality highlightings but also high-quality <acronym>HTML</acronym> and <acronym>CSS</acronym> code and that are easy to use and to extend.</p>
                <p>Two libraries have raised the bar considerably: <a href="http://pygments.org/">Pygments</a> for Python, and <a href="http://coderay.rubychan.de/">CodeRay</a> for Ruby. For PHP, on the other hand, there’s no modern library that fulfills all of these requirements. This is therefore an attempt to offer a remedy.</p>
                <div id="navigation">
                    <div id="next"><a href="user-guide">Next: User guide</a></div>
                </div>
            </div>
        </div>
    </body>
</html>
<!-- vim:ft=html
-->
