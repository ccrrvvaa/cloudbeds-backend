<?php

namespace App\services;

use App\models\Ent;
use App\repositories\EntRepository;

class EntService
{
    protected $repository;
    
    /**
     * Contructor for dependency injection. The idea is to use this to pass a mock
     * @param EntRepository $repository
     */
    public function __construct($repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return EntRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Gets all Ent models
     * @return Ent[]
     */
    public function findAll(): array
    {
        return $this->repository->findAll();
    }

    public function get(int $id): Ent
    {
        return $this->repository->get($id);
    }

    public function insert(Ent $ent): bool
    {
        $crossing = $this->repository->findCrossing($ent);

        if (count($crossing) > 0) {
            foreach ($crossing as $dbEnt) {
                if ($ent->startDate <= $dbEnt->startDate && $ent->endDate >= $dbEnt->endDate) {
                    // 1st case $ent encloses $dbEnt
                } elseif ($ent->startDate <= $dbEnt->startDate && $ent->endDate < $dbEnt->endDate) {
                    // 2nd case $ent crosses with $dbEnt->startDate
                } elseif ($ent->startDate > $dbEnt->startDate && $ent->endDate < $dbEnt->endDate) {
                    // 3rd case $ent is included in $dbEnt
                    if ($ent->price == $dbEnt->price) {

                    } else {
                        $newEntEndDate = clone $dbEnt->endDate;

                        $dbEntEndDate = clone $ent->startDate;
                        $dbEntEndDate->sub(new \DateInterval('P1D')); // substracts 1 day
                        $dbEnt->endDate = $dbEntEndDate;
                        $this->repository->update($dbEnt);

                        $this->repository->insert($ent);

                        $newEntStartDate = clone $ent->endDate;
                        $newEntStartDate->add(new \DateInterval('P1D')); // adds 1 day
                        $this->repository->insert(new Ent($newEntStartDate, $newEntEndDate, $dbEnt->price));
                    }
                } elseif ($ent->startDate > $dbEnt->startDate && $ent->endDate >= $dbEnt->endDate) {
                    // 4th case $ent crosses with $dbEnt->endDate
                    if ($ent->price == $dbEnt->price) {
                        $ent->startDate = $dbEnt->startDate;

                        $this->repository->delete($dbEnt->id);
                        $this->repository->insert($ent);
                    } else {

                    }
                }
            }

            return true;
        } else {
            return $this->repository->insert($ent);
        }
    }

    public function update(Ent $ent): bool
    {
        return $this->repository->update($ent);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function deleteAll(): bool
    {
        return $this->repository->deleteAll();
    }
}