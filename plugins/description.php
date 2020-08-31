<?php
/* This plugin shows card description snippets on the board view page and adds
   Customize options to control them. */

$context->hooks->register("start", function () {
    $this->request += [
        "descLines"     => 2,
        "descDir"       => "",
        "descBreaks"    => "",
    ];
});

$context->hooks->registerLast("echo_boardCustomize", function (array $vars) {
    // $filters - descLines descDir descBreaks
    extract($vars);
    extract($filters, EXTR_PREFIX_ALL, "f");
?>
<tr data-kwv-customize="list" class="tbl__grp">
    <th colspan="2"><?=$this("Display Descriptions")?></th>
</tr>
<tr data-kwv-customize="list">
    <th class="tbl__th"><?=$this("Number of lines:")?></th>
    <td>
        <input type="number" name="descLines"
               value="<?=intval($f_descLines)?>" min="0" max="20">
    </td>
</tr>
<tr data-kwv-customize="list">
    <th class="tbl__th"><?=$this("Starting from:")?></th>
    <td>
        <select name="descDir">
            <?=Kanbani\htmlOptions(["", "1"], [$this("Description’s start"), $this("Description’s end")], $f_descDir)?>
        </select>
        <noscript><?=$this("(needs JS)")?></noscript>
    </td>
</tr>
<tr data-kwv-customize="list">
    <th colspan="2" class="tbl__th">
        <input type="checkbox" name="descBreaks" <?=$f_descBreaks ? "checked" : ""?>>
        <?=$this("Preserve line breaks (fold lines if unchecked)")?>
    </th>
</tr>
<?php
});

$context->hooks->register("echo_cardItem", function (array $vars) {
    // $card
    extract($vars);
    $descLines = $this->request["descLines"];
    if (strlen(trim($card->description))) {
?>
    <article class="card-item__desc">
        <?=$this->hooks->format($card->description, $card)?>
    </article>
    <textarea class="card-item__desc-snip <?=$descLines > 0 ? "" : "filtered"?>"
              readonly rows="<?=intval($descLines)?>">
<?=($this->request["descBreaks"] ?? "")
    ? htmlspecialchars($card->description)
    : htmlspecialchars(preg_replace('/\v/u', " " /*<-en space*/, $card->description))
?>
</textarea>
<?php
    }
});
