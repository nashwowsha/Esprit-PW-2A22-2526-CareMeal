<?php
$dirs = [
    __DIR__ . '/View/FrontOffice/student',
    __DIR__ . '/View/FrontOffice/partner',
    __DIR__ . '/View/FrontOffice'
];

$replacements = [
    // preferences.php
    'âà‹Å“ Âªï Â¸ ' => '<i class="fa-solid fa-star-and-crescent"></i> ',
    'â‚¬à‚Âº' => '<i class="fa-solid fa-glass-water"></i> ',
    'âà…à‚Â¡-ï Â¸ ' => '<i class="fa-solid fa-scale-balanced"></i> ',
    'â€¦"' => '<i class="fa-solid fa-seedling"></i> ',
    
    // points.php
    'â€¦à‚Â½ Â¯' => '<i class="fa-solid fa-gift"></i> ',
    'â€¦à‚Â½ Â ' => '<i class="fa-solid fa-gift"></i> ',
    'â‚¬à‚Âº\'' => '<i class="fa-solid fa-coins"></i> ',
    'â‚¬à‹Å“ Â¥' => '<i class="fa-solid fa-coins"></i> ',
    'âà‹Å“Â¢' => '<i class="fa-solid fa-gift"></i> ',
    'â€¦à‚Â½ââ‚¬Â°' => '<i class="fa-solid fa-ticket"></i> ',
    
    // stats.php & offers.php
    'âÂ ââ‚¬à‹Å“' => '<i class="fa-solid fa-arrow-trend-up"></i> ',
    'âà…"...' => '<i class="fa-solid fa-chart-line"></i> ',
    'âà…"......' => '<i class="fa-solid fa-tags"></i> ',
    
    // module-evenements.php
    'à°Ã... ¸Ã...\'±' => '<i class="fa-solid fa-user-graduate"></i> ',
    'à°Ã... ¸ ±' => '<i class="fa-solid fa-seedling"></i> ',
    'à°Ã... ¸Ã... ½â€Å"' => '<i class="fa-solid fa-laptop-code"></i> ',
    'à°Ã... ¸ ³' => '<i class="fa-solid fa-kitchen-set"></i> ',
    'à°Ã... ¸â€Å"â€ ¦' => '<i class="fa-solid fa-users"></i> ',
    
    // Some leftovers
    'à  ' => 'à ', // double space
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    $files = scandir($dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $path = $dir . '/' . $file;
            $content = file_get_contents($path);
            
            foreach ($replacements as $search => $replace) {
                $content = str_replace($search, $replace, $content);
            }
            
            file_put_contents($path, $content);
        }
    }
}
echo "Emojis fixed.";
