<?php
class Profile {
    private $id;
    private $user_id;
    private $nom;
    private $prenom;
    private $telephone;
    private $avatar;
    private $linkedin;
    private $instagram;
    private $facebook;
    private $twitter;
    private $github;
    private $ecole;
    private $annee_etude;
    private $quartier;
    private $points_accumules;
    private $nom_entreprise;
    private $siret;
    private $description;
    private $site_web;
    private $secteur_activite;
    private $face_descriptor;
    private $created_at;

    public function __construct($data = []) {
        $this->id = $data['id'] ?? null;
        $this->user_id = $data['user_id'] ?? null;
        $this->nom = $data['nom'] ?? null;
        $this->prenom = $data['prenom'] ?? null;
        $this->telephone = $data['telephone'] ?? null;
        $this->avatar = $data['avatar'] ?? null;
        $this->linkedin = $data['linkedin'] ?? null;
        $this->instagram = $data['instagram'] ?? null;
        $this->facebook = $data['facebook'] ?? null;
        $this->twitter = $data['twitter'] ?? null;
        $this->github = $data['github'] ?? null;
        $this->ecole = $data['ecole'] ?? null;
        $this->annee_etude = $data['annee_etude'] ?? null;
        $this->quartier = $data['quartier'] ?? null;
        $this->points_accumules = $data['points_accumules'] ?? 0;
        $this->nom_entreprise = $data['nom_entreprise'] ?? null;
        $this->siret = $data['siret'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->site_web = $data['site_web'] ?? null;
        $this->secteur_activite = $data['secteur_activite'] ?? null;
        $this->face_descriptor = $data['face_descriptor'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getUserId() { return $this->user_id; }
    public function getNom() { return $this->nom; }
    public function getPrenom() { return $this->prenom; }
    public function getTelephone() { return $this->telephone; }
    public function getAvatar() { return $this->avatar; }
    public function getLinkedin() { return $this->linkedin; }
    public function getInstagram() { return $this->instagram; }
    public function getFacebook() { return $this->facebook; }
    public function getTwitter() { return $this->twitter; }
    public function getGithub() { return $this->github; }
    public function getEcole() { return $this->ecole; }
    public function getAnneeEtude() { return $this->annee_etude; }
    public function getQuartier() { return $this->quartier; }
    public function getPointsAccumules() { return $this->points_accumules; }
    public function getNomEntreprise() { return $this->nom_entreprise; }
    public function getSiret() { return $this->siret; }
    public function getDescription() { return $this->description; }
    public function getSiteWeb() { return $this->site_web; }
    public function getSecteurActivite() { return $this->secteur_activite; }
    public function getFaceDescriptor() { return $this->face_descriptor; }
    public function getCreatedAt() { return $this->created_at; }

    // Setters
    public function setId($id) { $this->id = $id; }
    public function setUserId($user_id) { $this->user_id = $user_id; }
    public function setNom($nom) { $this->nom = $nom; }
    public function setPrenom($prenom) { $this->prenom = $prenom; }
    public function setTelephone($tel) { $this->telephone = $tel; }
    public function setAvatar($avatar) { $this->avatar = $avatar; }
    public function setLinkedin($linkedin) { $this->linkedin = $linkedin; }
    public function setInstagram($instagram) { $this->instagram = $instagram; }
    public function setFacebook($facebook) { $this->facebook = $facebook; }
    public function setTwitter($twitter) { $this->twitter = $twitter; }
    public function setGithub($github) { $this->github = $github; }
    public function setEcole($ecole) { $this->ecole = $ecole; }
    public function setAnneeEtude($annee) { $this->annee_etude = $annee; }
    public function setQuartier($quartier) { $this->quartier = $quartier; }
    public function setPointsAccumules($points) { $this->points_accumules = $points; }
    public function setNomEntreprise($nomEntreprise) { $this->nom_entreprise = $nomEntreprise; }
    public function setSiret($siret) { $this->siret = $siret; }
    public function setDescription($description) { $this->description = $description; }
    public function setSiteWeb($siteWeb) { $this->site_web = $siteWeb; }
    public function setSecteurActivite($secteur) { $this->secteur_activite = $secteur; }
    public function setFaceDescriptor($descriptor) { $this->face_descriptor = $descriptor; }
    public function setCreatedAt($created_at) { $this->created_at = $created_at; }

    public function toArray() {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'telephone' => $this->telephone,
            'avatar' => $this->avatar,
            'linkedin' => $this->linkedin,
            'instagram' => $this->instagram,
            'facebook' => $this->facebook,
            'twitter' => $this->twitter,
            'github' => $this->github,
            'ecole' => $this->ecole,
            'annee_etude' => $this->annee_etude,
            'quartier' => $this->quartier,
            'points_accumules' => $this->points_accumules,
            'nom_entreprise' => $this->nom_entreprise,
            'siret' => $this->siret,
            'description' => $this->description,
            'site_web' => $this->site_web,
            'secteur_activite' => $this->secteur_activite,
            'face_descriptor' => $this->face_descriptor,
            'created_at' => $this->created_at
        ];
    }
}
?>
