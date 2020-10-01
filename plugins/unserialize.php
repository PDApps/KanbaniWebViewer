<?php
/* This plugin allows viewing Kanbani profiles stored as regular files somewhere
   on this server's file system. Suitable for FTP, SFTP, WebDAV transports.
   Only !disable it if you have another plugin providing board data, like welcome.php. */

$context->hooks->register("unserialize", function () {
    $profile = $this->request["profile"] ?? "";
    if (preg_match('/^[\w-]+$/', $profile) &&
            ($root = $this->config["unserialize.path"]) &&
            is_dir($path = "$root/$profile/boards")) {
        $syncFile = new Kanbani\SyncFile;
        $boards = $filePaths = [];
        foreach (scandir($path) as $file) {
            if (strrchr($file, ".") === ".kanbani") {
                $fileData = null;
                $filePath = "$path/$file";
                $filePaths[$filePath] = filemtime($filePath);
                if ($cacheFile = $this->config["cache"]) {
                    $syncFile->unserializeHeader(file_get_contents($filePath));
                    $cacheFile = "$cacheFile/unser.".bin2hex($syncFile->hash);
                    try {
                        $fileData = unserialize(Kanbani\getFileContentsWithLock($cacheFile));
                        touch($cacheFile);
                        $cacheFile = null;      // don't write cache file below, it's ok.
                    } catch (\Throwable $e) {
                        // No cache file, corrupted, wrong serialize format, etc.
                        // Try unserializing the file directly, bypassing the cache.
                    }
                }
                if (!$fileData) {
                    try {
                        $fileData = (new Kanbani\SyncData)->unserializeFileUsing($syncFile, $filePath);
                    } catch (Kanbani\MissingSyncFileSecret $e) {
                        $fileData = $this->hooks->decrypt([
                            "filePath" => $filePath,
                            "syncFile" => $syncFile,
                            "profileID" => $profile,
                        ]);
                        if (!$fileData) {
                            $this->hooks->echo_decryptPage(["profileID" => $profile]);
                            exit;
                        }
                    }
                    if ($cacheFile && $this->config["unserialize.cache"] && !$syncFile->isEncrypted()) {
                        file_put_contents($cacheFile, serialize($fileData), LOCK_EX);
                    }
                }
                $boards = array_merge($boards, $fileData->boards);
            }
        }
        if ($boards) {
            $this->custom->unserialize_files = $filePaths;
            $this->syncData(new Kanbani\SyncData($boards), $syncFile)
                ->persistent($profile, $this->config["unserialize.qrCode"]);
            foreach ($boards as $board) {
                if ($board->id === ($this->request["board"] ?? "")) {
                    $this->currentBoard($board);
                }
            }
            return true;
        }
    }
});

$context->hooks->register("updated", function () {
    if ($files = ($this->custom->unserialize_files ?? [])) {
        clearstatcache();
        return array_map("filemtime", array_keys($files)) !== array_values($files);
    }
});