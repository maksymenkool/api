<?php
namespace Api\Mapper\DB;

use Api\DB\AbstractMapper;
use Api\Model\Travel\Travel;
use Doctrine\DBAL\Driver\Statement;

/**
 * Class TravelMapper
 * @package Api\Mapper\DB
 */
class TravelMapper extends AbstractMapper
{
    /**
     * @var UserMapper
     */
    private $user_mapper;

    /**
     * @var CategoryMapper
     */
    private $category_mapper;

    /**
     * @var ActionMapper
     */
    private $action_mapper;

    /**
     * @param UserMapper $user_mapper
     */
    public function setUserMapper(UserMapper $user_mapper)
    {
        $this->user_mapper = $user_mapper;
    }

    /**
     * @param CategoryMapper $category_mapper
     */
    public function setCategoryMapper(CategoryMapper $category_mapper)
    {
        $this->category_mapper = $category_mapper;
    }

    /**
     * @param ActionMapper $action_mapper
     */
    public function setActionMapper(ActionMapper $action_mapper)
    {
        $this->action_mapper = $action_mapper;
    }

    /**
     * @param int $id
     * @return Travel|null
     */
    public function fetchById(int $id)
    {
        $select = $this->connection->prepare(
            'SELECT t.*, u.* FROM travels t JOIN users u ON t.author_id = u.id WHERE t.id = :id AND NOT deleted'
        );
        $select->execute(['id' => $id]);
        $row = $select->fetch(\PDO::FETCH_NAMED);
        if (empty($row)) {
            return null;
        }
        $travel = $this->build($row);
        $travel->setActions($this->action_mapper->fetchActionsForTravel($id));
        return $travel;
    }

    /**
     * @param int  $author_id
     * @param int  $limit
     * @param int  $offset
     * @param bool $is_published
     * @return Travel[]
     */
    public function fetchByAuthorId(int $author_id, int $limit, int $offset, bool $is_published = null): array
    {
        $select = $this->connection->prepare(
            'SELECT t.*, u.* FROM travels t
             JOIN users u ON t.author_id = u.id
             WHERE t.author_id = :userId AND NOT deleted '
            . ($is_published !== null ? 'AND is_published = :is_published ' : '')
            . 'ORDER BY t.id DESC LIMIT :limit OFFSET :offset'
        );

        $params = [
            'userId'  => $author_id,
            ':limit'  => $limit,
            ':offset' => $offset,
        ];
        if ($is_published !== null) {
            $params['is_published'] = $is_published;
        }
        $select->execute($params);
        return $this->buildAll($select);
    }

    /**
     * Fetch published travels of the given author
     * @param int $author_id
     * @param int $limit
     * @param int $offset
     * @return Travel[]
     */
    public function fetchPublishedByAuthorId(int $author_id, int $limit, int $offset): array
    {
        $is_published = true;
        return $this->fetchByAuthorId($author_id, $limit, $offset, $is_published);
    }

    /**
     * Insert into DB, update id
     *
     * @param Travel $travel
     */
    public function insert(Travel $travel)
    {
        $insert = $this->connection->prepare('
          INSERT INTO travels (
            title, description, is_published, image, author_id,
            creation_mode, estimated_price, transportation, app_version
          ) VALUES (
            :title, :description,:published, :image, :author_id,
            :creation_mode, :estimated_price, :transportation, :app_version
          ) RETURNING id, created
        ');
        $this->bindCommonValues($insert, $travel);
        $insert->execute();
        $row = $insert->fetch(\PDO::FETCH_ASSOC);
        $travel->setId($row['id']);
        $travel->setCreated(new \DateTime($row['created']));

        $this->category_mapper->setTravelCategories($travel->getId(), $travel->getCategoryIds());
    }

    /**
     * @param int $id
     */
    public function delete(int $id)
    {
        $this->connection
            ->prepare("DELETE FROM travels WHERE id = :id")
            ->execute([':id' => $id]);
    }

    /**
     * @param int $user_id
     * @return int[]
     */
    public function fetchFavoriteIds(int $user_id): array
    {
        $select = $this->connection->prepare('
            SELECT travel_id FROM  favorite_travels
            WHERE user_id = :user_id
            ');
        $select->execute([
            ':user_id' => $user_id,
        ]);

        $ids = [];
        while (false !== $id = $select->fetchColumn()) {
            $ids[$id] = $id;
        }
        return $ids;
    }

    /**
     * @param int $travel_id
     * @param int $user_id
     */
    public function addFavorite(int $travel_id, int $user_id)
    {
        $this->connection->prepare('
            INSERT INTO favorite_travels (user_id, travel_id)
            VALUES (:user_id, :travel_id) ON CONFLICT DO NOTHING
        ')->execute([
            ':user_id'   => $user_id,
            ':travel_id' => $travel_id,
        ]);
    }

    /**
     * @param int $travel_id
     * @param int $user_id
     */
    public function removeFavorite(int $travel_id, int $user_id)
    {
        $this->connection
            ->prepare('DELETE FROM favorite_travels WHERE user_id = :user_id AND travel_id = :travel_id')
            ->execute([
                ':user_id'   => $user_id,
                ':travel_id' => $travel_id,
            ]);
    }

    /**
     * @param int $user_id
     * @return Travel[]
     */
    public function fetchFavorites(int $user_id): array
    {
        $select = $this->connection->prepare('
            SELECT t.*, u.* FROM  favorite_travels ft
            JOIN travels t ON ft.travel_id = t.id AND NOT t.deleted
            JOIN users u ON t.author_id = u.id
            WHERE ft.user_id = :user_id
        ');
        $select->execute(['user_id' => $user_id]);
        return $this->buildAll($select);
    }

    /**
     * @param string $name
     * @param int    $limit
     * @param int    $offset
     * @return Travel[]
     */
    public function fetchByCategory(string $name, int $limit, int $offset): array
    {
        $select = $this->connection->prepare('
            SELECT t.*, u.* FROM travel_categories ct
            JOIN travels t ON ct.travel_id = t.id AND NOT t.deleted
            JOIN categories c ON ct.category_id = c.id
            JOIN users u ON u.id = t.author_id
            WHERE c.name = :name
            LIMIT :limit OFFSET :offset
        ');
        $select->execute([
            'name'    => $name,
            ':limit'  => $limit,
            ':offset' => $offset,
        ]);
        return $this->buildAll($select);
    }

    /**
     * @param string $name
     * @param int    $limit
     * @param int    $offset
     * @return Travel[]
     */
    public function fetchPublishedByCategory(string $name, int $limit, int $offset): array
    {
        $select = $this->connection->prepare('
            SELECT t.*, u.* FROM travel_categories ct
            JOIN travels t ON ct.travel_id = t.id AND NOT t.deleted
            JOIN categories c ON ct.category_id = c.id
            JOIN users u ON u.id = t.author_id
            WHERE c.name = :name AND is_published
            LIMIT :limit OFFSET :offset
        ');
        $select->execute([
            'name'    => $name,
            ':limit'  => $limit,
            ':offset' => $offset,
        ]);
        return $this->buildAll($select);
    }

    /**
     * Travels search by price and length
     *
     * @param int   $price_from
     * @param int   $price_to
     * @param int   $length_from
     * @param int   $length_to
     * @param array $category_ids
     * @param int   $limit
     * @param int   $offset
     * @return Travel[]
     */
    public function fetchTravelsByPriceByLength(
        int $price_from = null,
        int $price_to = null,
        int $length_from = null,
        int $length_to = null,
        array $category_ids = [],
        int $limit = 10,
        int $offset = 0
    ): array {
        $params = [
            ':limit'  => $limit,
            ':offset' => $offset,
        ];
        $tables = ['travels t', 'users u ON t.author_id = u.id'];
        $order_items = ['t.estimated_price ASC'];
        $conditions = ['is_published'];
        if ($price_from !== null) {
            $params[':price_from'] = $price_from;
            $conditions[] = 't.estimated_price >= :price_from';
        }
        if ($price_to !== null) {
            $params[':price_to'] = $price_to;
            $conditions[] = 't.estimated_price <= :price_to';
        }
        if ($length_from !== null) {
            $params[':length_from'] = $length_from;
            $conditions[] = 'days_count >= :length_from';
        }
        if ($length_to !== null) {
            $params[':length_to'] = $length_to;
            $conditions[] = 'days_count <= :length_to';
        }
        $tables[] = "
        (
            SELECT travel_id, MAX(offset_end) AS days_count
            FROM actions GROUP BY travel_id
        ) AS ac ON t.id = ac.travel_id
        ";
        if ($category_ids) {
            $sql_list = $this->helper->generateInExpression($category_ids, 'cat', $params);
            $tables[] = "
            (
                SELECT travel_id, COUNT(category_id) AS c
                FROM travel_categories 
                WHERE category_id IN $sql_list
                GROUP BY travel_id
            ) AS cat ON cat.travel_id = t.id 
            ";
            array_unshift($order_items, 'cat.c DESC');
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $from = implode(' JOIN ', $tables);
        $order = implode(', ', $order_items);
        $select = $this->connection->prepare("
          SELECT 
            t.*, 
            u.*
          FROM {$from}
          {$where}
          ORDER BY {$order} 
          LIMIT :limit 
          OFFSET :offset
        ");
        $select->execute($params);
        return $this->buildAll($select);
    }

    /**
     * Update title and description in DB
     *
     * @param Travel $travel
     */
    public function update(Travel $travel)
    {
        $update = $this->connection->prepare('
            UPDATE travels SET
            title = :title,
            description = :description,
            is_published = :published,
            image = :image,
            author_id = :author_id,
            creation_mode = :creation_mode,
            estimated_price = :estimated_price,
            transportation = :transportation,
            app_version = :app_version
            WHERE id = :id
        ');
        $this->bindCommonValues($update, $travel);
        $update->bindValue('id', $travel->getId(), \PDO::PARAM_INT);
        $update->execute();
    }

    public function markDeleted(int $travelId, bool $deleted = true)
    {
        $update = $this->connection->prepare('
          UPDATE travels SET
            deleted = :deleted
          WHERE id = :id
        ');
        $values = [
            'id'      => $travelId,
            'deleted' => $deleted,
        ];
        $this->helper->bindValues($update, $values);
        $update->execute();
    }

    /**
     * @param array $row
     * @return Travel
     */
    protected function create(array $row)
    {
        $travel = new Travel();
        $travel->setId($row['id']);
        $travel->setDescription($row['description']);
        $travel->setTitle($row['title']);
        $travel->setPublished($row['is_published']);
        $travel->setImage($row['image']);
        $travel->setCreated(new \DateTime($row['created']));
        $travel->setCreationMode($row['creation_mode']);
        $travel->setEstimatedPrice($row['estimated_price']);
        $travel->setTransportation($row['transportation']);
        $travel->setAppVersion($row['app_version']);
        $categories = $this->category_mapper->fetchByTravelId($travel->getId());
        if (count($categories)) {
            foreach ($categories as $category) {
                $category_ids[] = $category->getId();
            }
            $travel->setCategoryIds($category_ids);
        }
        return $travel;
    }

    /**
     * @param Statement $statement
     * @param Travel       $travel
     */
    private function bindCommonValues(Statement $statement, Travel $travel)
    {
        $values = [
            'title'           => $travel->getTitle(),
            'description'     => $travel->getDescription(),
            'published'       => $travel->isPublished(),
            'image'           => $travel->getImage(),
            'author_id'       => $travel->getAuthorId(),
            'creation_mode'   => $travel->getCreationMode(),
            'estimated_price' => $travel->getEstimatedPrice(),
            'transportation'  => $travel->getTransportation(),
            'app_version'     => $travel->getAppVersion(),
        ];
        $this->helper->bindValues($statement, $values);
    }

    /**
     * @param array $row
     * @return Travel
     */
    protected function build(array $row): Travel
    {
        list($travel, $author) = $this->createFromJoined($row, $this, $this->user_mapper);
        $travel->setAuthor($author);
        $travel->setActions($this->action_mapper->fetchActionsForTravel($travel->getId()));
        return $travel;
    }
}
