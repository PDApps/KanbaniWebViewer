<?php
$context->hooks->registerLast("echo_card", function (array $vars) {
    // $card
    // $list
    extract($vars);
?>
<div class="card-det body__card-det round">
    <h1 class="card-det__name"><?=htmlspecialchars($card->title)?></h1>
    <div class="card-det__loc">
        <?=$this("in %s / %s", "<b>".htmlspecialchars($list->title)."</b>", htmlspecialchars($this->currentBoard->title))?>
    </div>
    <dl class="card-det__info">
        <?=$this->hooks->echo_cardInfo(compact("card"))?>
    </dl>
    <div class="card-labels card-det__labels">
        <?php if ($card->archived) {?>
            <span class="isle card-labels__label card-det__label card-archived"><?=$this("Archived card")?></span>
        <?php }?>
        <?php if ($card->due_time) {?>
            <span class="isle card-labels__label card-det__label card-due card-due_over_<?=+($card->due_time / 1000 < time())?>">
                <?=htmlspecialchars(Kanbani\formatTime($this, $card->due_time / 1000))?>
            </span>
        <?php }?>
    </div>
    <?php if (strlen($card->description)) {?>
        <div class="card-det__desc-wr light-shade">
            <h2 class="card-det__desc-hdr"><?=$this("Description")?></h2>
            <article class="card-det__desc">
                <?=$this->hooks->trigger("format", [$card->description, $card])?>
            </article>
        </div>
    <?php }?>
    <?php if (strlen($card->custom)) {?>
        <h2 class="card-det__desc-hdr"><?=$this("Custom Data")?></h2>
        <textarea readonly class="card-det__custom light-shade block-area">
<?=htmlspecialchars(json_encode($card->custom, JSON_PRETTY_PRINT | Kanbani\JSON_FLAGS))?>
</textarea>
    <?php }?>
</div>
<?php
});
