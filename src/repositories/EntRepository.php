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
     * @return Ent[]
     */
    public function findAll(): array
    {
        return [];
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
     * Gets a list of Ents which cross intervals with parameters
     * @param Ent $newEnt
     * @return Ent[]
     */
    public function findCrossing(Ent $newEnt): array
    {
        return [];
    }

    public function insert(Ent $ent): bool
    {
        return true;
    }

    public function update(Ent $ent): bool
    {
        return true;
    }

    public function delete(int $id): bool
    {
        return true;
    }

    public function deleteAll(): bool
    {
        return true;
    }
}