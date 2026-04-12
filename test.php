<?php
require_once 'Model/Offer.php';
$model  = new OfferModel();
$offers = $model->getAll();
echo "<pre>";
print_r($offers);
echo "</pre>";