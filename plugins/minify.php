<?php
/* This plugin automatically merges and compresses CSS and JavaScript resources.
   It needs configured cache directory, and also the minifier (github.com/tdewolff/minify)
   installed on this server (Debian/Ubuntu: apt-get install minify). */

// Warning: the minified file is put in place of the first non-minified file in
// the list of all CSS/JS resources.
// External files are not minified. Given resource list A, B, C, D, E, if B and
// D are local then result would be A, minified_B_D, C, E - if D depends on C
// then it will break.
// All minified files should use the same charset and strict mode (for JS).

namespace Kanbani;

if ($cache = $context->config["cache"]) {
    $context->hooks->registerFirst("echo_empty", function (array &$vars) use ($cache) {
        // $css
        // $js
        extract($vars, EXTR_REFS);
        foreach (["css", "js"] as $var) {
            $all = array_filter($$var, "is_file");
            if ($all) {     // keep only local files.
                $latest = max(array_map("filemtime", $all));
                $file = "$cache/".hash("sha1", join("*", $all)).".$latest.$var";
                if (!is_file($file)) {
                    $temp = tempnam($cache, $var);
                    try {
                        // Not using $output because it can corrupt Unicode.
                        exec("minify -o".escapeshellarg($temp)." ".
                                join(" ", array_map("escapeshellarg", $all)),
                             $output, $code);
                        if (!$code && filesize($temp)) {
                            rename($temp, $file);
                        }
                    } finally {
                        try {
                            unlink($temp);
                        } catch (\Throwable $e) {}
                    }
                }
                if (is_file($file)) {
                    array_splice($$var, array_search($all[0], $$var), 1, [$file]);
                    $$var = array_diff($$var, $all);
                }
            }
        }
    });
}