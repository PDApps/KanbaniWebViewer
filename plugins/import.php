<?php
/* This plugin allows converting formats like CSV and Trello JSON to Kanbani boards. */
namespace Kanbani;
use stdClass;

$context->hooks->register("serve_import", function () {
    if ($this->request["format"] === "json") {
        $boards = json_decode(file_get_contents($this->files["upload"]["tmp_name"]));
        if (isset($boards->sync_version)) {
            SyncData::verifyUnserialized($boards);
            $boards = $boards->boards;
        }
        $this->syncData(new SyncData($boards))
            ->hooks->serve_viewBoard();
        return true;
    }
});

$context->hooks->register("serve_import", function () {
    if ($this->request["format"] === "kanbani") {
        $data = new SyncData;
        $data->unserializeFileUsing(
            $file = new SyncFile,
            $this->files["upload"]["tmp_name"],
            $this->request["secret"] ?? "",
            $this->request["boardID"] ?? "");
        $this->syncData($data, $file)
            ->hooks->serve_viewBoard();
        return true;
    }
});

$importCSV = function ($path, $separator) {
    $unpack = function (array $parts, array $keys) {
        return (object) array_combine($keys, array_slice($parts, 1 /*type*/, count($keys)));
    };
    $file = file_get_contents($path);
    if (!strncmp($file, SyncData::UTF8_BOM, strlen(SyncData::UTF8_BOM))) {
        $file = substr($file, 3);
    }
    $boards = $lists = $cards = [];
    // Remember: quoted strings may span multiple lines.
    $re = '/(?:"((?:[^"]|"")*)"|([^"]*?))(?:'.$separator.'|(\r?\n))/u';
    if (preg_match_all($re, $file, $matches)) {
        $start = 0;
        foreach (array_filter($matches[3]) as $i => $str) {
            $parts = [];
            for (; $start <= $i; ++$start) {
                if ($matches[1][$start] === "") {
                    $parts[] = $matches[2][$start];
                } else {
                    $parts[] = preg_replace('/""/u', '"', $matches[1][$start]);
                }
            }
            if (!$parts) {
                continue;
            } elseif ($parts[0] === "board") {
                $board = $unpack($parts, ["id", "create_time", "title", "custom"]);
                $board->field_history = new stdClass;
                $board->lists = [];
                $boards[$board->id] = $board;
            } elseif ($parts[0] === "list") {
                $list = $unpack($parts, ["id", "create_time", "title", "custom", "_parent"]);
                $list->field_history = new stdClass;
                $list->cards = [];
                $lists[$list->id] = $list;
            } elseif ($parts[0] === "card") {
                $card = $unpack($parts, ["id", "create_time", "title", "custom", "_parent", "create_user", "change_time", "related_name", "color", "description", "due_time", "archived"]);
                $card->field_history = new stdClass;
                $card->move_history = [];
                $cards[$card->id] = $card;
            }
        }
    }
    foreach ($cards as $card) {
        $lists[$card->_parent]->cards[] = $card;
        if (!$card->due_time) { $card->due_time = 0; }
        $card->custom = json_decode($card->custom);
        unset($card->_parent);
    }
    foreach ($lists as $list) {
        $boards[$list->_parent]->lists[] = $list;
        $list->custom = json_decode($list->custom);
        unset($list->_parent);
    }
    foreach ($boards as $board) {
        $board->custom = json_decode($board->custom);
    }
    return array_values($boards);
};
$context->hooks->register("serve_import", function () use ($importCSV) {
    if ($this->request["format"] === "xcsv") {
        $this->syncData(new SyncData($importCSV($this->files["upload"]["tmp_name"], ";")))
            ->hooks->serve_viewBoard();
        return true;
    }
});
$context->hooks->register("serve_import", function () use ($importCSV) {
    if ($this->request["format"] === "csv") {
        $this->syncData(new SyncData($importCSV($this->files["upload"]["tmp_name"], ",")))
            ->hooks->serve_viewBoard();
        return true;
    }
});

// List title
//                          optional blank line
// Card title
// Related name             optional
// D-U-E T-M                optional
//                          mandatory blank line
// Description              optional
// ...
//                          optional blank line
// ---...                   3 or more -
// Card title...
// <...as above>
// ===...                   3 or more =
// List title...
// <...as above>
$context->hooks->register("serve_import", function () {
    if ($this->request["format"] !== "txt") { return; }
    $lines = preg_split('/\n/u', file_get_contents($this->files["upload"]["tmp_name"]));
    $list = $card = null;
    $lists = [];
    foreach ($lines as $line) {
        $clean = preg_replace('/^\s*|\s*$/u', "", $line);
        if (!$list) {
            if ($clean === "") {
                continue;
            }
            $lists[] = $list = (object) [
                "id"            => QrCodeData::randomIdentifier(),
                "create_time"   => time() * 1000,
                "title"         => $clean,
                "field_history" => new stdClass,
                "custom"        => "",
                "cards"         => [],
            ];
        } elseif (!$card) {
            if ($clean === "") {
                continue;
            }
            $list->cards[] = $card = (object) [
                "id"            => QrCodeData::randomIdentifier(),
                "create_time"   => time() * 1000,
                "title"         => $clean,
                "field_history" => new stdClass,
                "custom"        => "",
                "related_name"  => null,
                "description"   => null,
                "color"         => 0,
                "due_time"      => null,
                "archived"      => false,
                "create_user"   => $this("Kanbani Web Viewer"),
                "change_time"   => time() * 1000,
                "move_history"  => [],
            ];
        } elseif ($card->related_name === null) {
            $card->related_name = $clean;
        } else {
            if ($card->due_time === null) {
                if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2} (\w+)$/u', $clean)) {
                    $card->due_time = strtotime($clean) * 1000;
                    continue;
                } else {
                    $card->due_time = 0;
                }
            }
            if (preg_match('/^-{3,}$/u', $clean)) {
                $card = null;
                continue;
            }
            if (preg_match('/^={3,}$/u', $clean)) {
                $list = $card = null;
                continue;
            }
            if ($card->description === null && $clean === "") {
                continue;
            }
            if ($card->description !== null) {
                $card->description .= "\n";
            }
            $card->description .= $clean;
        }
    }
    foreach ($lists as $list) {
        foreach ($list->cards as $card) {
            $card->description = preg_replace('/\s+$/u', "", $card->description);
        }
    }
    $board = (object) [
        "id"            => QrCodeData::randomIdentifier(),
        "create_time"   => time() * 1000,
        "title"         => basename($this->files["upload"]["name"],
        ".txt"),
        "field_history" => new stdClass,
        "custom"        => "",
        "lists"         => $lists,
    ];
    $this->syncData(new SyncData([$board]))
        ->hooks->serve_viewBoard();
    return true;
});

$context->hooks->register("serve_import", function () {
    if ($this->request["format"] === "tjson") {
        $data = json_decode(file_get_contents($this->files["upload"]["tmp_name"]));
        $cards = [];
        foreach ($data->cards as $card) {
            $resultCard = &$cards[$card->idList] or $resultCard = [];
            $resultCard[] = (object) [
                "id"            => $card->id,
                "create_time"   => time() * 1000,
                "title"         => $card->name,
                "field_history" => new stdClass,
                "custom"        => "",
                "related_name"  => $this("Kanbani Web Viewer"),
                "description"   => $card->desc,
                "color"         => 0,
                "due_time"      => strtotime($card->due) * 1000,
                "archived"      => false,
                "create_user"   => $this("Kanbani Web Viewer"),
                "change_time"   => strtotime($card->dateLastActivity) * 1000,
                "move_history"  => [],
            ];
        }
        $lister = function ($data) use ($cards) {
            return (object) [
                "id"            => $data->id,
                "create_time"   => time() * 1000,
                "title"         => $data->name,
                "field_history" => new stdClass,
                "custom"        => "",
                "cards"         => $cards[$data->id] ?? [],
            ];
        };
        $board = (object) [
            "id"                => $data->id,
            "create_time"       => time() * 1000,
            "title"             => $data->name,
            "field_history"     => new stdClass,
            "custom"            => "",
            "lists"             => array_map($lister, $data->lists),
        ];
        return $this->syncData(new SyncData([$board]))
            ->hooks->serve_viewBoard();
    }
});

$context->hooks->registerFirst("echo_boardImport", function (array $vars) {
    // $form
    extract($vars);
    $attrs = 'type="submit" name="format" value=';
    $form and $attrs = 'form="'.htmlspecialchars($form).'" '.$attrs;
?>
    <button <?=$attrs?>"xcsv">Excel</button>
    <button <?=$attrs?>"csv">CSV</button>
    <button <?=$attrs?>"json" title="<?=$this("Accepts full form (with sync_version) and short form (array of boards).")?>">JSON</button>
    <button <?=$attrs?>"txt" title="<?=$this("See Kanbani Web Viewer’s homepage for format details.")?>"><?=$this("Text")?></button>
    <button <?=$attrs?>"tjson"><?=$this("Trello JSON")?></button>
<?php
});

$context->hooks->register("echo_boardImport", function (array $vars) {
    // $form
    extract($vars);
    $form = $form ? 'form="'.htmlspecialchars($form).'"' : "";
?>
    </p>
    <p>
        <?=$this(
            "Import a Kanbani sync file. Leave %sSecret%s and %sBoard ID%s empty unless it’s encrypted.",
            "<b>", "</b>", "<b>", "</b>"
        )?>
    </p>
    <p>
        <input <?=$form?> class="import__group-input" name="secret" placeholder="<?=$this("Secret")?>">
        <input <?=$form?> class="import__group-input" name="boardID" placeholder="<?=$this("Board ID")?>">
        <button <?=$form?> type="submit" name="format" value="kanbani">
            <?=$this("Import .kanbani")?>
        </button>
<?php
});
