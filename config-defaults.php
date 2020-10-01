<?php
$tryCache = function ($dir) {
    try {
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        return is_writable($dir) ? $dir : null;
    } catch (\Throwable $e) {}
};

return [
    // The cache directory is used in various places. It can be disabled by setting
    // to null and only ever work in memory (will limit KWV's functionality).
    // If changing from the default, ensure these files are available from the web
    // under http://...kwv_root/cache/ since some plugins require this.
    //
    // If enabled, files older than $cacheAge are cleaned up every $cacheGC'nth
    // request (if that is 0 then on every request, if 1 then on every 2nd
    // request, if 2 then on every 3rd, etc.). If $cacheGC is < 0 then cleaning
    // is disabled. You can clean cache manually by running plugins/cache-clean.php:
    // @daily /usr/bin/php .../kwv/plugins/cache-clean.php
    // Cache path and age can be given as optional arguments:
    // php cache-clean.php [/tmp [3600]]
    "cache"         => $tryCache("cache"),
    "cacheAge"      => 7 * 24 * 3600,
    "cacheGC"       => 10000,

    // Private string used as a shared secret and for generating MACs. The default
    // takes path to KWV's installation and the contents of the user config file,
    // as these are the only variable components available in zero-configuration mode.
    "secret"        => __FILE__.(is_file("config.php") ? hash_file("sha1", "config.php") : ""),

    // Use this to "disable" PHP plugins without removing files in plugins/.
    // Can also use this to add plugins from other paths.
    // This only affects PHP plugins. JS/CSS plugins are always included but you
    // can exclude some with a hook (see plugins/minify.php for an example).
    "plugins"       => glob("plugins/*.php"),

    // Determines what happens when user opens a viewBoard page with no profile ID.
    // Because viewBoard is the default page (used if no page was supplied), this
    // is the action for opening KWV's root page.
    // Value is either false (report error), string URL (redirect) or Closure
    // (must return one of those values).
    "index" => function () {
        if (in_array("plugins/welcome.php", $this->config["plugins"])) {
            return $this->hooks->canonical(["profile" => "Welcome"]);
        }
    },

    // Settings for plugins follow. They only work if corresponding plugins are enabled.

    // Most Kanbani transports write sync data as regular files: a directory
    // per each profile, a boards/ directory inside and a file per one board:
    // $profileID/boards/$boardID. This config option specifies the path to your
    // sync root (the Base URL parameter in Kanbani's sync profile).
    // It works for FTP, SFTP and WebDAV (matches Apache's DocumentRoot). Example:
    //"unserialize.path" => "/home/kanbani/ftp",
    "unserialize.path" => null,

    // Enabling this (together with cache) improves performance of recently accessed
    // profiles. Encrypted profiles are decrypted in memory on every request, never cached.
    "unserialize.cache" => true,

    // Use null to prevent joining profiles (allow only web access).
    //
    // $qrCode = new QrCodeData;
    // $qrCode->mode = QrCodeData::...;
    // $qrCode->transport = new QrCodeWebDAV("https://example.com",
    //     new QrCodePassword("login", "pass"));
    "unserialize.qrCode" => null,

    // If this is enabled, when user imports an encrypted Kanbani board, it is
    // copied to cache/ and a temporary profile ID is generated (just like for
    // unencrypted import) so that such board can be viewed on another system as
    // long as the profile ID is known.
    // If this is disabled, imported board's page is generated during the same
    // page request and so cannot be revisited later.
    "import-cache.encrypted" => true,

    // This TZ is used if user has not chosen a TZ explicitly. If this is null then
    // default TZ of user's locale is used (and "UTC" for English).
    "locale.defaultTZ" => null,

    // By default, every external URL goes through plugins/unrefer.php. This removes pathname
    // information (including sync profile ID) from the referring URL but keeps the
    // domain name.
    //
    // Setting this to a web resource like nullrefer.com hides the domain as well,
    // preventing KWV pages from showing up in target website logs at all. Example:
    //"unrefer.via" => "https://nullrefer.com/?",
    // It would create links like so: https://nullrefer.com/?https%3A%2F%2Fgoogle.com
    "unrefer.via" => "",

    // Set to a mailto: or other url:// to put up an "abuse contact" button on
    // every page. See plugins/abuse.php.
    "abuse.href" => "",
];