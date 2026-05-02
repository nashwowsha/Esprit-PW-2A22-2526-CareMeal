<?php
$dirs = [
    __DIR__ . '/View/FrontOffice/student',
    __DIR__ . '/View/FrontOffice/partner'
];

// Dictionary of manual replacements since some emojis got completely mangled beyond simple decoding
$replacements = [
    'Ã©' => 'é',
    'Ã¨' => 'è',
    'Ãª' => 'ê',
    'Ã¢' => 'â',
    'Ã' => 'à', // Notice: 'à ' might match 'Ã '
    'Ã ' => 'à',
    'Ã§' => 'ç',
    'Ã‰' => 'É',
    'Ã®' => 'î',
    'Ã¯' => 'ï',
    'Ã»' => 'û',
    'Ã´' => 'ô',
    'Ãœ' => 'Ü',
    'â€”' => '—',
    'ðŸ‘¤' => '👤',
    'ðŸ¥³' => '🥳',
    'ðŸ”¥' => '🔥',
    'ðŸ‘‹' => '👋',
    'â˜°' => '☰',
    'Ã¢Ã…Ã‚Â¡-Ã¯ Â¸' => '⚖️',
    'â€¦\' Â±' => '🌱',
    'â€¦Ã‚Â½ Â ' => '🎁',
    'ðŸ’°' => '💰',
    'ðŸ“ˆ' => '📈',
    'ðŸ¤' => '🤝',
    'â€¦Â¯' => '🚀',
    'Ã®' => 'î',
    'âœ…' => '✅',
    'ðŸš€' => '🚀',
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
            
            // Also fix the weird "à " spacing issue
            $content = str_replace('Ã  ', 'à ', $content);

            file_put_contents($path, $content);
            echo "Fixed: $path\n";
        }
    }
}
echo "Done.";
