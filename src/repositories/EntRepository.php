<?php

namespace App\repositories;

use App\models\Ent;

class EntRepository
{
    protected $connection;
    
    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Extracts an Ent model from a database row
     * @param array $row
     * @return Ent
     */
    private function extractEntFromRow($row): Ent
    {
        $ent = new Ent(\DateTime::createFromFormat('Y-m-d',  $row['date_start']), 
                        \DateTime::createFromFormat('Y-m-d',  $row['date_end']), 
                        floatval($row['price']));
        $ent->id = floatval($row['id']);

        return $ent;
    }

    /**
     * @return Ent[]
     */
    public function findAll(): array
    {
        $result = [];

        $sql = 'SELECT * FROM ent ORDER BY date_start';

        foreach ($this->connection->query($sql) as $row)
            $result[] = $this->extractEntFromRow($row);

        return $result;
    }

    /**
     * @param int $id
     * @return Ent
     */
    public function get(int $id): Ent
    {
        return new Ent(new \DateTime('now'), new \DateTime('tomorrow'), 1);
    }

    /**
     * Gets a list of Ents which cross intervals with parameters or has a possible merge intervals
     * @param Ent $newEnt
     * @return Ent|null
     */
    public function findCrossing(Ent $newEnt)
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM ent WHERE (? >= date_start AND ? <= date_end)
                OR (? < date_start AND ? = price AND DATEDIFF(date_start, ?) = 1)
                OR (? > date_end AND ? = price AND DATEDIFF(?, date_end) = 1)
             ORDER BY date_start"
        );

        $stmt->execute([$newEnt->endDate->format('Y-m-d'), $newEnt->startDate->format('Y-m-d'),
                        $newEnt->endDate->format('Y-m-d'), $newEnt->price, $newEnt->endDate->format('Y-m-d'),
                        $newEnt->startDate->format('Y-m-d'), $newEnt->price, $newEnt->startDate->format('Y-m-d')]);

        if ($stmt->rowCount() == 0) return null;

        return  $this->extractEntFromRow($stmt->fetch());
    }

    public function insert(Ent $ent): bool
    {
        $sql = "INSERT INTO ent (date_start, date_end, price) VALUES (?, ?, ?)";
        $stmt = $this->connection->prepare($sql);

        $executed = $stmt->execute([$ent->startDate->format('Y-m-d'), $ent->endDate->format('Y-m-d'), $ent->price]);

        $ent->id = $this->connection->lastInsertId();

        return $executed;
    }

    public function update(Ent $ent): bool
    {
        $sql = "UPDATE ent SET date_start = ?, date_end = ?, price = ? WHERE id = ?";
        $stmt = $this->connection->prepare($sql);

        return $stmt->execute([$ent->startDate->format('Y-m-d'), $ent->endDate->format('Y-m-d'), $ent->price, $ent->id]);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM ent WHERE id = ?";
        $stmt = $this->connection->prepare($sql);

        return $stmt->execute([$id]);
    }

    public function deleteAll(): bool
    {
        $sql = "DELETE FROM ent; TRUNCATE TABLE ent;";
        $stmt = $this->connection->prepare($sql);

        return $stmt->execute();
    }
}