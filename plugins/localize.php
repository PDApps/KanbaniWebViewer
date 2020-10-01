<?php
/* This plugin translates default English strings into another language and provides
   Customize options for switching display language and time zone. */

$languages = [];
foreach (glob("localizations/*.php") as $file) {
    $languages[] = basename($file, ".php");
}
// English is hardcoded and so always present. If en.php is missing then
// default strings are taken as is. If en.php is present then some strings
// may be overwritten in this installation.
if (!in_array("en", $languages)) {
    $languages[] = "en";
}

$context->hooks->register("start", function () use ($languages) {
    $accept = $this->request["lang"] ?? $this->cookie("kwvlang") ?? $this->server["HTTP_ACCEPT_LANGUAGE"] ?? "";
    if (preg_match_all('/\w\w(-\w\w)?/', strtolower($accept), $matches)) {
        foreach ($matches[0] as $lang) {
            if (strlen($lang) > 2 && !in_array($lang, $languages)) {
                // ru-RU -> ru
                $lang = substr($lang, 0, 2);
            }
            if (in_array($lang, $languages)) {
                $strings = is_file($file = "localizations/$lang.php")
                    ? require "localizations/$lang.php" : [];
                $this->language = $lang;
                $this->locale = strncasecmp(PHP_OS, "win", 3)
                    ? (isset($strings["localeUnix"]) ? $strings["localeUnix"].".UTF-8" : $this->locale)
                    : ($strings["localeWindows"] ?? $this->locale);
                $this->tz = $this->request["tz"] ?? $this->cookie("kwvtz") ?? $this->config["locale.defaultTZ"] ?? $strings["tz"] ?? $this->tz;
                // Unlike other filters, locale and TZ should be passed to other pages, e.g. viewCard.
                $this->cookie("kwvlang", $this->language);
                $this->cookie("kwvtz", $this->tz);
                break;
            }
        }
    }
});

$context->hooks->register("echo_empty", function (array &$vars) {
    $tz = &$vars["bodyAttributes"]["data-tz"];
    $tz = $this->tz;
});

$context->hooks->register("translate", function (...$args) {
    $file = "localizations/$this->language.php";
    if (preg_match('/^[\w-]+$/', $this->language) && is_file($file)) {
        $strings = require $file;
        $args[0] = $strings[$args[0]] ?? $args[0];
    }
    return sprintf(...$args);
});

$context->hooks->registerFirst("echo_boardCustomize", function () use ($languages) {
    if ($this->profileID) {
        // Don't use timezone_abbreviations_list() because it contains duplicates
        // (returns all zones used historically for a location).
        $zones = timezone_identifiers_list();
?>
        <?php if (count($languages) > 1) {?>
            <tr>
                <th class="tbl__th"><?=$this("Language (%s):", "language")?></th>
                <td>
                    <select name="lang">
                        <?=Kanbani\htmlOptions($languages, $languages, $this->language)?>
                    </select>
                </td>
            </tr>
        <?php }?>
        <tr>
            <th class="tbl__th"><?=$this("Time zone:")?></th>
            <td>
                <select name="tz">
                    <?=Kanbani\htmlOptions($zones, $zones, $this->tz)?>
                </select>
            </td>
        </tr>
<?php
    }
});
