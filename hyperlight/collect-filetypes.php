#!/usr/bin/env php
<?php

require('hyperlight.php');

// Parse the languages/ subdirectory and compile a filetypes list that is
// considered by `hyperlight_file` to automatically recognize filetypes.

//
// Configuration
//

$dir = 'languages';
$output_file = "$dir/filetypes";

//
// Gather extensions
//

$files = glob("$dir/*.php");
$languages = preg_replace("/^$dir.(\w+)\.php$/i", '$1', $files);
$result = array_map('ext_from_lang', $languages);

//
// Write to output file
//

$output = implode("\n", $result);
file_put_contents($output_file, $output);

function ext_from_lang($language) {
    $lang = HyperLanguage::compileFromName($language);
    return "$language:" . implode(',', $lang->extensions());
}

?>
Updated <?php echo $output_file; ?>.
