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
