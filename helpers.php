<?php
/* https://pdapps.org/kanbani/web | License: MIT */
namespace Kanbani;

const JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

// Uncaught exception of this type has its message printed to the visitor.
// Other messages only show the message's class name. See plugins/exception.php.
class PublicException extends \Exception {}

class Hooks implements \ArrayAccess {
    // Allows storing any data during callbacks of the same trigger() call.
    // Do not read outside of a hook. Do not write (replace the value).
    //
    // $hooks->registerFirst("event", function () use ($hooks) {
    //     $hooks->frame->key = 123;   // setting
    // });
    // $hooks->registerAfter("event", function () use ($hooks) {
    //     echo $hooks->frame->key;    // getting
    // });
    public $frame;

    // If not null, all Closure hooks have their $this rebound to this.
    protected $context;
    protected $frames = [];

    // Below, key = event name, value = array of callable.
    protected $first = [];
    protected $normal = [];
    protected $last = [];
    protected $after = [];

    function __construct(Context $context = null) {
        $this->context = $context;
    }

    // Shortcut to the translate event:
    // $hooks("foo %s", "bar");
    // $hooks->trigger("translate", ["foo %s", "bar"]);
    function __invoke() {
        return $this->trigger("translate", func_get_args());
    }

    // Shortcut to trigger():
    // $hooks->event("foo", "bar");
    // $hooks->trigger("event", ["foo", "bar"]);
    // Do not use if $args has references (&), use trigger():
    // $hooks->event(&$wrong);
    // $hooks->trigger("event", [&$correct]);
    function __call($event, $args) {
        return $this->trigger($event, $args);
    }

    function context() {
        return $this->context;
    }

    function template($name, ...$args) {
        ob_start();
        $this->trigger("echo_$name", $args);
        return ob_get_clean();
    }

    // Invokes callbacks for $event in order: first, normal, last.
    // If any hook returns a non-null value other hooks in all groups are skipped.
    // The order in which hooks in the same group are called is unspecified.
    // After those 3 groups, after hooks are called, with $args + the return
    // value as the first argument; return value of after hooks are ignored and
    // all after hooks are always called.
    // The combination of first + after hooks can be used to implement caching.
    function trigger($event, array $args = []) {
        if (preg_match('/^echo_([\w.-]+)$/', $event, $match)) {
            if (is_file($file = "templates/$match[1].php")) {
                isolatedRequire($file, ["context" => $this->context, "hooks" => $this]);
            }
            // echo_ events are expected to have first argument as array &$vars
            // but for simpler invocation it can be omitted or non-reference:
            // $hooks->trigger("echo_foo");
            // $hooks->trigger("echo_foo", [$vars]);
            // $hooks->echo_foo(["immediate" => "not reference"]);
            // Below we make fix first argument, at the same time not breaking
            // the reference if $args has it:
            // $hooks->trigger("echo_foo", [&$vars]);
            // Above, a hook may have changed $vars.
            $vars = &$args[0] or $vars = [];
            $args[0] = &$vars;
        }
        try {
            array_unshift($this->frames, $this->frame = new \stdClass);
            $result = null;
            foreach ($this[$event] as $func) {
                $result = $this->invoke($func, $args);
                if ($result !== null) { break; }
            }
            array_unshift($args, $result);
            foreach ($this->after[$event] ?? [] as $func) {
                $result = $this->invoke($func, $args);
            }
        } finally {
            array_shift($this->frames);
            $this->frame = $this->frames[0] ?? null;
        }
        return $result;
    }

    protected function invoke($func, array $args) {
        if ($this->context) {
            // ->call() does not preserve references in $args, as does
            // $func(...$args). Only call_user_func_array() is usable.
            // fromCallable() returns $func if $func is already a Closure so it's fast.
            $func = \Closure::fromCallable($func)->bindTo($this->context);
        }
        return call_user_func_array($func, $args);
    }

    protected function addTo(&$callbacks, callable $callback) {
        if (!$callbacks) { $callbacks = []; }
        $callbacks[] = $callback;
        return $this;
    }

    function register($event, callable $func) {
        return $this->addTo($this->normal[$event], $func);
    }

    function registerFirst($event, callable $func) {
        return $this->addTo($this->first[$event], $func);
    }

    function registerLast($event, callable $func) {
        return $this->addTo($this->last[$event], $func);
    }

    function registerAfter($event, callable $func) {
        return $this->addTo($this->after[$event], $func);
    }

    // Checks if there are any hooks registered for an event in any priority
    // except after.
    function offsetExists($offset) {
        return !empty($this->first[$offset]) ||
               !empty($this->normal[$offset]) ||
               !empty($this->last[$offset]);
    }

    // Gets array of all callbacks in order of their priorities. After hooks
    // are not returned.
    function offsetGet($offset) {
        return array_merge(
            $this->first[$offset] ?? [],
            $this->normal[$offset] ?? [],
            $this->last[$offset] ?? []);
    }

    // Adds a new normal hook for an event.
    function offsetSet($offset, $value) {
        $this->register($offset, $value);
    }

    // Unregisters all hooks for an event excluding after.
    // unset and get can be used to wrap or disable existing hooks:
    // $old = $hooks["event"];
    // unset($hooks["event"]);
    // $hooks->register("wrapper", function () {
    //     foreach ($old as $callback) ...
    // };
    // Restoration is currently not possible because get removes priority information.
    function offsetUnset($offset) {
        unset($this->first[$offset]);
        unset($this->normal[$offset]);
        unset($this->last[$offset]);
    }
}

class Context {
    // Properties below are always set to some value.
    public $hooks;
    public $config = [];
    // Object for storing custom data similarly to Kanbani's custom field.
    // By convention, keys are "namespace_name", for example, "myplugin_fooBar" for
    // a key used in plugins/myplugin.php.
    public $custom;
    public $tz = "UTC";     // identifier for date_default_timezone_set()
    // PHP locale identifier for setlocale(). "C" is universally valid, en_US.UTF-8 is not:
    // https://docs.microsoft.com/en-us/previous-versions/visualstudio/visual-studio-2008/39cwe7zf(v=vs.90)
    public $locale = "C";
    // Used in <html lang=> and in Accept-Language. Called the "language tag":
    // https://www.ietf.org/rfc/bcp/bcp47.txt
    public $language = "en";
    public $request;        // array, $_REQUEST
    public $server;         // array, $_SERVER
    public $files = [];     // array, $_FILES omitting failed uploads

    // Properties below may be null depending on the type of request.
    public $syncFile;
    public $syncData;
    public $currentBoard;   // object, one of $syncData->boards
    public $profileID;      // string, missing for one-time profiles (imported)
    // QrCodeData, missing if profile exists in KWV only and can't be joined for
    // collaboration using Kanbani. If this is set then $profileID must be also set.
    public $kanbaniQrCode;

    // $lists is array, $cards is SplObjectStorage (key is a list object).
    // Of both properties, members are arrays with keys "list" or "card" (an
    // object inside $currentBoard), "visible" (false if was filtered). Plugins can
    // add other keys. Members are properly sorted.
    // These fields omit deleted cards/lists and reflect user's preferences while
    // $syncData contains profile data verbatim (unfiltered, unsorted, possibly deleted).
    public $lists;
    public $cards;

    function __construct() {
        $this->hooks = new Hooks($this);
        $this->custom = new \stdClass;
        $this->request = $_REQUEST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        static::cleanFiles($this->files);
    }

    static function cleanFiles(array &$files) {
        foreach ($files as $key => &$value) {
            if (!isset($value["error"])) {
                static::cleanFiles($value);    // <input name="file[]">
            } elseif ($value["error"] || !is_uploaded_file($value["tmp_name"])) {
                $error = $value["error"] ?: \UPLOAD_ERR_NO_FILE;
                foreach (get_defined_constants() as $name => $value) {
                    if ($error === $value && !strncmp($name, "UPLOAD_ERR_", 11)) {
                        $error = ucwords(strtolower(strtr(substr($name, 11), "_", " ")));
                        break;
                    }
                }
                throw new PublicException("Problem uploading a file ($key): $error.");
            }
        }
    }

    // Shortcut to the translate event:
    // $context("foo %s", "bar");
    // $context->hooks->trigger("translate", ["foo %s", "bar"]);
    function __invoke(...$args) {
        return $this->hooks->__invoke(...$args);
    }

    function cookie($name, $value = null) {
        if (func_num_args() === 1) {
            return $_COOKIE[$name] ?? null;
        } else {
            setcookie($name, $value, $value === null ? 1 : 0, "", "", $this->server["HTTPS"] ?? false, true);
            return $this;
        }
    }

    function unserialize() {
        if (!$this->syncData) {
            $this->hooks->trigger("unserialize");
        }
        return $this;
    }

    function syncData(SyncData $data = null, SyncFile $file = null) {
        $this->syncData = $data ?: new SyncData;
        $this->syncFile = $file ?: new SyncFile;
        $this->currentBoard = $data->boards[0] ?? null;
        return $this;
    }

    function currentBoard(\stdClass $board) {
        if (!in_array($board, $this->syncData->boards, true)) {
            throw new \InvalidArgumentException("Board is not part of current \$syncData.");
        }
        $this->currentBoard = $board;
        return $this;
    }

    function persistentReadOnly($profileID) {
        $this->profileID = $profileID;
        $this->kanbaniQrCode = null;
        return $this;
    }

    function persistent($profileID, QrCodeData $qrCode) {
        $this->profileID = $profileID;
        $this->kanbaniQrCode = $qrCode;
        return $this;
    }
}

function initializeGlobal() {
    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }, -1);
    error_reporting(-1);
    chdir(__DIR__);
    // These are required because of unserialize.qrCode in the config.
    // https://github.com/PDApps/KanbaniDataPHP
    require_once "kanbani-data/sync.php";
    require_once "kanbani-data/qrcode.php";
    $context = initialize();
    date_default_timezone_set($context->tz);
    if (!setlocale(LC_ALL, $context->locale)) {
        error_log("KWV warning: setlocale($context->locale) has failed. Ensure your /etc/locale.gen has this locale. If it does not then change that file, run locale-gen and restart php-fpm or Apache.");
    }
    // Our strings are UTF-8 but on Windows setlocale() can't use this encoding,
    // which results in wrong sort order:
    // setlocale(LC_ALL, "C");
    // echo strcoll("а", "б");      -1 (correct)
    // setlocale(LC_ALL, "russian");
    // echo strcoll("а", "б");      +1
    if (!strncasecmp(PHP_OS, "win", 3)) {
        setlocale(LC_COLLATE, "C");
    }
    return $context;
}

function initialize() {
    $context = new Context;
    if (is_file("config.php")) {
        $context->config += isolatedRequire("config.php", compact("context"));
    }
    $context->config += isolatedRequire("config-defaults.php");
    foreach ($context->config["plugins"] as $file) {
        isolatedRequire($file, compact("context"));
    }
    $context->hooks->start();
    return $context;
}

function isolatedRequire($file, array $vars = []) {
    extract($vars);
    return require_once $file;
}

// Remember: file_get_contents() does not respect LOCK_EX of file_put_contents().
function getFileContentsWithLock($path) {
    $handle = fopen($path, "rb");
    try {
        flock($handle, LOCK_SH);
        return stream_get_contents($handle);
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function htmlOptions(array $values, array $titles, $current = null) {
    return join(array_map(function ($value, $title) use ($current) {
        return '<option value="'.htmlspecialchars($value).'"'.
               ($value === $current ? " selected" : "").
               ">".htmlspecialchars($title)."</option>";
    }, $values, $titles));
}

function htmlAttributes(array $attrs) {
    $result = "";
    foreach ($attrs as $attr => $value) {
        $result .= " ".htmlspecialchars($attr).'="'.htmlspecialchars($value).'"';
    }
    return $result;
}

function timeInterval($translator, $to, $now = null) {
    if ($now === null) { $now = time(); }
    $past = $to < $now;
    $ago = $past ? "%s%s ago" : "in %s%s";
    $diff = abs($to - $now);
    if       ($diff > $p = 3600 * 24 * 360) {
        return $translator($ago, round($diff / $p), $translator("y"));
    } elseif ($diff > $p = 3600 * 24 * 28) {
        return $translator($ago, round($diff / $p), $translator("mo"));
    } elseif ($diff > $p = 3600 * 24 * 7) {
        return $translator($ago, round($diff / $p), $translator("w"));
    } elseif ($diff > $p = 3600 * 24) {
        return $translator($ago, round($diff / $p), $translator("d"));
    } elseif ($diff > $p = 3600) {
        return $translator($ago, round($diff / $p), $translator("h"));
    } elseif ($diff > $p = 60) {
        return $translator($ago, round($diff / $p), $translator("min"));
    } elseif ($diff > 5) {
        return $translator($ago, $diff, $translator("s"));
    } else {
        return $translator("just now");
    }
}

function formatTime($translator, $time) {
    // Omit the date for brevity if $time is today.
    $format = date("ymd", $time) === date("ymd") ? "%X" : "%x %X";
    return $translator("%s (%s)", strftime($format, $time), timeInterval($translator, $time));
}

function formatNumber($number, $decimals = 0) {
    $locale = localeconv();
    return number_format($number, $decimals, $locale["decimal_point"], $locale["thousands_sep"]);
}
