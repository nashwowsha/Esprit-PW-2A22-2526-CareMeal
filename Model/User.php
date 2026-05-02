<?php
require_once __DIR__ . '/Profile.php';

class User {
    private $id;
    private $email;
    private $role;
    private $status;
    private $createdAt;
    private $profile;

    public function __construct($id = null, $email = null, $role = null, $status = null, $createdAt = null, $profile = null) {
        $this->id = $id;
        $this->email = $email;
        $this->role = $role;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->profile = $profile; // Object Profile
    }

    // Getters
    public function getId() { return $this->id; }
    public function getEmail() { return $this->email; }
    public function getRole() { return $this->role; }
    public function getStatus() { return $this->status; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getProfile() { return $this->profile; }

    // Setters
    public function setId($id) { $this->id = $id; }
    public function setEmail($email) { $this->email = $email; }
    public function setRole($role) { $this->role = $role; }
    public function setStatus($status) { $this->status = $status; }
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; }
    public function setProfile($profile) { $this->profile = $profile; }

    // Conversion en tableau associatif
    public function toArray() {
        $userArray = [
            'id' => $this->id,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'created_at' => $this->createdAt
        ];

        if ($this->profile) {
            $profileArray = $this->profile->toArray();
            // on évite d'écraser l'id user par l'id profil si on a utilisé array_merge
            // pour être sûr, on unset l'id profil et les id redondants :
            unset($profileArray['id']); 
            unset($profileArray['user_id']);
            unset($profileArray['created_at']);
            
            $userArray = array_merge($userArray, $profileArray);
        }

        return $userArray;
    }
}
?>
