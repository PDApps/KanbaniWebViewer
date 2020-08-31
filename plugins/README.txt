CSS, JS and PHP files are automatically loaded for every page requested by client.
The order depends on the file name, e.g. "0-first.*" is loaded before "9-second.*".

Use plugins instead of modifying KWV's files to simplify upgrading and avoid conflicts.
To disable a plugin, do not remove its file - it will come back after upgrade.
Instead, exclude it in the config (see config-defaults.php).