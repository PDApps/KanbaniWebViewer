<?php
/* This plugin allows viewing encrypted Kanbani profiles. */
namespace Kanbani;

$parseCookie = function ($str) {
    $length = (int) $str;
    return [
        substr($str, strlen($length) + 1 /* "," */, $length),
        substr($str, strlen($length) + 1 + $length + 1 /* 2nd "," */),
    ];
};

$context->hooks->register("decrypt", function (array $options) use ($parseCookie) {
    // $filePath - required
    // $syncFile
    // $profileID
    extract($options);
    if (($profileID ?? "") &&  // this plugin doesn't support decrypting without known profile ID.
            $cookie = $this->cookie("kwvdecr_$profileID")) {
        $syncFile = $syncFile ?? new SyncFile;
        [$secret, $ids] = $parseCookie($cookie);
        // For multi-board profiles, we don't know which file corresponds to which
        // board. We could compare encryptedFileName() for every $id with
        // $syncFile's basename to learn that, but here we just try to decrypt
        // the file by testing each $id in turn.
        foreach (explode(",", $ids) as $id) {
            try {
                return (new SyncData)->unserializeFileUsing($syncFile, $filePath, $secret, $id);
            } catch (InvalidSyncFileHashException $e) {
                // Bad $secret and/id $id - try another.
            }
        }
    }
});

$context->hooks->register("serve_decrypt", function () {
    $profileID = trim($this->request["profile"]);
    $ids = trim($this->request["boards"] ?? "");
    $secret = trim($this->request["secret"] ?? "");
    if (strlen($ids) && strlen($secret)) {
        $value = strlen($secret).",$secret,".preg_replace('/[;\s]+/', ",", $ids);
        $this->cookie("kwvdecr_$profileID", $value);
        if ($this->request["redir"] ?? 1) {
            header("Location: ".$this->hooks->canonical(['profile' => $profileID]));
        } else {
            // JavaScriptless lass.
            echo $this("Settings saved. Reload this page.");
        }
    } else {
        $this->hooks->echo_decryptPage(compact("profileID", "secret", "ids"));
    }
    return true;
});

$context->hooks->register("echo_decrypt", function (array $vars) use ($parseCookie) {
    if ($cookie = $this->cookie("kwvdecr_$vars[profileID]")) {
        [$secret, $id] = $parseCookie($cookie);
    }
    // $profileID - required
    // $secret
    // $ids
    extract($vars);
    $frame = "decryptFrame".mt_rand();
?>
<form action="?do=decrypt&redir=0" method="post" target="<?=$frame?>" class="middle__in_left">
    <h1 class="middle__h1"><?=$this("Unlock an encrypted profile")?></h1>
    <p><?=$this("This sync data is encrypted. In order to view it in this browser you need to enter the following information.")?></p>
    <p>
        <b><?=$this("Secret:")?></b>
        <!-- Autocompletion won't work anyway since board URLs are farily random because of filter parameters. -->
        <input autofocus required autocomplete="off" class="block-area light-shade" name="secret" value="<?=htmlspecialchars($secret ?? "")?>">
    </p>
    <p>
        <?=$this("%sBoard IDs%s, space or comma-separated:", "<b>", "</b>")?>
        <textarea required class="block-area light-shade" name="boards"><?=htmlspecialchars($ids ?? "")?></textarea>
    </p>
    <p>
        <button type="submit"><b><?=$this("Unlock")?></b></button>
        <?php if ($this->cookie("kwvdecr_$profileID")) {?>
            <b><?=$this("(It wasn't possible to unlock it with the info you supplied last time.)")?></b>
        <?php }?>
    </p>
    <p><?=$this("These details will be stored in a temporary cookie on your computer. The cookie will be erased when you exit your browser, and you will have to unlock the profile again.")?></p>
    <p>
        <?=$this("Profile ID (do not edit):")?>
        <input required name="profile" value="<?=htmlspecialchars($profileID)?>" readonly>
    </p>
    <iframe class="decrypt__frame" name="<?=$frame?>" onload="this.contentWindow.location.host && location.reload()"></iframe>
</form>
<?php
    return true;
});
