<?php
/* This plugin automatically removes old files in the cache directory on every Nth visitor. */

if (PHP_SAPI === "cli" && count(get_included_files()) === 1) {
    require __DIR__."/../helpers.php";
    $context = Kanbani\initializeGlobal();
    if (!empty($argv[1])) { $context->config["cache"] = $argv[1]; }
    if (!empty($argv[2])) { $context->config["cacheAge"] = $argv[2]; }
    $context->config["cacheGC"] = 0;
    if (!is_dir($context->config["cache"])) {
        echo "Arguments: clean.php [cache/path [cache-age]]", PHP_EOL;
        echo $context->config["cache"], " does not exist", PHP_EOL;
        exit(1);
    }
}

if ($dir = $context->config["cache"] && $context->config["cacheGC"] >= 0 &&
        !mt_rand(0, $context->config["cacheGC"])) {
    foreach (scandir($dir) as $file) {
        $path = "$dir/$file";
        try {
            if (is_file($path) && filemtime($path) + $context->config["cacheAge"] < time()) {
                unlink($path);
            }
        } catch (\Throwable $e) {}
    }
}
