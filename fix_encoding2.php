<?php
$dirs = [
    __DIR__ . '/View/FrontOffice/student',
    __DIR__ . '/View/FrontOffice/partner'
];

// Fix the overlapping replacements from the previous script
$replacements = [
    'à§' => 'ç',
    'à‰' => 'É',
    'à®' => 'î',
    'à¯' => 'ï',
    'à»' => 'û',
    'à´' => 'ô',
    'àœ' => 'Ü',
    'à ' => 'à', // 'Ã ' became 'à '
    'à©' => 'é', // Just in case
    'à¨' => 'è',
    'àª' => 'ê',
    'à¢' => 'â',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    $files = scandir($dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php' && $file !== 'dashboard.php') {
            $path = $dir . '/' . $file;
            $content = file_get_contents($path);
            
            // Apply replacements
            foreach ($replacements as $search => $replace) {
                $content = str_replace($search, $replace, $content);
            }

            file_put_contents($path, $content);
            echo "Fixed: $path\n";
        }
    }
}

// Also let's fix dashboard.php in case I missed it manually or it was affected.
// Actually dashboard.php was NOT affected by the script because of `&& $file !== 'dashboard.php'`
echo "Done.";
