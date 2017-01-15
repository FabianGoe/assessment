<?php

namespace FabianGO\Assessment\Factories;

use FabianGO\Assessment\Models\UserModel;

class UserFactory extends BaseFactory
{
    /**
     * Create and save user model
     *
     * @param array $params
     *
     * @return UserModel|null
     */
    public function create(array $params)
    {
        $this->errors = [];

        $user = new UserModel($this->settings);
        $user->initialize($params, true);

        if ($user->getErrors()) {
            $this->errors = $user->getErrors();

            return null;
        }

        $stmt = $this->pdo->prepare("INSERT INTO `users` (
                                `firstname`, 
                                `initials`, 
                                `lastname`, 
                                `postal_code`, 
                                `housenumber`, 
                                `email`, 
                                `phonenumber`,
                                `password`)
                         VALUES (
                                :firstname,
                                :initials,
                                :lastname,
                                :postal_code,
                                :housenumber,
                                :email,
                                :phonenumber,
                                :password);");

        $pdoParams = [
            'firstname' => $user->db_firstname,
            'initials' => $user->db_initials,
            'lastname' => $user->db_lastname,
            'postal_code' => $user->db_postal_code,
            'housenumber' => $user->db_housenumber,
            'email' => $user->db_email,
            'phonenumber' => $user->db_phonenumber,
            'password' => $user->db_password
        ];

        // save user to db
        $stmt->execute($pdoParams);

        $id = $this->pdo->lastInsertId();
        $user->db_id = $id;

        return $user;
    }

    /**
     * Retrieve user by id
     *
     * @param int $id
     * @return UserModel|null
     */
    public function get($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `users` WHERE `id` = :id LIMIT 1");
        $stmt->bindParam('id', $id);

        $found = $stmt->execute();

        if ($found) {
            $user = new UserModel($this->settings);
            $user->initialize($stmt->fetch(), false);

            return $user;
        }

        return null;
    }

    /**
     * Return list of errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}