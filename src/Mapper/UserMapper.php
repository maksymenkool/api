<?php

namespace Mapper;
use PDO;

class UserMapper extends AbstractMapper
{
    /**
     * @var string
     */
    private $salt;

    /**
     * @param string $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * @param string $email
     * @return bool
     */
    public function hasEmail($email)
    {
        $select = $this->pdo->prepare('SELECT id FROM users WHERE email=:email');
        $select->execute([':email' => $email]);
        return $select->rowCount() == 1;
    }

    /**
     * @param $email
     * @param $password
     * @return string
     */
    public function getPasswordHash($email, $password)
    {
        return sha1($email . $password . $this->salt);
    }

    /**
     * Insert a new user
     * @param array $user
     * @return string user id
     */
    public function createUser(array $user)
    {
        $insert = $this->pdo->prepare('INSERT INTO users'
            . '("email", "password", "first_name", "last_name", "picture")'
            . 'VALUES (:email, :password, :first_name, :last_name, :picture) RETURNING id');
        $insert->execute([
            ':email' => $user['email'],
            ':password' => $this->getPasswordHash($user['email'], $user['password']),
            ':first_name' => $user['first_name'],
            ':last_name' => $user['last_name'],
            ':picture' => $user['picture'],
        ]);
        return $insert->fetchColumn();
    }

    /**
     * @param $email
     * @param $password
     * @return array|null
     */
    public function fetchByEmailAndPassword($email, $password)
    {
        $select = $this->pdo->prepare('SELECT * FROM users WHERE "email" = :email AND "password" = :password');
        $select->execute([
            ':email' => $email,
            ':password' => $this->getPasswordHash($email, $password),
        ]);

        return $select->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * @param $id
     * @return array|null
     */
    public function fetchById($id)
    {
        $select = $this->pdo->prepare('SELECT * FROM users WHERE "id" = :id');
        $select->execute([
            ':id' => $id,
        ]);

        return $select->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * @param $email
     * @return array|null
     */
    public function fetchByEmail($email)
    {
        $select = $this->pdo->prepare('SELECT * FROM users WHERE "email" = :email');
        $select->execute([
            ':email' => $email,
        ]);

        return $select->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}