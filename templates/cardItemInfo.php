<?php
$context->hooks->registerLast("echo_cardItemInfo", function (array $vars) {
    // $card
    extract($vars);
?>
<tr>
    <th class="tbl__th"><?=$this("Card ID:")?></th>
    <td><?=htmlspecialchars($card->id)?></td>
</tr>
<tr>
    <th class="tbl__th"><?=$this("Created on:")?></th>
    <td><?=htmlspecialchars(Kanbani\formatTime($this, $card->create_time / 1000))?></td>
</tr>
<tr>
    <th class="tbl__th"><?=$this("Created by:")?></th>
    <td><?=htmlspecialchars($card->create_user)?></td>
</tr>
<tr>
    <th class="tbl__th"><?=$this("Changed on:")?></th>
    <td><?=htmlspecialchars(Kanbani\formatTime($this, $card->change_time / 1000))?></td>
</tr>
<?php if (strlen($card->custom)) {?>
    <tr>
        <th class="tbl__th"><?=$this("Custom data:")?></th>
        <td><?=htmlspecialchars(json_encode($card->custom))?></td>
    </tr>
<?php }?>
<?php
});