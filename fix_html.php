<?php
$dirs = [
    __DIR__ . '/View/FrontOffice/student',
    __DIR__ . '/View/FrontOffice/partner'
];

$replacements = [
    // Clean up preferences tags
    '<div class="tag" data-value="vegetarien"><span class="tag-emoji"><i class="fa-solid fa-house"></i> </span> Végétarien</div>' => '<div class="tag" data-value="vegetarien"><span class="tag-emoji"><i class="fa-solid fa-leaf"></i> </span> Végétarien</div>',
    '<div class="tag" data-value="vegan"><span class="tag-emoji"><i class="fa-solid fa-house"></i> 🌱</span> Végan</div>' => '<div class="tag" data-value="vegan"><span class="tag-emoji"><i class="fa-solid fa-seedling"></i> </span> Végan</div>',
    '<div class="tag" data-value="sans-gluten"><span class="tag-emoji"><i class="fa-solid fa-house"></i> â€¦\' Â¾</span> Sans gluten</div>' => '<div class="tag" data-value="sans-gluten"><span class="tag-emoji"><i class="fa-solid fa-wheat-awn"></i> </span> Sans gluten</div>',
    '<div class="tag" data-value="bio"><span class="tag-emoji"><i class="fa-solid fa-house"></i> â€ \'</span> Bio</div>' => '<div class="tag" data-value="bio"><span class="tag-emoji"><i class="fa-solid fa-leaf"></i> </span> Bio</div>',
    '<div class="tag" data-value="sans-lactose"><span class="tag-emoji"><i class="fa-solid fa-house"></i> <i class="fa-solid fa-glass-water"></i> </span> Sans lactose</div>' => '<div class="tag" data-value="sans-lactose"><span class="tag-emoji"><i class="fa-solid fa-glass-water"></i> </span> Sans lactose</div>',
    '<div class="tag" data-value="sans-noix"><span class="tag-emoji"><i class="fa-solid fa-house"></i> <i class="fa-solid fa-seedling"></i> </span> Sans fruits à coque</div>' => '<div class="tag" data-value="sans-noix"><span class="tag-emoji"><i class="fa-solid fa-seedling"></i> </span> Sans fruits à coque</div>',
    '<div class="tag" data-value="faible-sucre"><span class="tag-emoji"><i class="fa-solid fa-house"></i> </span> Faible en sucre</div>' => '<div class="tag" data-value="faible-sucre"><span class="tag-emoji"><i class="fa-solid fa-candy-cane"></i> </span> Faible en sucre</div>',
    
    // other specific artifacts
    '<label for="pref-frequency" style="display:block;font-weight:500;margin-bottom:12px;color:var(--color-white);"><i class="fa-solid fa-house"></i>... Fréquence de commande souhaitée</label>' => '<label for="pref-frequency" style="display:block;font-weight:500;margin-bottom:12px;color:var(--color-white);"><i class="fa-solid fa-clock"></i> Fréquence de commande souhaitée</label>',
    '<span><i class="fa-solid fa-house"></i> </span> Sauvegarder mes préférences' => '<span><i class="fa-solid fa-check"></i> </span> Sauvegarder mes préférences',
    
    // points.php
    'Débutant <i class="fa-solid fa-house"></i> 🌱' => 'Débutant 🌱',
    '<h3 style="margin-bottom:20px;"><i class="fa-solid fa-house"></i> <i class="fa-solid fa-gift"></i>  Comment gagner des points</h3>' => '<h3 style="margin-bottom:20px;"><i class="fa-solid fa-gift"></i> Comment gagner des points</h3>',
    '<div style="font-size:2.5rem;margin-bottom:12px;"><i class="fa-solid fa-house"></i> <i class="fa-solid fa-glass-water"></i> \'</div>' => '<div style="font-size:2.5rem;margin-bottom:12px;"><i class="fa-solid fa-bag-shopping"></i></div>',
    '<div style="font-size:2.5rem;margin-bottom:12px;"><i class="fa-solid fa-house"></i> <i class="fa-solid fa-coins"></i> </div>' => '<div style="font-size:2.5rem;margin-bottom:12px;"><i class="fa-solid fa-user-plus"></i></div>',
    '<h3 style="margin-bottom:20px;"><i class="fa-solid fa-house"></i> â€¦à‚Â½ Â  Récompenses disponibles</h3>' => '<h3 style="margin-bottom:20px;"><i class="fa-solid fa-gift"></i> Récompenses disponibles</h3>',
    '<div class="reward-icon"><i class="fa-solid fa-house"></i> </div>' => '<div class="reward-icon"><i class="fa-solid fa-croissant"></i></div>',
    '<div class="reward-icon"><i class="fa-solid fa-house"></i> <i class="fa-solid fa-ticket"></i> </div>' => '<div class="reward-icon"><i class="fa-solid fa-ticket"></i></div>',
    'â€¦¦à‚Â½ Â¯' => '<i class="fa-solid fa-gift"></i> ',
    'â‚¬à‚Âº\'' => '<i class="fa-solid fa-bag-shopping"></i>',
    'â‚¬à‹Å“ Â¥' => '<i class="fa-solid fa-user-plus"></i>',
    'â€¦¦à‚Â½ Â ' => '<i class="fa-solid fa-gift"></i>',
    'âà‹Å“Â¢' => '<i class="fa-solid fa-mug-hot"></i>',
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
echo "Cleaned up HTML.";
