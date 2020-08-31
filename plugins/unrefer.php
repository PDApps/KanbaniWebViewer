<?php
/* This plugin hides referring URLs for external links on KWV pages. */

// https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Referrer-Policy
header("Referrer-Policy: origin-when-cross-origin");

if (count(get_included_files()) === 1) {
    require __DIR__."/../helpers.php";
    $context = Kanbani\initializeGlobal();
    $known = $context->config["secret"].$context->request["url"];
    if (hash_equals(hash("sha1", $known), $context->request["hash"])) {
        header("Location: ".$context->request["url"]);
    } else {
        http_response_code(403);
    }
    exit;
}

$context->hooks->registerFirst("external", function ($url) {
    $via = $this->config["unrefer.via"] ?? "";
    $via and $url = $via.urlencode($url);
    $hash = hash("sha1", $this->config["secret"].$url);
    return "plugins/".basename(__FILE__)."?".http_build_query(compact("url", "hash"));
});
