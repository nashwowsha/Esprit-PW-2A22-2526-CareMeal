<?php

class Offer
{
    public ?int    $id_offre        = null;
    public string  $titre           = '';
    public string  $description     = '';
    public float   $prix            = 0.0;
    public float   $prix_original   = 0.0;
    public ?string $photo_url       = null;
    public int     $quantite        = 0;
    public ?string $heure_debut     = null;
    public ?string $heure_fin       = null;
    public string  $statut          = 'publiée';
    public ?int    $id_categorie    = null;
    public ?string $id_partenaire   = null;
    public ?string $date_creation   = null;

    // Champs joints (categorie_offre)
    public ?string $nom_categorie   = null;
    public ?string $icone           = null;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}