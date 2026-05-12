<?php

class PlanningCollecte
{
    private $idCollecte;
    private $idRestaurant;
    private $idPref;
    private $idUser;
    private $modeCollecte;
    private $adresseLivraison;
    private $prefRegimeSnapshot;
    private $prefAllergiesSnapshot;
    private $prefLocalisationSnapshot;
    private $heureDemande;
    private $heureSouhaitee;
    private $statut;
    private $itemsJson;
    private $montantTotal;
    private $createdAt;
    private $updatedAt;

    public function getIdCollecte() { return $this->idCollecte; }
    public function setIdCollecte($value) { $this->idCollecte = (int)$value; }

    public function getIdRestaurant() { return $this->idRestaurant; }
    public function setIdRestaurant($value) { $this->idRestaurant = (int)$value; }

    public function getIdPref() { return $this->idPref; }
    public function setIdPref($value) { $this->idPref = (int)$value; }

    public function getIdUser() { return $this->idUser; }
    public function setIdUser($value) { $this->idUser = (int)$value; }

    public function getModeCollecte() { return $this->modeCollecte; }
    public function setModeCollecte($value) { $this->modeCollecte = (string)$value; }

    public function getAdresseLivraison() { return $this->adresseLivraison; }
    public function setAdresseLivraison($value) { $this->adresseLivraison = $value === null ? null : (string)$value; }

    public function getPrefRegimeSnapshot() { return $this->prefRegimeSnapshot; }
    public function setPrefRegimeSnapshot($value) { $this->prefRegimeSnapshot = (string)$value; }

    public function getPrefAllergiesSnapshot() { return $this->prefAllergiesSnapshot; }
    public function setPrefAllergiesSnapshot($value) { $this->prefAllergiesSnapshot = (string)$value; }

    public function getPrefLocalisationSnapshot() { return $this->prefLocalisationSnapshot; }
    public function setPrefLocalisationSnapshot($value) { $this->prefLocalisationSnapshot = (string)$value; }

    public function getHeureDemande() { return $this->heureDemande; }
    public function setHeureDemande($value) { $this->heureDemande = (string)$value; }

    public function getHeureSouhaitee() { return $this->heureSouhaitee; }
    public function setHeureSouhaitee($value) { $this->heureSouhaitee = (string)$value; }

    public function getStatut() { return $this->statut; }
    public function setStatut($value) { $this->statut = (string)$value; }

    public function getItemsJson() { return $this->itemsJson; }
    public function setItemsJson($value) { $this->itemsJson = (string)$value; }

    public function getMontantTotal() { return $this->montantTotal; }
    public function setMontantTotal($value) { $this->montantTotal = (float)$value; }

    public function getCreatedAt() { return $this->createdAt; }
    public function setCreatedAt($value) { $this->createdAt = (string)$value; }

    public function getUpdatedAt() { return $this->updatedAt; }
    public function setUpdatedAt($value) { $this->updatedAt = (string)$value; }

    public function toArray()
    {
        return [
            'id_collecte' => $this->idCollecte,
            'id_restaurant' => $this->idRestaurant,
            'id_pref' => $this->idPref,
            'id_user' => $this->idUser,
            'mode_collecte' => $this->modeCollecte,
            'adresse_livraison' => $this->adresseLivraison,
            'pref_regime_snapshot' => $this->prefRegimeSnapshot,
            'pref_allergies_snapshot' => $this->prefAllergiesSnapshot,
            'pref_localisation_snapshot' => $this->prefLocalisationSnapshot,
            'heure_demande' => $this->heureDemande,
            'heure_souhaitee' => $this->heureSouhaitee,
            'statut' => $this->statut,
            'items_json' => $this->itemsJson,
            'montant_total' => $this->montantTotal,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public static function fromArray($row)
    {
        if (!is_array($row) || empty($row)) {
            return null;
        }

        $entity = new self();
        $entity->setIdCollecte($row['id_collecte'] ?? 0);
        $entity->setIdRestaurant($row['id_restaurant'] ?? 0);
        $entity->setIdPref($row['id_pref'] ?? 0);
        $entity->setIdUser($row['id_user'] ?? 0);
        $entity->setModeCollecte($row['mode_collecte'] ?? '');
        $entity->setAdresseLivraison($row['adresse_livraison'] ?? null);
        $entity->setPrefRegimeSnapshot($row['pref_regime_snapshot'] ?? '');
        $entity->setPrefAllergiesSnapshot($row['pref_allergies_snapshot'] ?? '');
        $entity->setPrefLocalisationSnapshot($row['pref_localisation_snapshot'] ?? '');
        $entity->setHeureDemande($row['heure_demande'] ?? '');
        $entity->setHeureSouhaitee($row['heure_souhaitee'] ?? '');
        $entity->setStatut($row['statut'] ?? '');
        $entity->setItemsJson($row['items_json'] ?? '[]');
        $entity->setMontantTotal($row['montant_total'] ?? 0);
        $entity->setCreatedAt($row['created_at'] ?? '');
        $entity->setUpdatedAt($row['updated_at'] ?? '');
        return $entity;
    }
}
