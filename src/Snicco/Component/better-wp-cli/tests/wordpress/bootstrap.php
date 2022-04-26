<?php

declare(strict_types=1);

$root_folder = $_SERVER['WP_ROOT_FOLDER'] ?? null;

if (! is_string($root_folder) || ! is_dir($root_folder)) {
    echo "Invalid WordPress root folder\n";
    exit(1);
}

if (is_link($root_folder . '/wp-content/mu-plugins/better-wp-cli-test.php')) {
    return;
}

codecept_debug('mu-plugin is not symlinked yet.');

if (! is_dir($root_folder . '/wp-content/mu-plugins')) {
    codecept_debug('Creating mu-plugins dir');
    mkdir($root_folder . '/wp-content/mu-plugins');
} else {
    codecept_debug('must use plugin dir is already present.');
}

$res = symlink(__DIR__ . '/mu-plugin.php', $root_folder . '/wp-content/mu-plugins/better-wp-cli-test.php');

if (! $res) {
    echo "Could not create symlink\n";
    exit(1);
}

codecept_debug('Symlink created.');
