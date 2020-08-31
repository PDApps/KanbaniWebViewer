<?php
$context->hooks->registerLast("echo_board", function (array $vars) {
    // $filters
    extract($vars);
?>
<header class="horiz-bar" data-kwv-board-customizations>
    <input data-kwv-override type="checkbox" class="cust__override"
           title="<?=$this("Check to use the global filter for this list, uncheck to specify custom")?>">
    <form action="">
        <input type="hidden" name="profile" value="<?=$this->profileID?>">
        <?php if (count($this->syncData->boards) > 1) {?>
            <select class="hdr__brds" name="board">
                <?=Kanbani\htmlOptions(
                    array_column($this->syncData->boards, "id"),
                    array_column($this->syncData->boards, "title"),
                    $this->currentBoard->id
                )?>
            </select>
        <?php } else {?>
            <h1 class="isle hdr__name"><?=htmlspecialchars($this->currentBoard->title)?></h1>
        <?php }?>
        <?=$this->hooks->echo_boardHeader($vars)?>
    </form>
</header>
<?=$this->hooks->echo_boardBars($vars)?>
<div class="lists">
    <?php
        foreach ($this->lists as $list) {
            $attrs = [
                "id" => $list["list"]->id,
                "class" => "lists__list list".
                           ($list["visible"] ? "" : " filtered"),
            ];
            $html = $this->hooks->template("listItem", $list + ["cards" => $this->cards[$list["list"]], "attributes" => &$attrs]);
            // Avoid spaces between div.lists__list for proper margins.
            echo "<div ", Kanbani\htmlAttributes($attrs), ">", $html, "</div>";
            $this->hooks->echo_afterListItem($list);
        }
    ?>
</div>
<script>
    var kanbaniData = {
        profile: <?=preg_replace('/</u', "&lt;", $this->syncData->serializeToJSON())?>,
        currentBoard: <?=intval(array_search($this->currentBoard, $this->syncData->boards, true))?>
    }
</script>
<?php
});
