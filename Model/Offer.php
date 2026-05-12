<?php

class OfferModel
{
    private ?int $idOffre = null;
    private string $titre = '';
    private string $description = '';
    private float $prix = 0.0;
    private float $prixOriginal = 0.0;
    private string $photoUrl = '';
    private int $quantite = 0;
    private ?string $heureDebut = null;
    private ?string $heureFin = null;
    private string $statut = 'publiée';
    private ?int $idCategorie = null;
    private ?string $idPartenaire = null;

    public function getIdOffre(): ?int
    {
        return $this->idOffre;
    }

    public function setIdOffre(?int $idOffre): self
    {
        $this->idOffre = $idOffre;
        return $this;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = trim($titre);
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = trim($description);
        return $this;
    }

    public function getPrix(): float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    public function getPrixOriginal(): float
    {
        return $this->prixOriginal;
    }

    public function setPrixOriginal(float $prixOriginal): self
    {
        $this->prixOriginal = $prixOriginal;
        return $this;
    }

    public function getPhotoUrl(): string
    {
        return $this->photoUrl;
    }

    public function setPhotoUrl(string $photoUrl): self
    {
        $this->photoUrl = trim($photoUrl);
        return $this;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): self
    {
        $this->quantite = $quantite;
        return $this;
    }

    public function getHeureDebut(): ?string
    {
        return $this->heureDebut;
    }

    public function setHeureDebut(?string $heureDebut): self
    {
        $this->heureDebut = $heureDebut;
        return $this;
    }

    public function getHeureFin(): ?string
    {
        return $this->heureFin;
    }

    public function setHeureFin(?string $heureFin): self
    {
        $this->heureFin = $heureFin;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getIdCategorie(): ?int
    {
        return $this->idCategorie;
    }

    public function setIdCategorie(?int $idCategorie): self
    {
        $this->idCategorie = $idCategorie;
        return $this;
    }

    public function getIdPartenaire(): ?string
    {
        return $this->idPartenaire;
    }

    public function setIdPartenaire(?string $idPartenaire): self
    {
        $this->idPartenaire = $idPartenaire;
        return $this;
    }
}
