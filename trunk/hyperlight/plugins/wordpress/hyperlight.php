<?php
// Plugin Name: Hyperlight for WordPress
// Plugin URI: http://code.google.com/p/hyperlight
// Description: Static, CSS-based syntax highlighter for a variety of languages.
// Author: Konrad Rudolph
// Version: 0.1.0
// Author URI: http://madrat.net/

require_once('hyperlight/hyperlight.php');

$hyperlight_codes = array();
$hyperlight_replace_token = uniqid('hyperlight', true) . ':';
$hyperlight_code_index = 0;

//
// Wire plugin into WordPress
//

// ToDo Make configurable; include admin interface and head section.

add_filter('the_content', 'hyperlight_before_filter', 1);
add_filter('the_excerpt', 'hyperlight_before_filter', 1);
add_filter('comment_text', 'hyperlight_before_filter', 1);

add_filter('the_content', 'hyperlight_after_filter', 99);
add_filter('the_excerpt', 'hyperlight_after_filter', 99);
add_filter('comment_text', 'hyperlight_after_filter', 99);

// Replace code blocks which specify a `lang` argument with a replace token. The
// token is a key in a dictionary and will be replaced by the after-filter with
// the corresponding highlighted source code.
// TODO Activate similar mechanism for <code>!
function hyperlight_before_filter($content) {
    return preg_replace_callback(
        '#<pre(.*?)>(.*?)</pre>#is',
        'hyperlight_highlight_block',
        $content
    );
}

// Replace the hyperlight replace tokens with the corresponding highlighted
// source code.
function hyperlight_after_filter($content) {
    global $hyperlight_replace_token;
    return preg_replace_callback(
        "/$hyperlight_replace_token\d+/",
        'hyperlight_insert_block',
        $content
    );
}

function hyperlight_highlight_block($match) {
    global $hyperlight_codes, $hyperlight_replace_token, $hyperlight_code_index;

    // Notice: a key and its value must NOT be separated by space!
    $attributes = preg_split('/\s+/s', trim($match[1]));
    $code = $match[2];

    if (count($attributes) > 0) {
        $new_attr = array();
        foreach ($attributes as $attr) {
            list($name, $value) = explode('=', $attr);
            $new_attr[$name] = $value;
        }
        $attributes = $new_attr;
    }

    if (array_key_exists('lang', $attributes)) {
        $lang = trim($attributes['lang'], '"\'');
        $attributes = array_diff_key($attributes, array('lang' => ''));
    }

    if (!isset($lang)) return $match[0]; // No language given: don't highlight.

    $quote = '"';
    $class = "source-code $lang";

    if (array_key_exists('class', $attributes)) {
        $oldclass = $attributes['class'];
        if (substr($oldclass, 0, 1) === "'")
            $quote = "'";
        $class .= ' ' . trim($oldclass, '"\'');
    }

    $attributes['class'] = "$quote$class$quote";

    $new_attr = array();
    foreach ($attributes as $key => $value)
        $new_attr[] = "$key=$value";

    $attributes = ' ' . implode(' ', $new_attr);
    $hyperlight = new Hyperlight($lang);
    $code = $hyperlight->render($code);
    $index = "$hyperlight_replace_token$hyperlight_code_index";
    ++$hyperlight_code_index;
    $hyperlight_codes[$index] = "<pre$attributes>$code</pre>";
    return $index;
}

function hyperlight_insert_block($match) {
    global $hyperlight_codes;
    return $hyperlight_codes[$match[0]];
}

?>
