=== Plugin Name ===
Contributors: Konrad Rudolph
Tags: syntax highlighting, syntax highlight, syntax formatting, code formatting, code, formatting, highlight, syntax
Requires at least: 2.0.2
Tested up to: 2.8.6
Stable tag: trunk

A code highlighting plugin for WordPress that just works, and is highly configurable using CSS.

== Description ==

Hyperlight highlights source code, pure and simple. It's

* **Easy to use** -- using it is a matter of one function call.
* **Easy to extend** -- write your own language definitions in PHP using regular expressions.
* **Powerful** -- since the parser supports states, it can do so much more than just regular languages.
* **Compliant** -- Hyperlight produces valid, semantic strict XHTML.
* **Configurable** -- Hyperlight produces logical CSS rules which can be used by beautiful colour themes. 

== Installation ==

1. Create a folder called `hyperlight` in the `/wp-content/plugins/` directory.
1. Copy the file `hyperlight.php` from the current folder into this folder.
1. Create (yet another!) a sub-folder called `hyperlight`.
1. Copy all the hyperlight files there.

You should end up with a directory structure like this:

* `hyperlight/`
    * `hyperlight.php` -- The WordPress plugin file
    * `readme.txt` -- This file
    * `hyperlight/`
      * ... all Hyperlight files, in particular:
      * `hyperlight.php` -- The main Hyperlight include file

== Frequently Asked Questions ==

= How do I highlight code? =

Code (in `<pre>` tags) is highlighted automatically if its `<pre>` tag has an attribute `lang`. For example:

    <pre lang="php">
       <?php echo "Hello world!" php?>
    </pre>

= Code doesn't appear formatted! =

Hyperlight only parses the code and adds appropriate CSS class tags to the HTML output. In order for the code to appear coloured you need to use a Hyperlight colour scheme which consists of a single CSS file that you can drop into your WordPress theme.
Refer to the [Hyperlight documentation](http://code.google.com/p/hyperlight/wiki/UserGuide) for more detail.

== Changelog ==

= 0.1 =
* Initial WordPress version.
