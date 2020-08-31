<?php
/* This plugin adds an info button with QR codes for viewing this board in Kanbani
   and/or another web browser. */

$context->hooks->register("echo_boardHeader", function () {
    if ($this->kanbaniQrCode || $this->profileID) {
?>
<span class="info-hint info-hint_relative">
    <abbr class="isle info-hint__title"><?=$this($this->kanbaniQrCode ? "Join" : "Share")?></abbr>
    <div class="info-hint__hint horiz-bar__child">
        <?=$this->hooks->echo_boardShare()?>
    </div>
</span>
<?php
    }
});
