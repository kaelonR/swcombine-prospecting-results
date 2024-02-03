<?php
namespace SWCPR\Repositories;

use Exception;
use PDO;
use SWCPR\Models\{Planet, PlanetGridTile, PlanetListing, TerrainType, UpdateTerrainRequest};

class PlanetRepository extends RepositoryBase {
    private readonly DepositRepository $depositRepository;

    public function __construct(PDO $pdo, DepositRepository $depositRepository)
    {
        parent::__construct($pdo);
        $this->depositRepository = $depositRepository;
    }

    public function initDB(): void {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS Planets (id int NOT NULL AUTO_INCREMENT PRIMARY KEY, name nvarchar(255) NOT NULL, `system` nvarchar(255) NOT NULL, size int NOT NULL CHECK (size >= 1 and size <= 30))");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS PlanetGridTiles (id int NOT NULL AUTO_INCREMENT PRIMARY KEY, planet_id int NOT NULL REFERENCES Planets(id), coord_x int NOT NULL, coord_y int NOT NULL, terrain_type_id nvarchar(5) NOT NULL REFERENCES TerrainTypes(uid))");
        $this->pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS PlanetGridTiles_composite_id ON PlanetGridTiles (planet_id, coord_x, coord_y)");
    }

    /**
     * @return PlanetListing[]
     * @throws Exception
     */
    public function list() : array {
        $rows = $this->pdo->query("SELECT id, name, `system` FROM Planets")->fetchAll(PDO::FETCH_ASSOC);
        $planets = [];

        foreach($rows as $row) {
            $deposits = $this->depositRepository->listForPlanet($row['id']);
            $planets[] = new PlanetListing($row['id'], $row['name'], $row['system'], $deposits);
        }

        return $planets;
    }

    /**
     * @throws Exception
     */
    public function getById(int $planetId): ?Planet {
        $stmt = $this->pdo->prepare("SELECT id, name, `system`, size FROM Planets WHERE id = :id");
        $success = $stmt->execute(['id' => $planetId]);
        if(!$success) {
            throw new Exception("something went wrong while attempting to retrieve planet $planetId.");
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if(empty($result))
            return null;

        $tiles = $this->getTerrainTilesForPlanet($planetId);
        $deposits = $this->depositRepository->listForPlanet($planetId);
        return new Planet($result['id'], $result['name'], $result['system'], $result['size'], $tiles, $deposits);
    }

    /**
     * @throws Exception
     */
    public function addNewPlanet(string $name, string $system, int $size, TerrainType $defaultTerrain): Planet {
        list($planetId, $insertTileStatement) = $this->insertPlanet($name, $system, $size);

        $tiles = [];
        for($x = 0; $x < $size; $x++) {
            for($y = 0; $y < $size; $y++) {
                $insertTileSuccessful = $insertTileStatement->execute(['planet_id' => $planetId, 'coord_x' => $x, 'coord_y' => $y, 'terrain_type_id' => $defaultTerrain->uid]);
                if(!$insertTileSuccessful) {
                    $this->pdo->rollBack();
                    throw new Exception("Something went wrong while attempting to create a new planet.");
                }
                $tiles[] = new PlanetGridTile($this->pdo->lastInsertId(), $x, $y, $defaultTerrain->uid);
            }
        }

        $commitSuccessful = $this->pdo->commit();
        if(!$commitSuccessful)
            throw new Exception("Something went wrong while attempting to create a new planet.");

        return new Planet($planetId, $name, $system, $size, $tiles, []);
    }

    /**
     * @param Planet $planet
     * @param PlanetGridTile[] $grid
     * @return Planet
     * @throws Exception
     */
    public function addNewPlanetWithGrid(string $name, string $system, int $size, array $grid): Planet  {
        list($planetId, $insertTileStatement) = $this->insertPlanet($name, $system, $size);
        foreach($grid as $tile) {
            if($tile->x < 0 || $tile->y < 0 || $tile->x >= $size || $tile->y >= $size) {
                $this->pdo->rollBack();
                throw new Exception("Encountered an illegal tile while attempting to insert a planet with preset grid.");
            }

            $insertTileSuccessful = $insertTileStatement->execute(['planet_id' => $planetId, 'coord_x' => $tile->x, 'coord_y' => $tile->y, 'terrain_type_id' => $tile->terrainTypeUid]);
            if(!$insertTileSuccessful) {
                $this->pdo->rollBack();
                throw new Exception("Something went wrong while attempting to insert a preset grid.");
            }
            $tile->id = $this->pdo->lastInsertId();
        }

        $commitSuccessful = $this->pdo->commit();
        if(!$commitSuccessful)
            throw new Exception("Something went wrong while attempting to create a new planet.");

        return new Planet($planetId, $name, $system, $size, $grid, []);
    }

    /**
     * @throws Exception
     */
    public function deletePlanet(mixed $planetReference): void {
        $planetId = ($planetReference instanceof Planet) ? $planetReference->id : intval($planetReference);

        $this->pdo->beginTransaction();
        $deleteTilesStatement = $this->pdo->prepare("DELETE FROM PlanetGridTiles WHERE planet_id = :planet_id");
        $deleteTilesSuccessful = $deleteTilesStatement->execute(['planet_id' => $planetId]);

        $deletePlanetStatement = $this->pdo->prepare("DELETE FROM Planets WHERE id = :id");
        $deletePlanetSuccessful = $deletePlanetStatement->execute(['id' => $planetId]);

        if(!$deleteTilesSuccessful || !$deletePlanetSuccessful) {
            $this->pdo->rollBack();
            throw new Exception("Something went wrong while attempting to delete the planet");
        }

        $success = $this->pdo->commit();
        if(!$success)
            throw new Exception("Something went wrong while attempting to delete the planet");
    }

    /**
     * @throws Exception
     */
    public function updatePlanet(Planet $planet): void {
        $stmt = $this->pdo->prepare("UPDATE Planets SET name = :name, `system` = :system WHERE id = :id");
        $success = $stmt->execute(['id' => $planet->id, 'name' => $planet->name, 'system' => $planet->system ]);
        if(!$success)
            throw new Exception("Something went wrong while attempting to update the planet");
    }

    /**
     * @throws Exception
     */
    public function getTerrainTilesForPlanet(int $planetId): array {
        $stmt = $this->pdo->prepare("SELECT * FROM PlanetGridTiles WHERE planet_id = :planet_id ORDER BY coord_y, coord_x");
        $success = $stmt->execute(['planet_id' => $planetId]);
        if(!$success)
            throw new Exception("something went wrong while attempting to get the terrain tiles for planet $planetId");

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new PlanetGridTile($row['id'], $row['coord_x'], $row['coord_y'], $row['terrain_type_id']), $result);
    }

    /**
     * @param int $planetId id of the planet to which the tiles belong. Used for data safety purposes.
     * @param UpdateTerrainRequest[] $terrainTiles
     * @return void
     * @throws Exception
     */
    public function updatePlanetTerrainTiles(int $planetId, array $terrainTiles): void {
        $stmt = $this->pdo->prepare("UPDATE PlanetGridTiles SET terrain_type_id = :terrain_type_id WHERE id = :id AND planet_id = :planet_id");
        foreach($terrainTiles as $tile) {
            $success = $stmt->execute(['id' => $tile->id, 'planet_id' => $planetId, 'terrain_type_id' => $tile->terrainTypeUid]);
            if(!$success)
                throw new Exception("Something went wrong while attempting to update the grid tile.");
        }
    }

    /**
     * @throws Exception
     */
    private function insertPlanet(string $name, string $system, int $size): array
    {
        $this->pdo->beginTransaction();

        $insertPlanetStatement = $this->pdo->prepare("INSERT INTO Planets (name, `system`, size) VALUES (:name, :system, :size)");
        $insertPlanetSuccessful = $insertPlanetStatement->execute(['name' => $name, 'system' => $system, 'size' => $size]);
        if (!$insertPlanetSuccessful) {
            $this->pdo->rollBack();
            throw new Exception("Something went wrong while attempting to create a new planet.");
        }

        $planetId = $this->pdo->lastInsertId();
        $insertTileStatement = $this->pdo->prepare("INSERT INTO PlanetGridTiles (planet_id, coord_x, coord_y, terrain_type_id) VALUES (:planet_id, :coord_x, :coord_y, :terrain_type_id)");
        return array($planetId, $insertTileStatement);
    }
}