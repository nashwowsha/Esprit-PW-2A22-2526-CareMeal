<?php
$target = '/caremeal/student/matching_restaurant.php';
if (!empty($_SERVER['QUERY_STRING'])) {
    $target .= '?' . $_SERVER['QUERY_STRING'];
}

header('Location: ' . $target);
exit;
