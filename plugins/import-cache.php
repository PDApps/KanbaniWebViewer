<?php
/* This plugin stores imported (converted) boards in the cache directory (if one
   is configured) so that they can be viewed at a later time or on another computer. */

if ($context->config['cache']) {
    $context->hooks->register('unserialize', function () {
        $profile = $this->request["profile"] ?? "";
        if (preg_match('/^C-\w+$/', $profile) &&
                is_file($file = $this->config["cache"]."/$profile")) {
            $this->syncData(unserialize(Kanbani\getFileContentsWithLock($file)));
            $this->persistentReadOnly($profile);
            touch($file);
            foreach ($this->syncData->boards as $board) {
                if ($board->id === ($this->request['board'] ?? '')) {
                    $this->currentBoard($board);
                }
            }
            return true;
        }
    });
    $context->hooks->registerFirst('serve_import', function () {
        // On success, importers show serve the board page within the same request.
        // Intercept it and redirect to the page with the import-cache generated profile ID.
        $this->hooks->registerFirst('serve_viewBoard', function () {
            if ($this->syncData && !$this->profileID &&
                    ($this->config["import-cache.encrypted"] || !$this->syncFile->isEncrypted())) {
                $this->persistentReadOnly('C-'.Kanbani\QrCodeData::randomIdentifier());
                file_put_contents($this->config["cache"]."/$this->profileID", serialize($this->syncData), LOCK_EX);
                $url = $this->hooks->canonical(["profile" => $this->profileID]);
                header("Location: $url");
                return true;
            }
        });
    });
}
