<?php
/* This plugin adds multiple options to board's Customize info button -
   title/description text filter, card color mode, sorting, etc. */

$context->hooks->register("filter", function (array &$cards, array $filters) {
    extract($filters, EXTR_PREFIX_ALL, "f");
    $filterRE = [];
    foreach (preg_split('/\s+/u', $f_filter) as $word) {
        if (strlen($word)) {
            $filterRE[] = preg_quote($word, "/");
        }
    }
    $filterRE = "/".join("|", $filterRE)."/uiS";
    foreach ($cards as &$info) {
        $card = $info["card"];
        $info["visible"] &=
            (!strlen($f_archived) || +$f_archived === +!!$card->archived) &&
            (!strlen($f_relatedName) || $card->related_name === $f_relatedName) &&
            (strlen($filterRE) === 5 || preg_match($filterRE, $card->title.$card->description));
    }
});

$context->hooks->registerLast("filter", function (array &$cards, array $filters) {
    extract($filters, EXTR_PREFIX_ALL, "f");
    if ($f_sort === "-") {
        $cards = array_reverse($cards);
    } elseif ($f_sort) {
        usort($cards, function ($a, $b) use ($f_sort) {
            $field = ltrim($f_sort, "-");
            if (is_int($a["card"]->$field)) {
                $result = $a["card"]->$field - $b["card"]->$field;
            } else {
                $result = strcoll($a["card"]->$field, $b["card"]->$field);
            }
            return ($f_sort[0] === "-" ? -1 : +1) * $result;
        });
    }
});

$context->hooks->register("start", function () {
    $this->request += [
        "view"          => "horiz",
        "archived"      => "",
        "relatedName"   => "",
        "sort"          => "",
        "cardColors"    => "bg",
        "filter"        => "",
    ];
});

$context->hooks->register("echo_boardHeader", function (array $vars) {
    // $filters 
    extract($vars);
    extract($filters, EXTR_PREFIX_ALL, "f");

    $card = null;
    $relatedNames = $sortFields = [];

    foreach ($this->currentBoard->lists as $list) {
        foreach ($list->cards ?? [] as $card) {
            // If viewing only archived or unarchived cards, exclude other
            // cards' related names from the selector.
            if (isset($card->archived) && (!strlen($f_archived) || +$f_archived === +!!$card->archived)) {
                $relatedNames[$card->related_name] = 1;
            }
        }
    }

    $relatedNames = array_keys($relatedNames);
    sort($relatedNames);

    $this->custom->customize_hideRelatedName = count($relatedNames) < 2;

    if ($card) {
        $sortFields = ["" => $this("Position"), "-" => $this("Position – reverse")];
        $fields = array_diff(array_keys((array) $card), ["field_history", "move_history"]);
        foreach ($fields as $field) {
            $title = $this(ucfirst(strtr($field, "_", " ")));
            $sortFields[$field] = $title;
            $sortFields["-$field"] = $this("%s – reverse", $title);
        }
    }
?>
<span class="info-hint info-hint_relative">
    <abbr class="isle info-hint__title"><?=$this("Customize")?></abbr>
    <div class="info-hint__hint horiz-bar__child">
        <table class="tbl info-hint__tbl">
            <tr>
                <th class="tbl__th"><?=$this("Layout:")?></th>
                <td>
                    <select name="view">
                        <?=Kanbani\htmlOptions(["horiz", "vert"], [$this("Horizontal"), $this("Vertical")], $f_view)?>
                    </select>
                </td>
            </tr>
            <tr data-kwv-customize="list">
                <th class="tbl__th"><?=$this("Archive:")?></th>
                <td>
                    <select name="archived">
                        <?=Kanbani\htmlOptions(["", "0", "1"], [$this("Show archived and normal cards"), $this("Show non-archived cards only"), $this("Show archived cards only")], $f_archived)?>
                    </select>
                </td>
            </tr>
            <tr data-kwv-customize="list">
                <th class="tbl__th"><?=$this("Related name:")?></th>
                <td>
                    <select name="relatedName">
                        <?=Kanbani\htmlOptions(array_merge([""], $relatedNames), array_merge([$this("(Cards with any name)")], $relatedNames), $f_relatedName)?>
                    </select>
                </td>
            </tr>
            <tr data-kwv-customize="list">
                <th class="tbl__th"><?=$this("Sort by:")?></th>
                <td>
                    <select name="sort">
                        <?=Kanbani\htmlOptions(array_keys($sortFields), array_values($sortFields), $f_sort)?>
                    </select>
                </td>
            </tr>
            <tr data-kwv-customize="list">
                <th class="tbl__th"><?=$this("Card colors:")?></th>
                <td>
                    <select name="cardColors">
                        <?=Kanbani\htmlOptions(["bg", "fg"], [$this("Background"), $this("Header")], $f_cardColors)?>
                    </select>
                </td>
            </tr>
            <?php
                // By convention, if there is one control/option only (or multiple
                // single-control options) - use registerFirst(), else use register()
                // and echo <tr tbl__grp>.
                echo $this->hooks->echo_boardCustomize($vars);
            ?>
        </table>
    </div>
</span>
<input name="filter" class="isle hdr__filter" autofocus
       placeholder="<?=htmlspecialchars($this("Filter cards by title and description"))?>"
       value="<?=htmlspecialchars($f_filter)?>">
<?php if ($this->profileID) {?>
    <button type="submit" class="hdr__submit"><?=$this("OK")?></button>
<?php } else {?>
    <noscript class="isle"><?=$this("JavaScript is required for customizing")?></noscript>
<?php }?>
<?php
});

$context->hooks->registerFirst("echo_listItemInfo", function () {
?>
<tr>
    <th class="tbl__th"><?=$this("Filter:")?></th>
    <td>
        <input name="filter"
               placeholder="<?=htmlspecialchars($this("Filter list’s cards by title and description"))?>">
    </td>
</tr>
<?php
});

$context->hooks->register("echo_empty", function (array &$vars) {
    $class = &$vars["bodyAttributes"]["class"];
    $class .= " bv_".($this->request["view"] ?? "");
});

$context->hooks->register("echo_listItem", function (array &$vars) {
    $class = &$vars['attributes']['class'];
    $class .= " lists_bg-mode_".$this->request["cardColors"];
    if (strlen($this->request["relatedName"] ?? "")) {
        $class .= " cust-rnfilter";
    }
});

$context->hooks->registerLast("echo_cardItem", function (array $vars) {
    // $card
    extract($vars);
    if (empty($this->custom->customize_hideRelatedName)) {
?>
    <div class="card-item__rel">
        <?=htmlspecialchars($card->related_name)?>
    </div>
<?php
    }
});
