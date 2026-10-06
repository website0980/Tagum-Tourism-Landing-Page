<?php

function listingImagePath($storedPath) {
    $storedPath = trim(str_replace('\\', '/', (string)$storedPath));
    if ($storedPath === '' || preg_match('~^(?:https?:)?//|^data:~i', $storedPath)) {
        return $storedPath;
    }

    $isRootRelative = strpos($storedPath, '/') === 0;
    $relativePath = preg_replace('~^(?:\.\./)+~', '', ltrim($storedPath, '/'));
    if ($isRootRelative) {
        $documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/\\');
        $imagePath = $documentRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $publicPath = '/' . $relativePath;
    } else {
        $imagePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $publicPath = '../' . $relativePath;
    }

    $thumbnailPath = dirname($imagePath) . DIRECTORY_SEPARATOR . pathinfo($imagePath, PATHINFO_FILENAME) . '-thumb.jpg';
    if (is_file($thumbnailPath)) {
        $publicPath = substr($publicPath, 0, strrpos($publicPath, '/') + 1)
            . pathinfo($imagePath, PATHINFO_FILENAME) . '-thumb.jpg';
    }

    return $publicPath;
}