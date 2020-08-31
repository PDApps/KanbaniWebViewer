<?php
/* This plugin displays an introductory "Welcome Board", same as in Kanbani.
   This is a read only board, it cannot be synced to. */

if (is_file($file = substr(__FILE__, 0, -4).".json.gz")) {
    $context->hooks->register("unserialize", function () use ($file) {
        if (!strcasecmp($this->request["profile"] ?? "", "welcome")) {
            $boards = json_decode(gzdecode(file_get_contents($file)));
            $board = $boards->{$this->language} ?? $boards["en"];
            $this->syncData(new Kanbani\SyncData([$board]))
                ->persistentReadOnly("Welcome");
            return true;
        }
    });
}