<?php
$context->hooks->registerLast("echo_cardInfo", function (array $vars) {
    // $card
    extract($vars);
?>
<dt><?=$this("Card ID:")?></dt>
<dd><?=htmlspecialchars($card->id)?></dd>
<dt><?=$this("Created on:")?></dt>
<dd><?=htmlspecialchars($this(...Kanbani\formatTime($card->create_time / 1000)))?></dd>
<dt><?=$this("Created by:")?></dt>
<dd><?=htmlspecialchars($card->create_user)?></dd>
<dt><?=$this("Changed on:")?></dt>
<dd><?=htmlspecialchars($this(...Kanbani\formatTime($card->change_time / 1000)))?></dd>
<dt><?=$this("Download as:")?></dt>
<dd class="card-export">
    <?=$this->hooks->echo_cardExport(compact("card"))?>
</dd>
<?php
});