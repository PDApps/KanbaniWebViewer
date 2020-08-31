<?php
/* This plugin prevents various web robots and crawlers from browsing this KWV installation. */

header("X-Robots-Tag: none");

// "Legit" bots usually have a link:// or contact@email in their UA string.
if (preg_match("!://|@!", $context->server["HTTP_USER_AGENT"] ?? "")) {
    http_response_code(403);
    die("Access by robots is denied.");
}
