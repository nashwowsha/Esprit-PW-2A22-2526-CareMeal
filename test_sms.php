<?php
$apiKey = 'vJX1C500XmUjuWaCh2xIsns9X7LLRhwO';

// The URL for sending messages
$url = 'https://rest.smsmode.com/sms/v1/messages';

// The data to be sent
$data = [
    'recipient' => [
        "to" => "+21698133750"
    ],
    'body' => [
        "text" => "Votre code de reinitialisation CareMeal est : 123456."
    ],
    'from' => 'CareMeal'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Api-Key: ' . $apiKey,
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
curl_close($ch);
echo $response;
?>
