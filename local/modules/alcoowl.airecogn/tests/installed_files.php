<?php

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 4);

$directoryMappings = [
    dirname(__DIR__) . '/install/components/airecogn' => $_SERVER['DOCUMENT_ROOT'] . '/local/components/airecogn',
    dirname(__DIR__) . '/install/public' => $_SERVER['DOCUMENT_ROOT'] . '/local/handler/airecogn',
];

$checkedFiles = 0;

foreach ($directoryMappings as $sourceDirectory => $installedDirectory)
{
    if (!is_dir($sourceDirectory))
    {
        throw new RuntimeException('Source directory does not exist: ' . $sourceDirectory);
    }

    if (!is_dir($installedDirectory))
    {
        throw new RuntimeException('Installed directory does not exist: ' . $installedDirectory);
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $sourceFile)
    {
        if (!$sourceFile->isFile())
        {
            continue;
        }

        $relativePath = substr($sourceFile->getPathname(), strlen($sourceDirectory) + 1);
        $installedFile = $installedDirectory . '/' . str_replace('\\', '/', $relativePath);

        if (!is_file($installedFile))
        {
            throw new RuntimeException('Installed file is missing: ' . $installedFile);
        }

        if (hash_file('sha256', $sourceFile->getPathname()) !== hash_file('sha256', $installedFile))
        {
            throw new RuntimeException('Installed file differs from module source: ' . $installedFile);
        }

        echo 'file: OK — ' . str_replace('\\', '/', $relativePath) . "\n";
        $checkedFiles++;
    }

    echo 'directory: OK — ' . $installedDirectory . "\n";
}

$obsoleteFiles = [
    $_SERVER['DOCUMENT_ROOT'] . '/local/handler/airecogn/end_call/index.php',
    dirname(__DIR__) . '/handler/inbound.php',
];
foreach ($obsoleteFiles as $obsoleteFile)
{
    if (file_exists($obsoleteFile))
    {
        throw new RuntimeException('Obsolete public endpoint still exists: ' . $obsoleteFile);
    }

    echo 'obsolete endpoint absent: OK — ' . $obsoleteFile . "\n";
}

if ($checkedFiles === 0)
{
    throw new RuntimeException('No installed files were checked');
}

echo "installed files checked: {$checkedFiles}\n";
