<?php
/* https://pdapps.org/kanbani/web | License: MIT */
namespace Kanbani;

// array_column(), Throwable, ??.
if (version_compare(PHP_VERSION, "7.0.0", "<")) {
    $msg = "Kanbani Web Viewer requires PHP 7+.";
    if (!ini_get("display_errors")) {
        echo $msg;
    }
    throw new \RuntimeException($msg);
}

require_once "helpers.php";

$context = initializeGlobal();

// To enable creation of form submit buttons that pass more than one parameter,
// the ?do parameter is itself an URL-encoded string: ?do=export%26format%3Dtxt.
// do's query takes precedence over regular $_REQUEST members:
//     <input name="format" value="will be overridden">
//     <button name="do" value="export&format=txt">
$task = strtok(($context->request + ["do" => "viewBoard"])["do"], "&");
parse_str(strtok(null), $additional);
$context->request = $additional + $context->request;

$served = $context->hooks->trigger("serve_$task");

if ($served === null) {
    http_response_code(404);
    throw new PublicException("Nobody could handle your request.");
}
