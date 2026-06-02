<?php

// usage: php resolve_module_files.php --module=User

$options = getopt("", ["module:"]);
$module = $options['module'] ?? null;

if (!$module) {
    echo "Please provide a module name using --module=ModuleName\n";
    exit(1);
}

$root = getcwd();
$app = $root . '/src/app';
$routes = $root . '/src/routes';
$lowerModule = strtolower($module);

$paths = [
    'Controllers' => "{$app}/Http/Controllers/Api/{$module}",
    'Services' => "{$app}/Services/{$module}",
    'Requests' => "{$app}/Http/Requests/{$module}",
    'Resources' => "{$app}/Http/Resources/{$module}",
    'Repositories' => "{$app}/Repositories/Criteria/{$module}",
];

echo "MODULE_CONTEXT_START\n";
echo "Module: {$module}\n";

foreach ($paths as $type => $path) {
    if (is_dir($path)) {
        echo "[{$type}]\n";
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                echo $file->getRealPath() . "\n";
            }
        }
    }
}

// Check routes
echo "[Routes]\n";
$routePaths = [
    "{$routes}/api/{$lowerModule}.php",      // file
    "{$routes}/{$lowerModule}.php",          // file
    "{$routes}/api/{$lowerModule}",          // dir
];

foreach ($routePaths as $rp) {
    if (is_file($rp)) {
        echo $rp . "\n";
    } elseif (is_dir($rp)) {
         $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rp));
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                echo $file->getRealPath() . "\n";
            }
        }
    }
}

echo "MODULE_CONTEXT_END\n";

?>
