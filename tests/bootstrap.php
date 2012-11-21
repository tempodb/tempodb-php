<?php

function loader($class)
{
    $file = 'src/' . $class . '.php';
    if (file_exists($file)) {
        require $file;
    }
}

require_once('src/tempodb.php');
require_once('src/http/HttpRequest.php');
spl_autoload_register('loader');
