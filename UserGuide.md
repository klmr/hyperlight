

# Introduction #

The user guide aims to ease the first steps in using Hyperlight. Luckily, the interface is really easy to use, mainly because it’s also really small (remember: this is a _good_ thing). Most of the configuration goes on behind the scenes or in the CSS.

The first part will focus on the end user. However, most users will probably want to customize behaviour in one way or another. We will therefore also discuss how to modify or create themes and syntax definitions. And so, without further ado …

# Getting Started #

To use Hyperlight, all you have to do is to include the main file into your PHP source code and invoke the highlighting function. To highlight a source code, this is all you need to do:

```
hyperlight($code, $language);
```

To put this in some more context, imagine that you want to highlight the current file. Our program might not be self-replicating or self-modifying but it sure is self-embellishing.

```
<?php require('path/to/hyperlight.php'); ?>
<html>
    <head>
        <title>Very simple test for Hyperlight</title>
        <link rel="stylesheet" type="text/css" href="colors/zenburn.css">
    </head>
    <body>
        <?php hyperlight(file_get_contents(__FILE__), 'php'); ?>
    </body>
</html>
```

It really can’t get much simpler than that.

Notice that we didn’t have to put special HTML tags around our code. `hyperlight` does this for us. But don’t worry about lack of control. This function has two more optional arguments that you can use to control how these surrounding tags should look like. The first controls which surrounding tag to use and defaults to – what a surprise – `<pre>`. The second argument controls the attributes that the tag should have (in addition to the `class`). For a detailed description of how to use these arguments, read the reference entry on the `hyperlight` function.

Regardless of the fourth argument, the `class` attribute is **always** present and can’t be removed – and shouldn’t be: it’s necessary for the CSS themes to work.

PHP is something of a special case; it requires a `<?php` to start a PHP block. However, when posting code, this is often omitted because only a short snippet is posted. That’s fine. Hyperlight offers a special language tag for this rather unique case: `iphp`.

```
hyperlight($code, 'iphp');
```

Another special case occurs when we want to highlight a file. Hyperlight provides a shortcut to do this: `hyperlight_file`. As an added bonus, if you pass a regular file to this function, you don’t need to specify the file’s language explicitly. Hyperlight tries to figure the right language out by itself, based on the filename extension.

Now, that’s all there really is to it. Told you it was easy. ;-) But trust me, it gets more interesting once we want to create our own themes or language definitions.

# Creating Themes #

The whole visual appearance of the highlighted code in Hyperlight is based on a few simple CSS rules. The strength of Hyperlight lies in the fact that these rules are controlled by class names that are the same across all language definitions, thereby making it easy to adapt one theme for all languages.

At the same time, a finer degree of control might be needed because one size doesn’t fit all. This is possible in three ways. First, language definitions can define _mappings_ between different class names. Secondly, rules can be combined and nested. Lastly, if all else fails, code is also tagged with a language-specific class name. This can be used to establish a specific rule for one language only. Of course, these should be used sparingly because they make it much harder to develop colour themes that are usable across all language definitions. We will examine all these techniques in due course.

## The Theme File ##

A theme is just a CSS stylesheet that defines a set of rules based on class names. Therefore, in order to write a theme you need to know the rudiments of CSS. To limit the scope of the styles and make the theme definitions interoperate nicely with other, existing styles, it’s recommended that you prefix all theme-specific rules with `.source-code.`

## An Example ##

Let’s look at a small example theme file, actually a fragment of `zenburn.css` which is a shameless copy of the [original VIM configuration](http://slinky.imukuppi.org/zenburn/) of the same name.

```
.source-code {
    background: #3F3F3F;
    color: #DCDCCC;
}

.source-code .comment {
    color: #7F9F7F;
    font-style: italic;
}

.source-code .comment .todo {
    color: #DFDFDF;
    font-weight: bold;
}

.source-code .identifier {
    color: #EFDCBC;
}

.source-code .keyword {
    color: #F0DFAF;
    font-weight: bold;
}

.source-code .keyword.builtin {
    color: #EFEF8F;
    font-weight: normal;
}
```

Here, we can see three things:

<ol>
<li><p>The first rule sets up the environment. However, refrain from setting more specific information here. In particular, don’t set a border or a font face. These are settings that may be set elsewhere.</p></li>

<li><p>The third rule is nested: it applies only to “todo”s nested inside a comment. As an example, it might apply to</p>

<pre><code>&lt;span class="comment"&gt;&lt;span class="todo"&gt;TODO:&lt;/class&gt; i18n&lt;/span&gt;<br>
</code></pre>
</li>

<li><p>The last rule, on the other hand, is a specialization. It applies only to built-in keywords and overrides the more general keyword styles. It applies to:</p>

<pre><code>&lt;span class="keyword builtin"&gt;isset&lt;/span&gt;<br>
</code></pre>
</li>
</ol>

## Core Theme Classes ##

Hyperlight uses _mappings_ to unify the kinds of CSS classes used. This drastically reduces the number of possible class names across all languages, while still preserving a representative subset. All theme files should at least be aware of this subset. Notice that this doesn’t mean they should provide different styles for all possible rules – this would probably be a bad idea since it clutters the visual needlessly.

Here is an alphabetically sorted list of these core class names:

  * `char` – a character literal
  * `comment` – a source code comment
  * `doc` – a documentation tag; usually nested inside a `comment`
  * `escaped` – some escaped entity; usually nested inside a `string`
  * `identifier` – an identifier such as a variable or a function name
  * `keyword` – any keyword or reserved word in the language
    * `builtin` – a built-in function, such as `isset`
    * `literal` – a built-in literal, such as `true`
    * `operator` – an operator keyword, such as `instanceof`
    * `preprocessor` – a preprocessor statement, such as `#define` in C++
    * `type` – a built-in data type, such as `int`
  * `number` – a numeric literal
  * `regex` – a regular expression literal
  * `string` – a string literal
  * `tag` – a tag; this is mostly used in HTML but also elsewhere
  * `todo` – a “todo”-like annotation in a comment

Since languages such as HTML or CSS in particular use very different syntactical elements from other languages, it’s reasonable to reuse the above classes in other context. For example, HTML may redefine the `keyword` class for tag names.