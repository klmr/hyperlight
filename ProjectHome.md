# Hyperlight #

Hyperlight highlights source code, pure and simple. It's

  * **Easy to use** – using it is a matter of one function call.
  * **Easy to extend** – write your own language definitions in PHP using regular expressions.
  * **Powerful** – since the parser supports states, it can do so much more than just regular languages.
  * **Compliant** – Hyperlight produces valid, semantic strict XHTML.
  * **Configurable** – Hyperlight produces logical CSS rules which can be used by beautiful colour themes.

# Why? #

Good syntax highlighting is crucial for many different kinds content providers. Therefore, syntax highlighting libraries for the web have always been a central part of web development. However, requirements have changed. With more sophistication in web design came the demand for libraries that create not only high-quality highlightings but also high-quality HTML and CSS code and that are easy to use and to extend.

Two libraries have raised the bar considerably: [Pygments](http://pygments.org/) for Python, and [CodeRay](http://coderay.rubychan.de/) for Ruby. For PHP, on the other hand, there’s no modern library that fulfils all of these requirements. This is therefore an attempt to offer a remedy.

# Features #

## Easy to Use ##

There’s no configuration. The following code will highlight your source code. Nothing more needs to be said or done.

```
// Create a new hyperlight instance and print the highlighted code.
$highlighter = new HyperLight($code, 'cpp');
$highlighter->theResult();
```

Even easier, there’s a handy function `hyperlight` for lightweight usage, especially in HTML templates:

```
<?php hyperlight($code, 'php'); ?>
```

This code creates a `<pre>` container around the code. This can be controlled with a third argument to the function.

## Full CSS Support ##

Hyperlight creates tags that act as CSS class names to describe syntaxtical elements. Where applicable, they get nested or stacked.

To understand nesting, consider this comment in source code:

```
// TODO: Make code work.
```

This isn’t only a comment – it’s a “To do”. Hyperlight allows you to highlight it as such:

```
.comment { color: gray; }
.comment .todo { font-weight: bold; }
```

Stacked class names allow refinement of definitions. For example, there are several types of keywords: language constructs, built-in data types, operators etc. … This is taken into account by Hyperlight and can be used in the following CSS definition:

```
.keyword { color: #008; }
.keyword.type { color: #088; }
.keyword.operator { font-weight: bold; }
```