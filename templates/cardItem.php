<?php
$context->hooks->registerFirst("echo_cardItem", function (array $vars) {
    // $card
    extract($vars);
?>
<header class="">
    <?php if ($card->archived) {?>
        <div class="card-item__labels">
            <span class="isle card-item__label card-archived"><?=$this("Archived")?></span>
        </div>
    <?php }?>
    <h3 class="card-item__name">
        <?php if ($this->profileID) {?>
        <a data-kwv-do="viewCard"
           href="<?=htmlspecialchars($this->hooks->canonical(["do" => "viewCard", "card" => $card->id, "profile" => $this->profileID]))?>">
            <?=htmlspecialchars($card->title)?></a>
        <?php } else {?>
            <?=htmlspecialchars($card->title)?>
        <?php }?>
    </h3>
</header>
<?php if ($card->due_time) {?>
    <span class="isle card-item__due card-due card-due_over_<?=+($card->due_time / 1000 < time())?>">
        <?=htmlspecialchars(Kanbani\formatTime($this, $card->due_time / 1000))?>
    </span>
<?php }?>
<?php
});

$context->hooks->registerLast("echo_cardItem", function (array $vars) {
    // $card
    extract($vars);
?>
<span class="info-hint card-item__hint">
    <abbr class="info-hint__title card-item__hint-title"><?=$this("Info")?></abbr>
    <div class="info-hint__hint">
        <table class="tbl info-hint__tbl">
            <?=$this->hooks->echo_cardItemInfo(compact("card"))?>
        </table>
    </div>
</span>
<?php
});
