<?php
/* This plugin simplifies bootstrapping new KWV installation. */
namespace Kanbani;

$isInstalled = is_file("config.php");

if ($isInstalled) {
    $context->hooks->register("serve_install", function () {
        switch ($this->request["subdo"] ?? "") {
            case "qr":
                $stubBoard = (object) [
                    "title" => $this("Your server at %s", $this->server["HTTP_HOST"] ?? $this->server["SERVER_ADDR"]),
                    "id" => "0",
                ];
                $this
                    ->syncData(new SyncData([$stubBoard]))
                    ->persistent(QrCodeData::randomIdentifier(), $this->config['baseQrCode'])
                    ->hooks->serve_qrImageProfile();
                return true;
            default:
                $this->hooks->echo_empty([
                    "bodyAttributes" => ["class" => "middle body_full"],
                    "body" => $this->hooks->template("installed"),
                ]);
                return true;
        }
    });

    $context->hooks->register("echo_installed", function () {
?>
<article class="middle__out">
    <div class="middle__in middle__in_pad">
        <p><b><?=$this("Kanbani Web Viewer has been successfully configured and is ready for use!")?></b></p>
        <?php if ($this->config["baseQrCode"]) {?>
            <p>
                <?=$this(
                    "Start by syncing a profile from the %sKanbani app%s on your device, then following the link to the “online viewer” from that profile’s %sShare%s screen.",
                    '<a href="https://pdapps.org/kanbani">', '</a>',
                    '<b>', '</b>'
                )?>
            </p>
            <p>
                <?=$this(
                    "A quick way to set up such a profile is by scanning the QR code below using Kanbani’s %sScan%s command from the main menu. You can also %sdownload this image%s and send it to somebody else.",
                    '<b>', '</b>',
                    '<a href="?do=install&subdo=qr&dl=1">', "</a>"
                )?>
            </p>
            <img src="?do=install&subdo=qr&dl=1">
        <?php }?>
        <?php if (in_array("plugins/welcome.php", $this->config["plugins"])) {?>
            <p>
                <?=$this(
                    "If you wish, you can browse the included “%sWelcome Board%s”.",
                    '<a href="?profile=Welcome">', '</a>'
                )?>
            </p>
        <?php }?>
        <p><?=$this("Happy tasking!")?></p>
        <p>
            <?=$this(
                "P.S. %sGet in touch%s in case of trouble.",
                '<a href="https://pdapps.org/kanbani/forum">', '</a>'
            )?>
        </p>
    </div>
</article>
<?php
    });

    return;
}

// Below is for when not installed yet.

$context->hooks->registerFirst("start", function () {
    if (!preg_match('/^install(&|$)/', $this->request["do"] ?? "")) {
        header("Location: ?do=install");
        exit;
    }
    // Default config sets cache to cache/ only if it is writable. It may be not
    // writable now, but the installer instructs the user to use this directory and
    // doesn't try writing to it, so this saves from writing checks "use $cache or cache/".
    $this->config["cache"] = $this->config["cache"] ?? "cache";
});

$context->hooks->register("serve_install", function () {
    $authKey = $this->cookie("kwvinst");
    if (!$authKey) {
        $authKey = QrCodeData::randomIdentifier(64);
        $this->cookie("kwvinst", $authKey);
    }

    $isAuthorized = function () use ($authKey) {
        try {
            return trim(file_get_contents($this->config["cache"]."/admin.txt")) === $authKey;
        } catch (\Throwable $e) {}
    };

    if ($isAuthorized()) {
        ini_set("display_errors", 1);
    }

    $do = $this->request["subdo"] ?? "";
    switch ($do) {
        case "authdl":
            header("Content-Disposition: attachment; filename=admin.txt");
            echo $authKey;
            return true;
        case "authes":
        case "cfges":
            header("Content-Type: text/event-stream");
            while ($do === "authes" ? !$isAuthorized() : !is_file("config.php")) {
                usleep(250000);
            }
            echo "data: 1\r\n\r\n";
            return true;
        case "":
            if (!$isAuthorized()) {
                http_response_code(401);
                $this->hooks->echo_empty([
                    "bodyAttributes" => ["class" => "middle body_full"],
                    "body" => $this->hooks->template("install"),
                ]);
            } else {
                $this->hooks->echo_empty([
                    "bodyAttributes" => ["class" => "page"],
                    "body" => $this->hooks->template("installing", ["request" => $this->request["i"] ?? []]),
                ]);
            }
            return true;
        case "cfgcheck":
        case "cfgdl":
        case "cfgwrite":
            if ($isAuthorized()) {
                $config = (object) [
                    "request" => $this->request["i"],
                    "prior" => [],
                    "config" => [],
                    "warn" => [],
                ];
                $this->hooks->buildConfig($config);
                $code = join("\n", [
                    "<?php",
                    "// Generated by ".basename(__FILE__)." | ".
                    gmdate("Y-m-d H:i:s \\Z")." | \$SHA1\$",
                    join("\n", $config->prior),
                    "return [",
                    join("\n", $config->config),
                    "];",
                ]);
                // This hash is not used yet but it can tell if the config was
                // edited after being generated or not, for writing the post-install
                // configuration utility. Line breaks are removed to deal with various kinds of EOL.
                $code = preg_replace('/\$SHA1\$/', hash("sha1", preg_replace('/\v/u', "", $code)), $code, 1);
                switch ($do) {
                case "cfgcheck":
                    $this->hooks->echo_empty([
                        "bodyAttributes" => ["class" => "page"],
                        "body" => $this->hooks->template("installing", [
                            "request" => $this->request["i"],
                            "configCode" => $code,
                            "configWarn" => $config->warn,
                        ]),
                    ]);
                    return true;
                case "cfgdl":
                    header("Content-Disposition: attachment; filename=config.php");
                    echo $code;
                    return true;
                case "cfgwrite":
                    file_put_contents("config.php", $code);
                }
            }
            header("Location: ?do=install");
            return true;
        case "cfgwrite":
            if ($isAuthorized()) {
            }
            header("Location: ?do=install");
            return true;
    }
});

$context->hooks->register("echo_install", function () {
?>
<article class="middle__out" data-kwv-inst="install">
    <div class="middle__in middle__in_pad">
        <h1><?=$this("Welcome to Kanbani Web Viewer!")?></h1>
        <p><?=$this("This Viewer has not been properly set up yet.")?></p>
        <p><?=$this("If you are the administrator of this server, you need to authorize yourself:")?></p>
        <ol class="middle__list">
            <?php if (!is_dir($this->config["cache"])) {?>
                <li>
                    <?=$this(
                        "Create the directory named %s",
                        "<b>".$this->config["cache"]."</b>"
                    )?>
                </li>
            <?php }?>
            <li>
                <?=$this(
                    "Upload %sthis %s file%s to the %s directory (replace if exists)",
                    '<a href="?do=install&subdo=authdl">',
                    '<b>admin.txt</b>',
                    '</a>',
                    "<b>".$this->config["cache"]."</b>"
                )?>
            </li>
            <li><?=$this("Reload this page")?></li>
        </ol>
        <noscript><p><b><?=$this("You need to have cookies enabled.")?></b></p></noscript>
    </div>
</article>
<?php
});

$context->hooks->register("echo_installing", function (array $vars) {
    // $request
    // $configCode
    // $configWarn
    extract($vars);
    extract($request, EXTR_PREFIX_ALL, "r");

    $transports = ["SFTP", "WebDAV", "FTP"];
    $zones = timezone_identifiers_list();

    $plugins = array_map(function ($file) use (&$r_plugins) {
        $name = basename($file);
        $description = "";
        foreach (token_get_all(file_get_contents($file)) as $token) {
            if (is_array($token) && $token[0] === T_COMMENT &&
                    !strncmp($token[1], "/*", 2)) {
                $description = trim(substr($token[1], 2, -2));
                break;
            }
        }
        $system = stripos($description, "!disable") !== false;
        $checked = isset($r_plugins[$file])
            ? !empty($r_plugins[$file])
            : in_array($file, $this->config["plugins"]);
        return compact("file", "name", "description", "system", "checked");
    }, glob("plugins/*.php"));

    usort($plugins, function ($a, $b) {
        return $a["name"] <=> $b["name"];
    });
?>

<form action="?" method="post" class="page__in" data-kwv-inst="installing">
    <!-- Messages below may produce more submit buttons but we still want submission
         by Enter in some input field trigger cfgcheck, not cfgwrite or something else. -->
    <input class="filtered" type="submit" name="do" value="install&subdo=cfgcheck">

    <h1 class="page__h1"><?=$this("Configure your Viewer")?></h1>

    <?php if (!empty($configWarn)) {?>
        <div class="inst__warn">
            <p>
                <?=$this(
                    "Potential problems were found in your configuration. You can ignore them and finish or you can make adjustments and %scheck again%s.",
                    '<button type="submit" name="do" value="install&subdo=cfgcheck">',
                    '</button>'
                )?>
            </p>
            <ul>
                <?php foreach ($configWarn as $msg) {?>
                    <li><?=htmlspecialchars($msg)?></li>
                <?php }?>
            </ul>
        </div>
    <?php }?>

    <?php if (!empty($configCode)) {?>
        <div class="inst__code">
            <h2><?=$this("All set! Now finish the installation")?></h2>
            <ol>
                <li>
                    <?php if (is_writable(".")) {?>
                        <?=$this(
                            "%sClick here%s to save the configuration",
                            '<button type="submit" name="do" value="install&subdo=cfgwrite">',
                            '</button>'
                        )?>
                    <?php } else {?>
                        <?=$this(
                            "Upload %sthis %s file%s to the Viewer's directory (near %s)",
                            '<button type="submit" name="do" value="install&subdo=cfgdl">',
                            'config.php',
                            '</button>',
                            "<b>index.php</b>"
                        )?>
                    <?php }?>
                </li>
                <li><?=$this("Reload this page")?></li>
            </ol>
        </div>
    <?php }?>

    <h2><?=$this("Kanbani app integration")?></h2>
    <p><?=$this("These settings control generation of QR codes for importing sync profiles into Kanbani.")?></p>
    <p><?=$this("If you are going to access your boards only from a web browser without using the app, leave these unconfigured.")?></p>
    <h3><?=$this("Transport")?></h3>
    <p><?=$this("Select how Kanbani transports data to your server:")?></p>
    <p>
        <select name="i[qrTransport]" id="qrTransport">
            <?=htmlOptions(
                array_merge([""], $transports),
                array_merge(["Disable Kanbani integration"], $transports),
                $r_qrTransport ?? ""
            )?>
        </select>
    </p>
    <div class="inst__transp-cfg">
        <p>
            <?=$this("Sync endpoint URL of this server:")?>

            <input name="i[qrURL]" placeholder="<?=$this("Base URL")?>" class="block-area light-shade"
                   value="<?=htmlspecialchars($r_qrURL ?? "")?>">
        </p>
        <h3><?=$this("Sync directory")?></h3>
        <p>
            <?=$this("Specify absolute path to the directory where Kanbani stores transported data:")?>
            <input name="i[unserPath]" placeholder="/home/kanbani/ftp" class="block-area light-shade"
                   value="<?=htmlspecialchars($r_unserPath ?? "")?>">
        </p>
        <h3><?=$this("Authentication")?></h3>
        <p><?=$this("Your server may require Kanbani to supply valid credentials on sync:")?></p>
        <p>
            <label>
                <input type="radio" name="i[qrAuth]" id="qrAuthNo" value="" <?=empty($r_qrAuth) ? "checked" : ""?>>
                <?=$this("No authentication")?>
            </label>
        </p>
        <p>
            <label>
                <input type="radio" name="i[qrAuth]" id="qrAuthPassword" value="pass" <?=empty($r_qrAuth) ? "" : "checked"?>>
                <?=$this("Username and password")?>
            </label>
            <span class="inst__auth-cfg">
                <input name="i[qrUser]" placeholder="<?=$this("Username")?>" class="block-area light-shade"
                       value="<?=htmlspecialchars($r_qrUser ?? "")?>">
                <input name="i[qrPass]" placeholder="<?=$this("Password")?>" class="block-area light-shade"
                       value="<?=htmlspecialchars($r_qrPass ?? "")?>">
            </span>
        </p>
    </div>

    <h2><?=$this("Viewer plugins")?></h2>
    <p><?=$this("This Viewer comes with a variety of plugins that allow import boards from CSV, filter cards, customize the board’s look and so on.")?></p>
    <p><?=$this("Uncheck a checkbox to disable a plugin, or leave them all checked to enable (recommended, doesn’t hurt).")?></p>
    <?php foreach ($plugins as $plugin) {?>
        <?php if ($plugin["system"]) { continue; }?>
        <p>
            <label>
                <input type="hidden" value=""
                       name="i[plugins][<?=htmlspecialchars($plugin["file"])?>]">
                <input type="checkbox" value="1"
                       name="i[plugins][<?=htmlspecialchars($plugin["file"])?>]"
                       <?=$plugin["checked"] ? "checked" : ""?>>
                <b><?=htmlspecialchars(basename($plugin["name"]))?></b>
            </label>
        </p>
        <p><?=htmlspecialchars($this($plugin["description"]))?></p>
    <?php }?>

    <h2><?=$this("Other settings")?></h2>
    <p><?=$this("By default, visitors will see time in the timezone of their native language (and London's time for all English visitors).")?></p>
    <p><?=$this("You can override this default timezone if most of your visitors are from a known location.")?></p>
    <p>
        <select name="i[tz]">
            <?=htmlOptions(
                array_merge([""], $zones),
                array_merge(["Use visitor's language timezone"], $zones),
                $r_tz ?? ""
            )?>
        </select>
    </p>

    <h2><?=$this("All done?")?></h2>
    <p><?=$this("Good! Now use the button below to check your configuration and save. One step away from getting this Viewer running!")?></p>
    <p>
        <button type="submit" name="do" value="install&subdo=cfgcheck">
            <?=$this("Check configuration")?>
        </button>
    </p>
</form>
<?php
});

$context->hooks->register("buildConfig", function (object $result) {
    extract($result->request, EXTR_PREFIX_ALL, "r");

    $cacheBad = !is_writable($this->config["cache"]);
    $otherBad = is_writable(".") || is_writable("index.php");
    if ($cacheBad || $otherBad) {
        $result->warn[] = $this("Make the %s directory writable for better performance (%s).", $this->config["cache"]."/", $this($cacheBad ? "fix this" : "good, you have this"));
        $result->warn[] = $this("Make all other files and directories read only for better security (%s).", $this($otherBad ? "fix this" : "good, you have this"));
    }

    if ($r_qrTransport) {
        $result->prior[] = '';
        $result->prior[] = '$baseQrCode = new Kanbani\\QrCodeData;';
        if ($r_qrAuth === "pass") {
            $result->prior[] =  '$auth = new Kanbani\\QrCodePassword('.
                                var_export($r_qrUser, true).
                                ', '.
                                var_export($r_qrPass, true).
                                ');';
        }
        $class = 'Kanbani\\QrCode'.$r_qrTransport;
        if (!class_exists($class)) {
            throw new PublicException("Transport class does not exist: $class.");
        }
        $result->prior[] = '$baseQrCode->transport = new '.$class.'('.
                  var_export($r_qrURL, true).
                  ($r_qrAuth ? ', $auth' : '').
                  ');';
        $result->config[] = '    "baseQrCode" => $baseQrCode,';
        $result->config[] = '    "unserialize.path" => '.var_export($r_unserPath, true).',';
        if (method_exists($class, "testConnection")) {
            $obj = new $class($r_qrURL);
            if ($r_qrAuth) {
                $obj->auth = new QrCodePassword($r_qrUser, $r_qrPass);
            }
            if ($errors = $obj->testConnection()) {
                $result->warn[] = $this("Connection test reported an error: %s.", $errors[1]);
            }
        }
        if (!is_dir($r_unserPath)) {
            $result->warn[] = $this("Sync directory does not exist: $r_unserPath.");
        }
        $result->prior[] = '';
    }

    $disabled = array_filter($r_plugins, function ($v) { return !$v; });
    if ($disabled) {
        $result->config[] = '    "plugins" => array_diff(glob("plugins/*.php"), [';
        foreach ($disabled as $file => $state) {
            $result->config[] = '        '.var_export($file, true).',';
        }
        $result->config[] = '    ]),';
    }

    if ($r_tz) {
        $result->config[] = '    "locale.defaultTZ" => '.var_export($r_tz, true).',';
    }

    $result->config[] = '    "secret" => '.var_export(base64_encode(QrCodeData::randomIdentifier(33)), true).',';
});
