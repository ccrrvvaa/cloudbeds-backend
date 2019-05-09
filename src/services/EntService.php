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

    /**
     * Inserts a new Ent, and does the needed transformation to data in order to comply all the test task
     * @param Ent $ent
     * @return bool
     */
    public function insert(Ent $ent): bool
    {
        $dbEnt = $this->repository->findCrossing($ent);

        while ($dbEnt) {
            if ($ent->endDate < $dbEnt->startDate) {
                // 1st merge case when $ent is at left side
                $ent->endDate = $dbEnt->endDate;

                $this->repository->delete($dbEnt->id);
            } elseif ($ent->startDate > $dbEnt->endDate) {
                // 2nd merge case when $ent is at left side
                $ent->startDate = $dbEnt->startDate;

                $this->repository->delete($dbEnt->id);
            } elseif ($ent->startDate <= $dbEnt->startDate && $ent->endDate >= $dbEnt->endDate) {
                // 1st crossing case $ent encloses $dbEnt
                $this->repository->delete($dbEnt->id);
            } elseif ($ent->startDate <= $dbEnt->startDate && $ent->endDate < $dbEnt->endDate) {
                // 2nd crossing case $ent crosses with $dbEnt->startDate
                if ($ent->price == $dbEnt->price) {
                    $ent->endDate = $dbEnt->endDate;

                    $this->repository->delete($dbEnt->id);
                } else {
                    $dbEnt->startDate = clone $ent->endDate;
                    $dbEnt->startDate->add(new \DateInterval('P1D'));

                    $this->repository->update($dbEnt);
                }
            } elseif ($ent->startDate > $dbEnt->startDate && $ent->endDate < $dbEnt->endDate) {
                // 3rd crossing case $ent is included in $dbEnt
                if ($ent->price == $dbEnt->price) {
                    $ent->startDate = $dbEnt->startDate;
                    $ent->endDate = $dbEnt->endDate;

                    $this->repository->delete($dbEnt->id);
                } else {
                    $newEntEndDate = clone $dbEnt->endDate;

                    $dbEntEndDate = clone $ent->startDate;
                    $dbEntEndDate->sub(new \DateInterval('P1D')); // substracts 1 day
                    $dbEnt->endDate = $dbEntEndDate;
                    $this->repository->update($dbEnt);

                    $newEntStartDate = clone $ent->endDate;
                    $newEntStartDate->add(new \DateInterval('P1D')); // adds 1 day
                    $this->repository->insert(new Ent($newEntStartDate, $newEntEndDate, $dbEnt->price));
                }
            } elseif ($ent->startDate > $dbEnt->startDate && $ent->endDate >= $dbEnt->endDate) {
                // 4th crossing case $ent crosses with $dbEnt->endDate
                if ($ent->price == $dbEnt->price) {
                    $ent->startDate = $dbEnt->startDate;

                    $this->repository->delete($dbEnt->id);
                } else {
                    $dbEnt->endDate = clone $ent->startDate;
                    $dbEnt->endDate->sub(new \DateInterval('P1D'));

                    $this->repository->update($dbEnt);
                }
            }

            // Get the next crossing/merge interval
            $dbEnt = $this->repository->findCrossing($ent);

            // Trying to insert repeated Ent. This means that it's not necessary to continue with the insert.
            if($ent->startDate == $dbEnt->startDate && $ent->endDate == $dbEnt->endDate && $ent->price == $dbEnt->price)
                return true;
        }

        return $this->repository->insert($ent);
    }

    /**
     * Updates an Ent model
     * @param Ent $ent
     * @return bool
     */
    public function update(Ent $ent): bool
    {
        return $this->repository->update($ent);
    }

    /**
     * Deletes an specific Ent
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Deletes all Ent models
     * @return bool
     */
    public function deleteAll(): bool
    {
        return $this->repository->deleteAll();
    }
}