<?php
/* This plugin adds an info button to the board page header with details like
   encryption, last change time, etc. */
namespace Kanbani;

$context->hooks->register("echo_boardHeader", function () {
    $allCards = array_merge(...array_column($this->currentBoard->lists, "cards"));
    $lastChanged = max(array_column($allCards, "change_time") ?: [0]) / 1000;
?>
<span class="info-hint info-hint_relative">
    <abbr class="isle info-hint__title <?=$this->syncFile->isEncrypted() ? "hdr__encr" : ""?>"
    ><?=$this(
        $this->syncFile->isEncrypted() ? "%s (encrypted)" : "%s",
        timeInterval($this, $lastChanged)
    )?></abbr>
    <div class="info-hint__hint horiz-bar__child">
        <table class="tbl info-hint__tbl">
            <tr class="tbl__grp">
                <th colspan="2"><?=$this("File Information")?></th>
            </tr>
            <tr>
                <th class="tbl__th"><?=$this("Encrypted:")?></th>
                <td>
                    <?php if ($this->syncFile->isEncrypted()) {?>
                        <?=htmlspecialchars($this("yes (%s)", $this->syncFile->encryptAlgorithm))?>
                    <?php } else {?>
                        <?=$this("no")?>
                    <?php }?>
                </td>
            </tr>
            <tr>
                <th class="tbl__th"><?=$this("Corrupted:")?></th>
                <td>
                    <?=htmlspecialchars($this($this->syncFile->isBadHash ? "yes (%s)" : "no (%s)", $this->syncFile->hashAlgorithm))?>
                </td>
            </tr>
            <tr class="tbl__grp">
                <th colspan="2"><?=$this("Board Information")?></th>
            </tr>
            <tr>
                <th class="tbl__th"><?=$this("Board ID:")?></th>
                <td><?=htmlspecialchars($this->currentBoard->id)?></td>
            </tr>
            <tr>
                <th class="tbl__th"><?=$this("Created on:")?></th>
                <td><?=htmlspecialchars(formatTime($this, $this->currentBoard->create_time / 1000))?></td>
            </tr>
            <tr>
                <th class="tbl__th"><?=$this("Changed on:")?></th>
                <td><?=htmlspecialchars(formatTime($this, $lastChanged))?></td>
            </tr>
            <tr>
                <th class="tbl__th"><?=$this("Total cards:")?></th>
                <td><?=formatNumber(count($allCards))?></td>
            </tr>
            <?php if (strlen($this->currentBoard->custom)) {?>
                <tr>
                    <th class="tbl__th"><?=$this("Custom data:")?></th>
                    <td><?=htmlspecialchars(json_encode($this->currentBoard->custom))?></td>
                </tr>
            <?php }?>
        </table>
    </div>
</span>
<?php
});
