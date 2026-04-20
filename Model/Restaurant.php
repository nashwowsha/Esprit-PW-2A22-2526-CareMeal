<?php

class Restaurant
{
    private $idRestaurant;
    private $idOwner;
    private $nom;
    private $localisation;
    private $imagePath;
    private $description;
    private $telephone;
    private $horaires;
    private $actif;
    private $mealsJson;
    private $createdAt;
    private $updatedAt;

    public function getIdRestaurant()
    {
        return $this->idRestaurant;
    }

    public function setIdRestaurant($idRestaurant)
    {
        $this->idRestaurant = (int)$idRestaurant;
    }

    public function getIdOwner()
    {
        return $this->idOwner;
    }

    public function setIdOwner($idOwner)
    {
        $this->idOwner = (int)$idOwner;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function setNom($nom)
    {
        $this->nom = (string)$nom;
    }

    public function getLocalisation()
    {
        return $this->localisation;
    }

    public function setLocalisation($localisation)
    {
        $this->localisation = (string)$localisation;
    }

    public function getImagePath()
    {
        return $this->imagePath;
    }

    public function setImagePath($imagePath)
    {
        $this->imagePath = (string)$imagePath;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = (string)$description;
    }

    public function getTelephone()
    {
        return $this->telephone;
    }

    public function setTelephone($telephone)
    {
        $this->telephone = (string)$telephone;
    }

    public function getHoraires()
    {
        return $this->horaires;
    }

    public function setHoraires($horaires)
    {
        $this->horaires = (string)$horaires;
    }

    public function getActif()
    {
        return $this->actif;
    }

    public function setActif($actif)
    {
        $this->actif = (int)$actif;
    }

    public function getMealsJson()
    {
        return $this->mealsJson;
    }

    public function setMealsJson($mealsJson)
    {
        $this->mealsJson = (string)$mealsJson;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = (string)$createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = (string)$updatedAt;
    }

    public function toArray()
    {
        return [
            'id_restaurant' => $this->idRestaurant,
            'id_owner' => $this->idOwner,
            'nom' => $this->nom,
            'localisation' => $this->localisation,
            'image_path' => $this->imagePath,
            'description' => $this->description,
            'telephone' => $this->telephone,
            'horaires' => $this->horaires,
            'actif' => $this->actif,
            'meals_json' => $this->mealsJson,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public static function fromArray($row)
    {
        if (!is_array($row) || empty($row)) {
            return null;
        }

        $restaurant = new self();
        $restaurant->setIdRestaurant($row['id_restaurant'] ?? 0);
        $restaurant->setIdOwner($row['id_owner'] ?? 0);
        $restaurant->setNom($row['nom'] ?? '');
        $restaurant->setLocalisation($row['localisation'] ?? '');
        $restaurant->setImagePath($row['image_path'] ?? '');
        $restaurant->setDescription($row['description'] ?? '');
        $restaurant->setTelephone($row['telephone'] ?? '');
        $restaurant->setHoraires($row['horaires'] ?? '');
        $restaurant->setActif($row['actif'] ?? 1);
        $restaurant->setMealsJson($row['meals_json'] ?? '[]');
        $restaurant->setCreatedAt($row['created_at'] ?? '');
        $restaurant->setUpdatedAt($row['updated_at'] ?? '');

        return $restaurant;
    }
}

