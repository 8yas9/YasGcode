<?php

function create_temp_file(string $path, string $content = ''): string
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($path, $content);

    return $path;
}

function cleanup_test_files(string ...$paths): void
{
    foreach ($paths as $path) {
        if (is_dir($path)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            rmdir($path);
        } elseif (file_exists($path)) {
            unlink($path);
        }
    }
}

function assert_file_contains(string $file, string $needle): void
{
    if (!file_exists($file)) {
        throw new \PHPUnit\Framework\AssertionFailedError("File does not exist: {$file}");
    }
    $content = file_get_contents($file);
    if (strpos($content, $needle) === false) {
        throw new \PHPUnit\Framework\AssertionFailedError("File does not contain: {$needle}");
    }
}

function assert_file_not_contains(string $file, string $needle): void
{
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, $needle) !== false) {
            throw new \PHPUnit\Framework\AssertionFailedError("File still contains: {$needle}");
        }
    }
}