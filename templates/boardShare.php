<?php
$context->hooks->registerLast("echo_boardShare", function () {
    $qrImageProfile = "?do=qrImageProfile&profile=".rawurlencode($this->profileID);
    $qrImageWeb = $this->hooks->canonical(['profile' => $this->profileID]);
    $qrImageWebImage = $qrImageWeb.(strrchr($qrImageWeb, "?") ? "&" : "?")."do=qrImageWeb";
    parse_str($this->server["QUERY_STRING"], $query);
    $qrImageWebCurrent = $this->hooks->canonical(["do" => "qrImageWeb"] + $query);
?>
<p>
    <?=$this(
        "This is Kanbani Web Viewer – an %sopen source%s web interface to Kanbani boards.",
        '<a target="_blank" href="https://pdapps.org/kanbani/web">',
        "</a>"
    )?>
</p>
<p>
    <?=$this(
        "%sKanbani%s is an advanced, flexible & freeware task manager for Android.",
        '<a target="_blank" href="https://pdapps.org/kanbani">',
        "</a>"
    )?>
</p>
<div class="switch">
    <?php if ($this->kanbaniQrCode) {?>
        <div class="switch__item switch__item_n_0 switch__item_visible">
            <p>
                <b><?=$this("Join this board for collaboration:")?></b>
            </p>
            <ol>
                <li>
                    <img class="share-hint__right" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEsAAABLAQAAAAAQNUdHAAAAAmJLR0QAAKqNIzIAAAAJcEhZcwAACxMAAAsTAQCanBgAAADXSURBVHjafdKxjcQgEAXQbxEQbgNI0wYZLXkb8PoasFvajDaQaMCbbYA894V80TEmegHS/8wAwFfVgH5GFM1uRXupxTDl8sht9jd0Wwy3bHO6IdPCgnAFD8i+4Zn/qg+oPJ8sqsUgpjdmX7doUX7UnUn2r8WyoK6xLBEGGxIeufcdU/Z3XX1hpkEglqdqbzYkn9te3wCmGVx4N5dJLcoGXfnuXAxyZG5LciSLnLocHHzvOyL3prv2u2Ny83Kl2eTIZ+CGZ6ynt8g0+fCDJ4vs6w7frm/0n7+pVTd0AxAmGgAAAABJRU5ErkJggg==">
                    <?=$this(
                        "Install Kanbani from %sGoogle Play%s or from %spdapps.org%s",
                        '<a target="_blank" href="https://play.google.com/store/apps/details?id=org.pdapps.kanbani">',
                        "</a>",
                        '<a target="_blank" href="https://pdapps.org/kanbani">',
                        "</a>"
                    )?>
                    <a title="<?=$this("Google Play and the Google Play logo are trademarks of Google LLC.")?>"
                       target="_blank" href="https://play.google.com/store/apps/details?id=org.pdapps.kanbani">
                        <img class="info-hint__block-img info-hint__block-img_max-height"
                             alt="<?=$this("Get it on Google Play")?>"
                             src="<?=$this("https://play.google.com/intl/en_gb/badges/static/images/badges/en_badge_web_generic.png")?>">
                    </a>
                </li>
                <li>
                    <?=$this(
                        "Open Kanbani and scan or %sdownload%s this QR code from the main menu:",
                        '<a href="'.htmlspecialchars($qrImageProfile).'&dl=1">',
                        "</a>"
                    )?>
                    <img class="info-hint__block-img" src="<?=htmlspecialchars($qrImageProfile)?>">
                </li>
            </ol>
            <p>
                <a href="#" class="switch__go switch__go_n_1">
                    <?=$this("Or share this page for another web browser")?></a>
                &rarr;
            </p>
        </div>
    <?php }?>
    <?php if ($this->profileID) {?>
        <div class="switch__item switch__item_n_1 <?=$this->kanbaniQrCode ? "" : "switch__item_visible"?>">
            <p>
                <b><?=$this("View this board in any web browser:")?></b>
            </p>
            <p>
                <?=$this(
                    "Copy/paste or send yourself %sthis URL%s, or scan or %sdownload%s this QR code:",
                    '<a href="'.htmlspecialchars($qrImageWeb).'">', "</a>",
                    '<a href="'.htmlspecialchars($qrImageWebImage).'&dl=1">', "</a>"
                )?>
                <img class="info-hint__block-img" src="<?=htmlspecialchars($qrImageWebImage)?>">
            </p>
            <p>
                <?=$this(
                    "The above uses default options. To preserve your current sorting and other parameters, copy/paste %sthis page’s URL%s or scan or %sdownload%s this QR code:",
                    '<a href="">', "</a>",
                    '<a data-kwv-qr="qrImageWeb&dl=1" href="'.htmlspecialchars($qrImageWebCurrent).'&dl=1">',
                    "</a>"
                )?>
                <img data-kwv-qr="qrImageWeb" class="info-hint__block-img"
                     src="<?=htmlspecialchars($qrImageWebCurrent)?>">
            </p>
            <?php if ($this->kanbaniQrCode) {?>
                <p>
                    <a href="#" class="switch__go switch__go_n_0">
                        <?=$this("Or join for collaboration using an Android device")?></a>
                    &rarr;
                </p>
            <?php }?>
        </div>
    <?php }?>
</div>
<?php if (!$this->kanbaniQrCode && $this->profileID) {?>
    <p>
        <?=$this("Note: this board is view-only and cannot be edited by the Kanbani app.")?>
    </p>
<?php }?>
<?php
});