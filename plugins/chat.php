<?php
/* This plugin adds an info button with the number of viewers of the current board
   and creates a common room where they can exchange text messages. It also provides
   a notification when the sync profile is updated on the server. */

$cache = $context->config["cache"];
if (!$cache) { return; }

$context->hooks->registerAfter("serve_chat", function () use ($cache) {
    $room = $this->request["room"];
    $file = "$cache/chat.$room";
    if (!preg_match('/^[\w\-]+$/', $room)) {
        throw new Exception("Invalid room: $room.");
    }

    if (isset($this->request["message"])) {
        $msg = preg_replace('/^\s+|\s+$|\v/u', "", $this->request["message"]);
        if (strlen($msg)) { $msg .= "\n"; }
        return file_put_contents($file, $msg, FILE_APPEND | LOCK_EX);
    }

    $me = Kanbani\QrCodeData::randomIdentifier();

    register_shutdown_function(function () use (&$file, $me) {
        try {
            unlink("$file.$me");
        } catch (\Throwable $e) {}
    });

    touch($file);
    $file = realpath($file);    // can't rely on CWD inside the shutdown function.
    touch("$file.$me");

    // Chat feed can also check for and notify about profile updates when somebody
    // syncs to the server. It's optional functionality so ignore errors if unserialize() fails.
    if (!empty($this->request["profile"])) {
        try {
            $this->unserialize();
        } catch (\Throwable $e) {}
    }

    set_time_limit($age = 3600);
    header("Content-Type: text/event-stream");
    header("X-Accel-Buffering: no");
    $online = $size = $updated = null;

    for ($cycle = 0; ; ++$cycle) {
        clearstatcache();    // affects filesize(), filemtime().
        if ($size !== filesize($file)) {
            // file_get_contents() doesn't respect file locks (LOCK_EX above).
            flock($handle = fopen($file, "rb"), LOCK_SH);
            // If $file was truncated, fseek() can seek past EOF (but feof() is still false!)
            // and stream_get_contents() will read nothing. ftell() will return the same
            // value given to fseek() (past EOF). So in case of truncation, we rewind,
            // and unless we rewound to the beginning we skip first line (that may be truncated).
            if ($size === null || $size > filesize($file)) {
                fseek($handle, max(0, filesize($file) - 10000));
                if (ftell($handle)) {
                    fgets($handle);
                }
            } else {
                fseek($handle, $size);
            }
            if (!$size) {
                echo "event: history\r\n";
            }
            foreach (preg_split("/\n/u", stream_get_contents($handle)) as $line) {
                if (strlen($line)) {
                    echo "data: ", $this->hooks->format($line), "\r\n";
                }
            }
            echo "\r\n";
            flock($handle, LOCK_UN);
            $size = ftell($handle);
            fclose($handle);
        }
        if ($cycle % 10 === 0) {  // every second.
            $current = count(array_filter(array_map("filemtime", glob("$file.*")),
                function ($t) use ($age) { return $t > time() - $age - 100; }));
            if ($online !== $current) {     // includes $me.
                $online = $current;
                echo "event: online\r\n";
                echo "data: $online\r\n";
                echo "\r\n";
            }
        }
        if ($cycle % 50 === 0) {   // every 5 seconds.
            // Detects disconnected clients (can't do this without trying to echo).
            echo ":\r\n\r\n";
        }
        if ($cycle % 100 === 0 && !$updated && $updated = $this->hooks->updated()) {   // every 10 seconds.
            echo "event: update\r\n";
            echo "data: 1\r\n";     // must have data: else event is not triggered.
            echo "\r\n";
        }
        // output_buffering in php.ini is on by default.
        if (ob_get_level()) { ob_flush(); }
        flush();
        usleep(100000);
    }
});

$context->hooks->register("echo_board", function () {
    $this->custom->chat_target = "chat".mt_rand();
?>
<iframe id="i<?=$this->custom->chat_target?>" name="i<?=$this->custom->chat_target?>" class="filtered"></iframe>
<form method="post"
      target="i<?=$this->custom->chat_target?>"
      id="f<?=$this->custom->chat_target?>"
      action="?do=chat&room=<?=htmlspecialchars(rawurlencode($this->profileID ?: $this->currentBoard->id))?>&profile=<?=htmlspecialchars(rawurlencode($this->profileID))?>"></form>
<?php
});

$context->hooks->register("echo_boardHeader", function () {
    if ($this->profileID) {
?>
<span class="info-hint info-hint_relative chat filtered">
    <abbr class="isle info-hint__title chat__title"></abbr>
    <div class="info-hint__hint horiz-bar__child">
        <p class="chat__update">
            <?=$this(
                "This profile was updated. %sReload this page%s to see the new version.",
                '<a href="">', '</a>'
            )?>
        </p>
        <p class="chat__help"><?=$this("Chat with people viewing this profile or leave notes for later:")?></p>
        <div class="chat__msgs"></div>
        <p class="chat__empty"><?=$this("It’s so quiet here…")?></p>
        <textarea form="f<?=$this->custom->chat_target?>"
                  name="message" class="chat__text block-area" rows="1"
                  disabled placeholder="<?=$this("Type your message…")?>"></textarea>
    </div>
</span>
<?php
    }
});
