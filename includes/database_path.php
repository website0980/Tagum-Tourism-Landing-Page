<?php

function appDatabasePath(): string {
    $configuredPath = getenv('TAGUM_DATABASE_PATH');
    if (is_string($configuredPath) && trim($configuredPath) !== '') {
        return $configuredPath;
    }

    $projectRoot = dirname(__DIR__);
    return dirname($projectRoot)
        . DIRECTORY_SEPARATOR . basename($projectRoot) . '-data'
        . DIRECTORY_SEPARATOR . 'database.db';
}