<?php

/**
 * Merges several regular expressions into one, using the indicated 'glue'.
 *
 * This function takes care of individual modifiers so it's safe to use
 * <em>different</em> modifiers on the individual expressions. The order of
 * sub-matches is preserved as well, but the numbering isn't.
 * If {@link $names} is given, the individual expressions are captured in
 * named sub-matches using the contents of that array as names.
 * Matching pair-delimiters (e.g. <code>"{…}"</code>) are currently
 * <strong>not</strong> supported.
 *
 * This function was created after a {@link http://stackoverflow.com/questions/244959/
 * StackOverflow discussion}. Most of it was written or thought of by users
 * “porneL” and “eyelidlessness”. Many thanks to both of them.
 *
 * @param string $glue  A string to insert between the individual expressions.
 *      This should usually be either the empty string, indicating
 *      concatenation, or the pipe (<code>|</code>), indicating alternation.
 * @param array $expressions    The expressions to merge. The expressions may
 *      have arbitrary different delimiters and modifiers.
 * @param array $names  Optional. This is either an empty array or an array of
 *      strings of the same length as {@link $expressions}. In that case,
 *      the strings of this array are used to create named sub-matches for the
 *      expressions.
 * @return string An string representing a regular expression equivalent to the
 *      merged expressions. Returns <code>FALSE</code> if an error occurred.
 */
function preg_merge($glue, array $expressions, array $names = array()) {
    // … then, a miracle occurs.

    // Sanity check …

    $use_names = $names !== null and count($names) !== 0;

    if (
        $use_names and count($names) !== count($expressions) or
        !is_string($glue)
    )
        die("Sanity check failed!");
        //return false;

    $active_modifiers = array();
    $result = array();
    $n = 0;

    foreach ($expressions as $expression) {
        if ($use_names)
            $name = str_replace(' ', '_', $names[$n++]);

        // Get delimiters and modifiers:

        /*
        if (preg_match('/^(.)(.*)\\1([imsxeADSUXJu]*)$/s', $expression, $matches) === false)
            die("Sub-expression $expression didn't match pattern");
            //return false;

        $delim = $matches[1];
        $sub_expr = $matches[2];
        if ($delim !== '/') {
            // Replace occurrences by the escaped delimiter by its unescaped
            // version and escape new delimiter.
            $sub_expr = str_replace("\\$delim", $delim, $sub_expr);
            $sub_expr = str_replace('/', '\\/', $sub_expr);
        }
        $modifiers = $matches[3] === '' ? array() : str_split(trim($matches[3]));
         */

        $stripped = preg_strip($expression);

        if ($stripped === false)
            return false;

        list($sub_expr, $modifiers) = $stripped;

        // Calculate which modifiers are new and which are obsolete.

        $cancel_modifiers = array_diff($active_modifiers, $modifiers);
        $active_modifiers = $modifiers;

        $new_modifiers = implode('', $active_modifiers);
        $old_modifiers = empty($cancel_modifiers) ?
            '' : '-' . implode('', $cancel_modifiers);
        $sub_modifiers = "(?$new_modifiers$old_modifiers)";
        if ($sub_modifiers === '(?)')
            $sub_modifiers = '';

        $sub_name = $use_names ? "?<$name>" : '?:';
        $new_expr = "($sub_name$sub_modifiers$sub_expr)";
        $result[] = $new_expr;
    }

    return '/' . implode($glue, $result) . '/';
}

/**
 * Strips a regular expression string off its delimiters and modifiers.
 *
 * @param string $expression The regular expression string to strip.
 * @return array An array whose first entry is the expression itself, the
 *      second an array of delimiters. If the argument is not a valid regular
 *      expression, returns <code>FALSE</code>.
 *
 */
function preg_strip($expression) {
    if (preg_match('/^(.)(.*)\\1([imsxeADSUXJu]*)$/s', $expression, $matches) === false)
        return false;

    $delim = $matches[1];
    $sub_expr = $matches[2];
    if ($delim !== '/') {
        // Replace occurrences by the escaped delimiter by its unescaped
        // version and escape new delimiter.
        $sub_expr = str_replace("\\$delim", $delim, $sub_expr);
        $sub_expr = str_replace('/', '\\/', $sub_expr);
    }
    $modifiers = $matches[3] === '' ? array() : str_split(trim($matches[3]));

    return array($sub_expr, $modifiers);
}

?>
