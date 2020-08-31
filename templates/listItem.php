<?php
$context->hooks->registerLast("echo_listItem", function (array $vars) {
    // $list
    // $cards
    extract($vars);
?>
<header class="list-hdr">
    <h2 class="list-hdr__name"><?=htmlspecialchars($list->title)?></h2>
    <span class="info-hint list-hdr__hint">
        <abbr class="info-hint__title list-hdr__hint-title" data-kwv-list-counter>
            <?=Kanbani\formatNumber(count($cards))?>
        </abbr>
        <div class="info-hint__hint list-hdr__hint-hint">
            <table class="tbl info-hint__tbl">
                <?=$this->hooks->echo_listItemInfo(compact("list"))?>
            </table>
        </div>
    </span>
</header>
<ol class="list__cards card-list">
    <li class="list-empty <?=array_filter(array_column($cards, "visible")) ? "filtered" : ""?>" data-kwv-list-empty>
        <?=$list->cards
            ? $this(
                "All cards (%s) are hidden by filters",
                Kanbani\formatNumber(count($list->cards))
            )
            : $this("Empty list")
        ?>
    </li>
    <?php
        foreach ($cards as $card) {
            $attrs = [
                "id" => $card["card"]->id,
                "class" => "card-list__item light-shade card-item".
                           " card-item_bg_".intval($card["card"]->color).
                           ($card["visible"] ? "" : " filtered"),
            ];
            $html = $this->hooks->template("cardItem", $card + ["attributes" => &$attrs]);
            echo "<li ", Kanbani\htmlAttributes($attrs), ">", $html, "</li>";
            $this->hooks->echo_afterCardItem($card);
        }
    ?>
</ol>
<?php
});
