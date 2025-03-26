<?php
error_reporting(E_ALL & ~E_WARNING);
function customErrorHandler($errno, $errstr, $errfile, $errline)
{
    error_log("Error [$errno]: $errstr in $errfile on line $errline", 0);
    header("Location: error500.php");
    exit();
}

function customExceptionHandler($exception)
{
    error_log("Uncaught exception: " . $exception->getMessage(), 0);
    header("Location: error500.php");
    exit();
}

set_error_handler("customErrorHandler");
set_exception_handler("customExceptionHandler");
