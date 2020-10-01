<?php
/* This is part of KWV core handling standard pages. It should not be !disabled. */
namespace Kanbani;

$context->hooks->register("serve_viewBoard", function () {
    if ($this->request["profile"] ?? "") {
        $this->unserialize();
        if (!$this->syncData) {
            throw new PublicException("No such sync profile exists on this server. You may need to perform the sync with Kanbani first, then refresh this page.");
        }
        $this->lists = [];
        $this->cards = new \SplObjectStorage;
        foreach ($this->currentBoard->lists as $list) {
            if (isset($list->cards)) {      // not deleted.
                $this->lists[] = ["list" => $list, "visible" => true];
                $cards = [];
                foreach ($list->cards as $card) {
                    if (isset($card->title)) {
                        $cards[] = ["card" => $card, "visible" => true];
                    }
                }
                $this->hooks->trigger("filter", [&$cards, $this->request]);
                $this->cards[$list] = $cards;
            }
        }
        $this->hooks->echo_empty([
            "title" => $this("%s | Kanbani Web Viewer", $this->currentBoard->title),
            "body"  => $this->hooks->template("board", [
                "filters" => $this->request,
            ]),
        ]);
    } else {
        $url = $this->config["index"];
        if ($url instanceof \Closure) { $url = $url->bindTo($this)(); }
        if ($url) {
            header("Location: $url");
        } else {
            throw new PublicException("No sync profile ID was given.");
        }
    }
    return true;
});

$context->hooks->register("serve_viewCard", function () {
    $this->unserialize();
    $card = $this->syncData->findCard($this->request["card"], $list, $this->currentBoard);
    if ($this->server["HTTP_X_REQUESTED_WITH"] ?? "") {
        $this->hooks->echo_card(compact("card", "list"));
    } else {
        $this->hooks->echo_empty([
            "bodyAttributes" => ["class" => "body_shaded"],
            "body" => $this->hooks->template("card", compact("card", "list")),
        ]);
    }
    return true;
});

$serveQR = function ($context, $data, QrCodeData $kanbaniQrCode = null) {
    $headers = [];
    $large = !empty($context->request["dl"]);
    $vars = compact("headers", "large", "data", "kanbaniQrCode");
    ob_start();
    $context->hooks->trigger("echo_qrCode", [&$vars]);
    if ($large) {
        foreach ($vars["headers"] as $header) {
            if (!strncasecmp($header, "content-type", 12) &&
                    preg_match("!\bimage/(\w+)!", $header, $match)) {
                $ext = $match[1];
            }
        }
        $vars["headers"][] = "Content-Disposition: attachment; filename=\"".preg_replace('/["\\\\]/u', "", $context->currentBoard->title ?: $context("Share")).".$ext\"";
    }
    array_map("header", $vars["headers"]);
    ob_end_flush();
    return true;
};

$context->hooks->register("serve_qrImageProfile", function () use ($serveQR) {
    $this->unserialize();
    if (!$this->kanbaniQrCode) {
        throw new PublicException("This board is view-only and cannot be collaborated on.");
    }
    $qrData = clone $this->kanbaniQrCode;
    $qrData->id = $this->profileID;
    $qrData->title = $this->currentBoard->title;
    $qrData->boards = array_map([QrCodeBoard::class, "from"], $this->syncData->boards);
    if ($this->syncFile->isEncrypted()) {
        $qrData->secret = $this->syncFile->secret;
        $qrData->hashAlgorithm = $this->syncFile->hashAlgorithm;
        $qrData->encryptAlgorithm = $this->syncFile->encryptAlgorithm;
    }
    return $serveQR($this, $qrData->serialize(), $qrData);
});

$context->hooks->register("serve_qrImageWeb", function () use ($serveQR) {
    $this->unserialize();
    if (!$this->profileID) {
        throw new PublicException("This board is transient and cannot be viewed elsewhere.");
    }
    parse_str($this->server["QUERY_STRING"], $query);
    unset($query["do"]);
    unset($query["dl"]);
    $data = $this->hooks->canonical($query);
    // Encoding ~ is not necessary in URL components, see the comment in kwv.js. Makes the image larger.
    return $serveQR($this, str_replace("%7E", "~", $data));
});
