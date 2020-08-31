<?php
/* This is part of KWV core providing default behavior. It should not be !disabled. */

$context->hooks->registerLast("external", function ($url) {
    return $url;
});

$context->hooks->registerLast("translate", function () {
    return sprintf(...func_get_args());
});

$context->hooks->registerFirst("echo_empty", function (array &$vars) {
    $css = ["normalize.css", "kwv.css"];
    $js = ["kwv.js"];
    foreach (glob("plugins/*.{css,js}", GLOB_BRACE) as $file) {
        ${substr(strrchr($file, "."), 1)}[] = $file;
    }
    $vars += [
        "title"     => $this("Kanbani Web Viewer"),
        "css"       => $css,
        "js"        => $js,
    ];
});

$context->hooks->registerLast("format", function ($str) {
    $str = preg_replace('/(\v\h*\v\h*)+/u', "</p><p>", htmlspecialchars($str));
    $str = preg_replace('/\v/u', "<br>", $str);
    $str = preg_replace_callback('!\bhttps?://[\w\-./]+!ui', function ($match) {
        return "<a href=\"".htmlspecialchars($this->hooks->external($match[0])).'"'.
                " target=\"_blank\">".htmlspecialchars($match[0])."</a>";
    }, $str);
    return "<p>$str</p>";
});

$context->hooks->registerLast("canonical", function ($query) {
    $url = $this->server["REQUEST_SCHEME"]."://".$this->server["HTTP_HOST"];
    if ($this->server["SERVER_PORT"] != ($this->server["REQUEST_SCHEME"] === "http" ? 80 : 443)) {
        $url .= ":".$this->server["SERVER_PORT"];
    }
    // /                      = /
    // /index.php             = /index.php
    // /kwv/root/             = /
    // /kwv/root/index.php    = /index.php
    $name = strrchr($this->server["REQUEST_URI"], "/");
    $url .= substr($this->server["REQUEST_URI"], 0, -strlen($name))."/";
    // The simplest way is using URLs of form /index.php?profile=profileID&query...
    // KWV also supports cleaner URLs of form /profileID?query... since virtually
    // every page requires profileID but this needs configuring mod_rewrite or similar, e.g. for nginx:
    // rewrite ^/([\w-]+)$ /index.php?profile=$1&$args;
    $name = strtok($name, "?");
    if ($name !== "/" && $name !== "/index.php") {
      if (is_string($query)) {
            parse_str($query, $query);
      }
      if (isset($query["profile"])) {
        $url .= urlencode($query["profile"]);
        unset($query["profile"]);
      }
    }
    if (is_array($query)) {
        $query = http_build_query($query);
    }
    if (strlen($query)) {
        $url .= "?$query";
    }
    return $url;
});

$context->hooks->registerLast("serve_decrypt", function () {
    $this->hooks->echo_decryptPage();
    return true;
});

if ($context->config["cache"]) {
    $context->hooks->registerFirst("echo_qrCode", function (array &$vars) {
        $hash = hash("sha1", "$vars[large].$vars[data]");
        if (is_file($file = $this->config["cache"]."/qr.$hash")) {
            $handle = fopen($file, "rb");
            $vars["headers"] = array_merge($vars["headers"],
                json_decode(fread($handle, (int) fgets($handle))));
            fpassthru($handle);
            fclose($handle);
            // Touching after reading so that the file's age is not
            // bumped on read errors.
            touch($file);
            return true;
        } else {
            $this->hooks->frame->core_cache = $file;
            ob_start();
        }
    });

    $context->hooks->registerAfter("echo_qrCode", function ($result, array $vars) {
        if ($result && $file = ($this->hooks->frame->core_cache ?? "")) {
            $handle = fopen($file, "wb");
            $headers = json_encode($vars["headers"], Kanbani\JSON_FLAGS);
            fwrite($handle, (strlen($headers) + 1)."\n");
            // Adding \n for readability in a text editor.
            fwrite($handle, "$headers\n");
            fwrite($handle, ob_get_flush());
            fclose($handle);
        }
    });
}
