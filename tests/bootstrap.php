<?php

function loader($class)
{
    $file = 'src/' . $class . '.php';
    if (file_exists($file)) {
        require $file;
    }
}

require_once('src/tempodb.php');
spl_autoload_register('loader');
