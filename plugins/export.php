<?php
/* This plugin allows exporting boards and cards in basic formats like CSV. */

$sendBoard = function ($context, $format, $mime, $ext, $func) {
    if ($context->request["format"] === $format) {
        $context->unserialize();
        $data = $func();
        header("Content-Type: $mime");
        header("Content-Disposition: attachment; filename=\"".preg_replace('/["\\\\]/u', "", $context->currentBoard->title ?: $context("Board")).".$ext\"");
        ob_start("ob_gzhandler");
        // Delaying output allows seeing exceptions during $func().
        echo $data;
        return true;
    }
};
$context->hooks->register("serve_export", function () use ($sendBoard) {
    return $sendBoard($this, "csv", "text/csv; charset=utf-8", "csv", function () {
        $this->syncData->boards = [$this->currentBoard];
        return $this->syncData->serializeToCSV();
    });
});
$context->hooks->register("serve_export", function () use ($sendBoard) {
    return $sendBoard($this, "xcsv", "text/csv; charset=utf-8", "csv", function () {
        $this->syncData->boards = [$this->currentBoard];
        return $this->syncData->serializeToExcelCSV();
    });
});
$context->hooks->register("serve_export", function () use ($sendBoard) {
    return $sendBoard($this, "json", "application/json; charset=utf-8", "json", function () {
        $this->syncData->boards = [$this->currentBoard];
        return $this->syncData->serializeToJSON(JSON_PRETTY_PRINT);
    });
});
$context->hooks->register("serve_export", function () use ($sendBoard) {
    return $sendBoard($this, "kanbani", "application/octet-stream", "kanbani", function () {
        if (empty($this->request['exportAll'])) {
            $this->syncData->boards = [$this->currentBoard];
        }
        $this->syncFile->data = $this->syncData->serializeToJSON();
        $this->syncFile->secret = $this->request["exportSecret"] ?? "";
        $this->syncFile->version = $this->syncFile->secret
            ? $this->syncFile::ENCRYPTED : $this->syncFile::PLAIN;
        if ($this->syncFile->secret) {
            $this->syncFile->boardID = $this->currentBoard->id;
        }
        return $this->syncFile->serialize();
    });
});

$sendCard = function ($context, $format, $mime, $ext, $func) {
    if ($context->request["format"] === $format) {
        $context->unserialize();
        $card = $context->syncData->findCard($context->request["card"]);
        header("Content-Type: $mime");
        header("Content-Disposition: attachment; filename=\"".preg_replace('/["\\\\]/u', "", $card->title ?: $context("Card")).".$ext\"");
        echo $func($card);
        return true;
    }
};
$context->hooks->register("serve_export", function () use ($sendCard) {
    return $sendCard($this, "ctxt", "text/plain; charset=utf-8", "txt", function ($card) {
        $lines = [
            $card->title,
            $card->related_name,
        ];
        // Not using strftime() due to its localization and because strtotime()
        // cannot convert localized dates (in case this exported TXT is fed
        // back to the TXT import).
        if ($card->due_time) {
            $lines[] = date("Y-m-d H:i T", $card->due_time / 1000);
        }
        $lines[] = "";
        if (strlen($card->description)) {
            $lines[] = preg_replace("/\r\n|\r/u", "\n", $card->description);
        }
        return join("\n", $lines);
    });
});
$context->hooks->register("serve_export", function () use ($sendCard) {
    return $sendCard($this, "cjson", "application/json; charset=utf-8", "json", function ($card) {
        return json_encode($card, JSON_PRETTY_PRINT | Kanbani\JSON_FLAGS);
    });
});
$context->hooks->register("serve_export", function () use ($sendCard) {
    return $sendCard($this, "cvcs", "text/calendar; charset=utf-8", "vcs", function ($card) {
        $format = 'Ymd\\THis\\Z';
        return join("\n", array_filter([
            "BEGIN:VCALENDAR",
            "VERSION:2.0",
            "BEGIN:VEVENT",
            "DTSTAMP:".gmdate($format, $card->create_time / 1000),
            "SUMMARY:".$card->title,
            "DESCRIPTION:".preg_replace('/\v/u', '\n', $card->description),
            $card->due_time ? "DTSTART:".gmdate($format, $card->due_time / 1000) : "",
            $card->due_time ? "DTEND:".gmdate($format, $card->due_time / 1000 + 3600) : "",
            "UID:".$card->id,
            "END:VEVENT",
            "END:VCALENDAR",
        ]));
    });
});

$context->hooks->register("echo_cardExport", function (array $vars) {
    // $card
    extract($vars);
    $query = "?".http_build_query([
        "do"     => "export",
        "card"   => $card->id,
        "board"  => $this->currentBoard->id,
        "profile"=> $this->profileID,
        "format" => "",
    ]);
?>
    <span class="card-export__item"><a href="<?=htmlspecialchars($query)?>ctxt"><?=$this("Text")?></a></span>
    <span class="card-export__item"><a href="<?=htmlspecialchars($query)?>cjson">JSON</a></span>
    <span class="card-export__item"><a href="<?=htmlspecialchars($query)?>cvcs">VCS/ICS</a></span>
<?php
});

$context->hooks->registerFirst("echo_boardExport", function (array $vars) {
    // $form
    extract($vars);
    $attrs = 'type="submit" name="do" value=';
    $form and $attrs = 'form="'.htmlspecialchars($form).'" '.$attrs;
?>
    <button <?=$attrs?>"export&format=xcsv">Excel</button>
    <button <?=$attrs?>"export&format=csv">CSV</button>
    <button <?=$attrs?>"export&format=json">JSON</button>
<?php
});

$context->hooks->register("echo_boardExport", function (array $vars) {
    // $form
    extract($vars);
    $form = $form ? 'form="'.htmlspecialchars($form).'"' : "";
    $attrs = $form.' type="submit" name="do" value=';
?>
    </p>
    <p>
        <?=$this(
            "Export as a Kanbani sync file. Leave %sSecret%s empty if you do not want to encrypt it.",
            "<b>", "</b>"
        )?>
    </p>
    <p>
        <input <?=$form?> name="exportSecret" placeholder="<?=$this("Secret")?>">
        <button <?=$attrs?>"export&format=kanbani"><?=$this("Export this board")?></button>
        <?php if (!$this->syncData->isSingleBoard()) {?>
            <button <?=$attrs?>"export&format=kanbani&exportAll=1"><?=$this("Export all boards")?></button>
        <?php }?>
<?php
});
