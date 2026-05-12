<?php
$ch = curl_init('https://such-bash-crewmate.ngrok-free.dev/Controller/ChatbotController.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, '{"message":"bonjour","history":[],"user_id":2,"user_role":"student"}');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 25);
$r = curl_exec($ch);
echo $r;

