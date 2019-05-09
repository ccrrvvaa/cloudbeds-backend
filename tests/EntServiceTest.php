<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\models\Ent;
use App\services\EntService;

class EntRepositoryMock
{
    /**
     * @var Ent[]
     */
    protected $ents;

    public function __construct()
    {
        $this->ents = [];
    }

    /**
     * @param Ent[] $ents
     */
    public function setEnts(array $ents): void
    {
        $this->ents = $ents;
    }

    private function getLastId()
    {
        if (count($this->ents) == 0)    return 0;

        $lastId = 0;
        foreach ($this->ents as $ent)
            if ($ent->id > $lastId) $lastId = $ent->id;
        
        return $lastId;
    }

    public function findAll()
    {
        return $this->ents;
    }

    /**
     * @param Ent $a
     * @param Ent $b
     */
    function compareMethod($a, $b)
    {
        if ($a->startDate == $b->startDate)
            return 0;

        return ($a->startDate < $b->startDate) ? -1 : 1;
    }

    public function sortEnts()
    {
        usort($this->ents, "self::compareMethod");
    }

    public function get(int $id): Ent
    {
        foreach ($this->ents as $ent)
            if ($ent->id == $id)    return $ent;
        
        return null;
    }

    /**
     * Gets a list of Ents which cross intervals with parameters
     * @param Ent $newEnt
     * @return Ent|null
     */
    public function findCrossing(Ent $newEnt)
    {
        foreach ($this->ents as $ent) { 
            if ($newEnt->endDate < $ent->startDate || $newEnt->startDate > $ent->endDate) {
                // Possible merge with existent intervals
                if ($newEnt->endDate < $ent->startDate && $newEnt->price == $ent->price && ($ent->startDate->diff($newEnt->endDate))->days == 1)
                    return $ent;
                elseif ($newEnt->price == $ent->price && ($newEnt->startDate->diff($ent->endDate))->days == 1)
                    return $ent;
            } else {
                // There is a crossing existent interval
                return $ent;
            }
        }

        return null;
    }

    public function insert(Ent $ent): bool
    {
        $ent->id = $this->getLastId() + 1;

        $this->ents[] = $ent;

        $this->sortEnts();

        return true;
    }

    public function update(Ent $ent): bool
    {
        $dbEnt = $this->get($ent->id);

        if (!$dbEnt)    throw new \InvalidArgumentException('Ent not found');

        $dbEnt->price = $ent->price;
        $dbEnt->startDate = $ent->startDate;
        $dbEnt->endDate = $ent->endDate;

        $this->sortEnts();

        return true;
    }

    public function delete(int $id): bool
    {
        $index = -1;

        foreach ($this->ents as $i => $ent) {
            if ($ent->id == $id) {
                $index = $i;
                break;
            }
        }

        if ($index > -1)    array_splice($this->ents, $index, 1);

        return true;
    }
}


class EntServiceTest extends TestCase
{
    /**
     * @var EntService
     */
    protected $service;

    protected function setUp(): void
    {
        $this->service = new EntService(new EntRepositoryMock);
    }

    public function testCreateInstance(): void
    {
        $this->assertInstanceOf(EntService::class, $this->service);
    }

    public function testEntInserted(): void
    {
        $ent = new Ent(new \DateTime('2019-01-01'), new \DateTime('2019-01-10'), 15);

        $this->service->insert($ent);

        $this->assertEquals(1, count($this->service->findAll()));
    }

    public function testIntervalGreaterThanExistingOne(): void // from Example 1
    {
        $ent1 = new Ent(new \DateTime('2019-01-01'), new \DateTime('2019-01-10'), 15);
        $ent2 = new Ent(new \DateTime('2019-01-05'), new \DateTime('2019-01-20'), 15);

        $this->service->insert($ent1);
        $this->service->insert($ent2);

        $ents = $this->service->findAll();
        $this->assertEquals(1, count($ents));
        $this->assertEquals('01', $ents[0]->startDate->format('d'));
        $this->assertEquals('20', $ents[0]->endDate->format('d'));
        $this->assertEquals(15, $ents[0]->price);
    }

    public function testIntervalIncludedInExistingEnt(): void // from Example 1
    {
        $initialEnt = new Ent(new \DateTime('2019-01-01'), new \DateTime('2019-01-20'), 15);
        $initialEnt->id = 1;

        $repositoryMock = $this->service->getRepository();
        $repositoryMock->setEnts([$initialEnt]);

        $ent = new Ent(new \DateTime('2019-01-02'), new \DateTime('2019-01-08'), 45);

        $this->service->insert($ent);

        $ents = $this->service->findAll();
        
        $this->assertEquals(3, count($ents));
        $this->assertEquals('01', $ents[0]->startDate->format('d'));
        $this->assertEquals('01', $ents[0]->endDate->format('d'));
        $this->assertEquals(15, $ents[0]->price);
        $this->assertEquals('02', $ents[1]->startDate->format('d'));
        $this->assertEquals('08', $ents[1]->endDate->format('d'));
        $this->assertEquals(45, $ents[1]->price);
        $this->assertEquals('09', $ents[2]->startDate->format('d'));
        $this->assertEquals('20', $ents[2]->endDate->format('d'));
        $this->assertEquals(15, $ents[2]->price);
    }

    public function testMergeIntervalWithSamePrice(): void // from Example 1
    {
        $initialEnt1 = new Ent(new \DateTime('2019-01-01'), new \DateTime('2019-01-01'), 15);
        $initialEnt1->id = 1;
        $initialEnt2 = new Ent(new \DateTime('2019-01-02'), new \DateTime('2019-01-08'), 45);
        $initialEnt2->id = 2;

        $repositoryMock = $this->service->getRepository();
        $repositoryMock->setEnts([$initialEnt1, $initialEnt2]);

        $ent = new Ent(new \DateTime('2019-01-09'), new \DateTime('2019-01-10'), 45);

        $this->service->insert($ent);

        $ents = $this->service->findAll();

        $this->assertEquals(2, count($ents));
        $this->assertEquals($initialEnt1, $ents[0]);
        $this->assertEquals('02', $ents[1]->startDate->format('d'));
        $this->assertEquals('10', $ents[1]->endDate->format('d'));
        $this->assertEquals(45, $ents[1]->price);
    }

    public function testMergeIntervals(): void // from Example 1
    {
        $initialEnt1 = new Ent(new \DateTime('2019-01-01'), new \DateTime('2019-01-01'), 15);
        $initialEnt1->id = 1;
        $initialEnt2 = new Ent(new \DateTime('2019-01-02'), new \DateTime('2019-01-08'), 45);
        $initialEnt2->id = 2;
        $initialEnt3 = new Ent(new \DateTime('2019-01-09'), new \DateTime('2019-01-20'), 15);
        $initialEnt3->id = 3;

        $repositoryMock = $this->service->getRepository();
        $repositoryMock->setEnts([$initialEnt1, $initialEnt2, $initialEnt3]);

        $ent = new Ent(new \DateTime('2019-01-09'), new \DateTime('2019-01-10'), 45);

        $this->service->insert($ent);

        $ents = $this->service->findAll();

        $this->assertEquals(3, count($ents));
        $this->assertEquals($initialEnt1, $ents[0]);
        $this->assertEquals('02', $ents[1]->startDate->format('d'));
        $this->assertEquals('10', $ents[1]->endDate->format('d'));
        $this->assertEquals(45, $ents[1]->price);
        $this->assertEquals('11', $ents[2]->startDate->format('d'));
        $this->assertEquals('20', $ents[2]->endDate->format('d'));
        $this->assertEquals(15, $ents[2]->price);
    }

    public function testCrossingWith2Intervals(): void // from Example 2
    {
        $initialEnt1 = new Ent(new \DateTime('2019-01-01'), new \DateTime('2019-01-05'), 15);
        $initialEnt1->id = 1;
        $initialEnt2 = new Ent(new \DateTime('2019-01-20'), new \DateTime('2019-01-25'), 15);
        $initialEnt2->id = 2;

        $repositoryMock = $this->service->getRepository();
        $repositoryMock->setEnts([$initialEnt1, $initialEnt2]);

        $ent = new Ent(new \DateTime('2019-01-04'), new \DateTime('2019-01-21'), 45);

        $this->service->insert($ent);

        $ents = $this->service->findAll();

        $this->assertEquals(3, count($ents));
        $this->assertEquals('01', $ents[0]->startDate->format('d'));
        $this->assertEquals('03', $ents[0]->endDate->format('d'));
        $this->assertEquals(15, $ents[0]->price);
        $this->assertEquals('04', $ents[1]->startDate->format('d'));
        $this->assertEquals('21', $ents[1]->endDate->format('d'));
        $this->assertEquals(45, $ents[1]->price);
        $this->assertEquals('22', $ents[2]->startDate->format('d'));
        $this->assertEquals('25', $ents[2]->endDate->format('d'));
        $this->assertEquals(15, $ents[2]->price);
    }

    public function testCrossingAndMergeIntoOneInterval(): void // from Example 2
    {
        $initialEnt1 = new Ent(new \DateTime('2019-01-01'), new \DateTime('2019-01-03'), 15);
        $initialEnt1->id = 1;
        $initialEnt2 = new Ent(new \DateTime('2019-01-04'), new \DateTime('2019-01-21'), 45);
        $initialEnt2->id = 2;
        $initialEnt3 = new Ent(new \DateTime('2019-01-22'), new \DateTime('2019-01-25'), 15);
        $initialEnt3->id = 3;

        $repositoryMock = $this->service->getRepository();
        $repositoryMock->setEnts([$initialEnt1, $initialEnt2, $initialEnt3]);

        $ent = new Ent(new \DateTime('2019-01-03'), new \DateTime('2019-01-21'), 15);

        $this->service->insert($ent);

        $ents = $this->service->findAll();

        $this->assertEquals(1, count($ents));
        $this->assertEquals('01', $ents[0]->startDate->format('d'));
        $this->assertEquals('25', $ents[0]->endDate->format('d'));
        $this->assertEquals(15, $ents[0]->price);
    }
}