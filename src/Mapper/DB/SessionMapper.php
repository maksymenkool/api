<?php
namespace Api\Mapper\DB;

use Doctrine\DBAL\Driver\Connection;

class SessionMapper
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param int    $user_id
     * @param string $token
     * @param string $device
     * @return int session id
     */
    public function createSession(int $user_id, string $token, string $device = null): int
    {
        $sql = 'INSERT INTO sessions (user_id, token, device) VALUES (:user_id, :token, :device) RETURNING id';
        $insert = $this->connection->prepare($sql);
        $insert->execute(
            [
                ':user_id' => $user_id,
                ':token'   => $token,
                ':device'  => $device,
            ]
        );
        return $insert->fetchColumn();
    }

    /**
     * @param int    $id
     * @param string $token
     * @return int|false
     */
    public function getUserId(int $id, string $token)
    {
        $select = $this->connection->prepare('SELECT user_id FROM sessions WHERE id = :id AND token = :token');
        $select->execute([
            ':id'    => $id,
            ':token' => $token,
        ]);
        return $select->fetchColumn(0) ?: null;
    }
}
