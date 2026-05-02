<?php
$f = 'c:\xampp\htdocs\projet2a22\View\FrontOffice\student\points.php';
$c = file_get_contents($f);
$c = preg_replace('/<h3[^>]*><i class="fa-solid fa-house"><\/i>[^<]*Récompenses disponibles<\/h3>/', '<h3 style="margin-bottom:20px;"><i class="fa-solid fa-gift"></i> Récompenses disponibles</h3>', $c);
$c = preg_replace('/<div class="reward-icon"><i class="fa-solid fa-house"><\/i>\s*<\/div>/', '<div class="reward-icon"><i class="fa-solid fa-gift"></i></div>', $c);
file_put_contents($f, $c);
echo "Fixed points.php lines.";
