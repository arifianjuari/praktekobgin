<?php
function checkRequirement($name, $condition, $required = true)
{
    echo $name . ': ';
    if ($condition) {
        echo '<span style="color: green;">OK</span>';
    } else {
        echo '<span style="color: ' . ($required ? 'red">FAILED' : 'orange">WARNING') . '</span>';
    }
    echo '<br>';
}

echo '<h1>Server Requirements Check</h1>';

// PHP Version
checkRequirement(
    'PHP Version (>= 7.4)',
    version_compare(PHP_VERSION, '7.4.0', '>=')
);

// Required Extensions
$requiredExtensions = [
    'pdo',
    'pdo_mysql',
    'gd',
    'fileinfo',
    'mbstring',
    'json',
    'session'
];

foreach ($requiredExtensions as $ext) {
    checkRequirement(
        "Extension: $ext",
        extension_loaded($ext)
    );
}

// Optional Extensions
$optionalExtensions = [
    'imagick',
    'exif',
    'curl'
];

foreach ($optionalExtensions as $ext) {
    checkRequirement(
        "Extension: $ext (optional)",
        extension_loaded($ext),
        false
    );
}

// Directory Permissions
$directories = [
    'uploads',
    'uploads/edukasi',
    'logs'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        @mkdir($dir, 0755, true);
    }
    checkRequirement(
        "Directory $dir is writable",
        is_writable($dir)
    );
}

// Memory Limit
$memoryLimit = ini_get('memory_limit');
$memoryLimitBytes = return_bytes($memoryLimit);
checkRequirement(
    "Memory Limit (>= 128M) - Current: $memoryLimit",
    $memoryLimitBytes >= 128 * 1024 * 1024
);

// Upload Max Filesize
$uploadMax = ini_get('upload_max_filesize');
$uploadMaxBytes = return_bytes($uploadMax);
checkRequirement(
    "Upload Max Filesize (>= 2M) - Current: $uploadMax",
    $uploadMaxBytes >= 2 * 1024 * 1024
);

// Post Max Size
$postMax = ini_get('post_max_size');
$postMaxBytes = return_bytes($postMax);
checkRequirement(
    "Post Max Size (>= 8M) - Current: $postMax",
    $postMaxBytes >= 8 * 1024 * 1024
);

// Max Execution Time
$maxExecution = ini_get('max_execution_time');
checkRequirement(
    "Max Execution Time (>= 30) - Current: $maxExecution",
    $maxExecution >= 30 || $maxExecution == 0
);

// Helper function to convert PHP size strings to bytes
function return_bytes($size_str)
{
    switch (substr($size_str, -1)) {
        case 'K':
        case 'k':
            return (int)$size_str * 1024;
        case 'M':
        case 'm':
            return (int)$size_str * 1024 * 1024;
        case 'G':
        case 'g':
            return (int)$size_str * 1024 * 1024 * 1024;
        default:
            return (int)$size_str;
    }
}
