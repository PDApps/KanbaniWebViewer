<?php
/* This plugin adds an info button with forms for exporting current board as another
   format and importing a file making it a Kanbani board. */

$context->hooks->register("echo_board", function () {
?>
<form action="" enctype="multipart/form-data" method="post" id="importForm">
    <input type="hidden" name="do" value="import">
</form>
<?php
});

$context->hooks->register("echo_boardHeader", function () {
?>
<span class="info-hint info-hint_relative">
    <abbr class="isle info-hint__title"><?=$this("Convert")?></abbr>
    <div class="info-hint__hint horiz-bar__child">
        <?php if ($this->profileID) {?>
            <p>
                <?=$this("%sExport.%s Download this board as:", "<b>", "</b>")?>
                <?php $this->hooks->echo_boardExport(["form" => ""])?>
            </p>
            <hr class="info-hint__hr">
        <?php } elseif (!$this->kanbaniQrCode) {?>
            <p>
                <?=$this(
                    "%sThis board is only available for viewing until you close this page.%s It is not stored on this server and cannot be accessed by other devices.",
                    "<b>", "</b>"
                )?>
            </p>
            <hr class="info-hint__hr">
        <?php }?>
        <p>
            <?=$this(
                "%sImport.%s Convert arbitrary data to a Kanbani board (file up to %s):",
                "<b>", "</b>",
                ini_get("upload_max_filesize")."B"
            )?>
        </p>
        <p>
            <input form="importForm" type="file" name="upload" required>
            <?php $this->hooks->echo_boardImport(["form" => "importForm"])?>
        </p>
    </div>
</span>
<?php
});
