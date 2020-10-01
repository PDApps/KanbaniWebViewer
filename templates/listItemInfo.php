<?php
$context->hooks->registerLast("echo_listItemInfo", function (array $vars) {
    // $list
    extract($vars);
    $archivedCount = count(array_filter(array_column($list->cards, "archived")));
?>
<tr class="tbl__grp" data-kwv-list-customizations>
    <th colspan="2"><?=$this("List Information")?></th>
</tr>
<tr>
    <th class="tbl__th"><?=$this("List ID:")?></th>
    <td><?=htmlspecialchars($list->id)?></td>
</tr>
<tr>
    <th class="tbl__th"><?=$this("Created on:")?></th>
    <td><?=htmlspecialchars(Kanbani\formatTime($this, $list->create_time / 1000))?></td>
</tr>
<tr>
    <th class="tbl__th"><?=$this("Total cards:")?></th>
    <td>
        <?=$this(
            "%s, including %s archived and %s non-archived",
            Kanbani\formatNumber(count($list->cards)),
            Kanbani\formatNumber($archivedCount),
            Kanbani\formatNumber(count($list->cards) - $archivedCount)
        )?>
    </td>
</tr>
<?php if (strlen($list->custom)) {?>
    <tr>
        <th class="tbl__th"><?=$this("Custom data:")?></th>
        <td><?=htmlspecialchars(json_encode($list->custom))?></td>
    </tr>
<?php }?>
<?php
});