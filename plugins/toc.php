<?php
/* This plugin adds Table of Contents (ToC) to each board. */

$context->hooks->registerFirst("echo_boardHeader", function () {
?>
<a href="#toc" class="isle brd-toc-toggle" title="<?=$this("Table of Contents")?>"><?=$this("ToC")?></a>
<?php
});

$context->hooks->register("echo_boardBars", function () {
?>
<ol class="brd-toc round" id="toc">
    <?php foreach ($this->lists as $list) {?>
        <li class="<?=$list["visible"] ? "" : "filtered"?>">
            <a href="#<?=htmlspecialchars($list["list"]->id)?>">
                <b><?=htmlspecialchars($list["list"]->title)?></b>
                <?=$this("(%s)", Kanbani\formatNumber(count($list["list"]->cards)))?>
            </a>
            <ol>
                <?php foreach ($this->cards[$list["list"]] as $info) {?>
                    <?php $card = $info["card"]?>
                    <li class="<?=$info["visible"] ? "" : "filtered"?>"
                        data-kwv-toc="<?=htmlspecialchars($card->id)?>">
                        <a href="#<?=htmlspecialchars($card->id)?>">
                            <?=htmlspecialchars($card->title)?>
                            <?php if ($card->archived) {?>
                                <span class="isle brd-toc__label card-archived"><?=$this("Archived")?></span>
                            <?php }?>
                            <?php if ($card->due_time) {?>
                                <span class="isle brd-toc__label card-due card-due_over_<?=+($card->due_time / 1000 < time())?>">
                                    <?=htmlspecialchars($this(...Kanbani\formatTime($card->due_time / 1000)))?>
                                </span>
                            <?php }?>
                        </a>
                    </li>
                <?php }?>
            </ol>
        </li>
    <?php }?>
</ol>
<?php
});
