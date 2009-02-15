<?php define('HYPERLIGHT_SHORTCUT', true); require('../../hyperlight.php'); ?>
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
                <a id="introduction"></a><h3>Introduction</h3>
                <ul id="guide-menu">
                    <li><a href="#introduction">Introduction</a></li>
                    <li><a href="#getting-started">Getting Started</a></li>
                    <li><a href="#creating-themes">Creating Themes</a></li>
                    <li><a href="#creating-syntax-definitions">Creating Syntax Definitions</a></li>
                </ul>
                <p>The user guide aims to ease the first steps in using Hyperlight. Luckily, the interface is really easy to use, mainly because it’s also really small (remember: this is a <em>good</em> thing). Most of the configuration goes on behind the scenes or in the <acronym>CSS</acronym>.</p>
                <p>The first part will focus on the end user. However, most users will probably want to customize behaviour in one way or another. We will therefore also discuss how to modify or create themes and syntax definitions. And so, without further ado …</p>

                <a id="getting-started"></a><h3>Getting Started</h3>
                <p>At this point, let’s assume that you have already downloaded and unzipped the package into its target location because let’s face it, who wants to have an umpteenth description of how to unzip an archive?</p>
                <p>To use Hyperlight, all you have to do is to include the main file into your <acronym>PHP</acronym> source code and invoke the highlighting function. To highlight a source code, this is all you need to do:</p>
                <?php hy('hyperlight($code, $language);', 'iphp'); ?>
                <p>To put this in some more context, imagine that you want to highlight the current file. Our program might not be self-replicating or self-modifying but it sure is self-embellishing.</p>
                <?php hyf('code1.php'); ?>
                <p>It really can’t get much simpler than that.</p>
                <p>Notice that we didn’t have to put special <acronym>HTML</acronym> tags around our code. <a href="../ref/#hyperlight-function"><code>hyperlight</code></a> does this for us. But don’t worry about lack of control. This function has two more optional arguments that you can use to control how these surrounding tags should look like. The first controls which surrounding tag to use and defaults to – what a surprise – <?php hyperlight('<pre>', 'xml', 'code'); ?>. The second argument controls the attributes that the tag should have (in addition to the <code>class</code>). For a detailed description of how to use these arguments, read the reference entry on the <a href="../ref/#hyperlight-function"><code>hyperlight</code></a> function.</p>

                <div class="notice">
                    <p>Regardless of the fourth argument, the <code>class</code> attribute is <strong>always</strong> present and can’t be removed – and shouldn’t be: it’s necessary for the <acronym>CSS</acronym> themes to work.</p>
                </div>

                <p><acronym>PHP</acronym> is something of a special case; it requires a <code>&lt;?php</code> to start a <acronym>PHP</acronym> block. However, when posting code, this is often omitted because only a short snippet is posted. That’s fine. Hyperlight offers a special language tag for this rather unique case: <code>iphp</code>.</p>
                <?php hy('hyperlight($code, \'iphp\');', 'iphp'); ?>
                <p>Another special case occurs when we want to highlight a file. Hyperlight provides a shortcut to do this: <a href="../ref/#hyperlight_file-function"><code>hyperlight_file</code></a>. As an added bonus, if you pass a regular file to this function, you don’t need to specify the file’s language explicitly. Hyperlight tries to figure the right language out by itself, based on the filename extension.</p>
                <p>Now, that’s all there really is to it. Told you it was easy. ;-) But trust me, it gets more <em>interesting</em> once we want to create our own themes or language definitions.</p>

                <a id="creating-themes"></a><h3>Creating Themes</h3>
                <p>The whole visual appearance of the highlighted code in Hyperlight is based on a few simple <acronym>CSS</acronym> rules. The strength of Hyperlight lies in the fact that these rules are controlled by class names that are the same across all language definitions, thereby making it easy to adapt one theme for all languages.</p>
                <p>At the same time, a finer degree of control might be needed because one size doesn’t fit all. This is possible in three ways. First, language definitions can define <em>mappings</em> between different class names. Secondly, rules can be combined and nested. Lastly, if all else fails, code is also tagged with a language-specific class name. This can be used to establish a specific rule for one language only. Of course, these should be used sparingly because they make it much harder to develop colour themes that are usable across all language definitions. We will examine all these techniques in due course.</p>
                <a id="the-theme-file"></a><h4>The Theme File</h4>
                <p>A theme is just a <acronym>CSS</acronym> stylesheet that defines a set of rules based on class names. Therefore, in order to write a theme you need to know the rudiments of <acronym>CSS</acronym>. To limit the scope of the styles and make the theme definitions interoperate nicely with other, existing styles, it’s recommended that you prefix all theme-specific rules with <?php hy('.source-code', 'css', 'code'); ?>.</p> 

                <h4>An Example</h4>
                <p>Let’s look at a small example theme file, actually a fragment of <code>zenburn.css</code>, which is used for this document.</p>
                <?php hyf('mini-zenburn.css'); ?>
                <p>Here, we can see three things:</p>
                <ol>
                    <li>
                        <p>The first rule sets up the environment. However, refrain from setting more specific information here. In particular, don’t set a border or a font face. These are settings that may be set elsewhere.</p>
                    </li>
                    <li>
                        <p>The third rule is nested: it applies only to “todo”s nested inside a comment. As an example, it might apply to</p>
                        <?php hy('<span class="comment"><span class="todo">TODO:</class> i18n</span>', 'xml'); ?>
                    </li>
                    <li>
                        <p>The last rule, on the other hand, is a <em>specialization</em>. It applies only to built-in keywords and overrides the more general keyword styles. It applies to:</p>
                        <?php hy('<span class="keyword builtin">isset</span>', 'xml'); ?>
                    </li>
                </ol>

                <h4>Core Theme Classes</h4>
                <p>Hyperlight uses <em>mappings</em> to unify the kinds of <acronym>CSS</acronym> classes used. This drastically reduces the number of possible class names across all languages, while still preserving a representative subset. All theme files should at least be aware of this subset. Notice that this doesn’t mean they should provide different styles for all possible rules – this would probably be a bad idea since it clutters the visual needlessly.</p>
                <p>Here is an alphabetically sorted list of these core class names:</p>
                <ul>
                <li><code>char</code> – a character literal</li>
                <li><code>comment</code> – a source code comment</li>
                <li><code>doc</code> – a documentation tag; usually nested inside a <code>comment</code></li>
                <li><code>escaped</code> – some escaped entity; usually nested inside a <code>string</code></li>
                <li><code>identifier</code> – an identifier such as a variable or a function name</li>
                <li><code>keyword</code> – any keyword or reserved word in the language
                    <ul>
                        <li><code>builtin</code> – a built-in function, such as <?php hy('isset', 'iphp', 'code'); ?></li>
                        <li><code>literal</code> – a built-in literal, such as <?php hy('true', 'iphp', 'code'); ?></li>
                        <li><code>operator</code> – an operator keyword, such as <?php hy('instanceof', 'iphp', 'code'); ?></li>
                        <li><code>preprocessor</code> – a preprocessor statement, such as <?php hy('#define', 'cpp', 'code'); ?> in C++</li>
                        <li><code>type</code> – a built-in data type, such as <?php hy('int', 'iphp', 'code'); ?></li>
                    </ul>
                </li>
                <li><code>number</code> – a numeric literal</li>
                <li><code>regex</code> – a regular expression literal</li>
                <li><code>string</code> – a string literal</li>
                <li><code>tag</code> – a tag; this is mostly used in <acronym>HTML</acronym> but also elsewhere</li>
                <li><code>todo</code> – a “todo”-like annotation in a comment</li>
                </ul>

                <p>Since languages such as <acronym>HTML</acronym> or <acronym>CSS</acronym> in particular use very different syntactical elements from other languages, it’s reasonable to reuse the above classes in other context. For example, <acronym>HTML</acronym> may redefine the <code>keyword</code> class for tag names.
            </div>
        </div>
    </body>
</html>
<!-- vim:ft=html
-->
