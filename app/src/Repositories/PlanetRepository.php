<?php
namespace SWCPR\Repositories;

use PDO;
use SWCPR\Models\Planet;
use SWCPR\Models\PlanetGridTile;
use SWCPR\Models\TerrainType;

class PlanetRepository extends RepositoryBase {
    public function initDB(): void {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS Planets (id int NOT NULL AUTO_INCREMENT PRIMARY KEY, name nvarchar(255) NOT NULL, size int NOT NULL CHECK (size >= 1 and size <= 30))");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS PlanetGridTiles (id int NOT NULL AUTO_INCREMENT PRIMARY KEY, planet_id int NOT NULL REFERENCES Planets(id), coord_x int NOT NULL, coord_y int NOT NULL, terrain_type_id nvarchar(5) NOT NULL REFERENCES TerrainTypes(uid))");
    }

    public function list() : array {
        $result = $this->pdo->query("SELECT * FROM Planets")->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Planet(...$row), $result);
    }

    public function getById(int $planetId): ?Planet {
        $stmt = $this->pdo->prepare("SELECT * FROM Planets WHERE id = :id");
        $stmt->execute(['id' => $planetId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if(empty($result))
            return null;

        return new Planet($result['id'], $result['name'], $result['size']);
    }

    public function addNewPlanet(Planet $planet, TerrainType $defaultTerrain): Planet {
        $this->pdo->beginTransaction();

        $insertPlanetStatement = $this->pdo->prepare("INSERT INTO Planets (name, size) VALUES (:name, :size)");
        $insertPlanetSuccessful = $insertPlanetStatement->execute(['name' => $planet->name, 'size' => $planet->size]);
        if(!$insertPlanetSuccessful) {
            $this->pdo->rollBack();
            throw new Error("Something went wrong while attempting to create a new planet.");
        }


        $planetId = $this->pdo->lastInsertId();
        $insertTileStatement = $this->pdo->prepare("INSERT INTO PlanetGridTiles (planet_id, coord_x, coord_y, terrain_type_id) VALUES (:planet_id, :coord_x, :coord_y, :terrain_type_id)");
        for($x = 0; $x < $planet->size; $x++) {
            for($y = 0; $y < $planet->size; $y++) {
                $insertTileSuccessful = $insertTileStatement->execute(['planet_id' => $planetId, 'coord_x' => $x, 'coord_y' => $y, 'terrain_type_id' => $defaultTerrain->uid]);
                if(!$insertTileSuccessful) {
                    $this->pdo->rollBack();
                    throw new Error("Something went wrong while attempting to create a new planet.");
                }
            }
        }

        $commitSuccessful = $this->pdo->commit();
        if(!$commitSuccessful)
            throw new Error("Something went wrong while attempting to create a new planet.");

        $planet->id = $planetId;
        return $planet;
    }

    public function deletePlanet(mixed $planetReference): void {
        $planetId = ($planetReference instanceof Planet) ? $planetReference->id : intval($planetReference);

        $this->pdo->beginTransaction();
        $deleteTilesStatement = $this->pdo->prepare("DELETE FROM PlanetGridTiles WHERE planet_id = :planet_id");
        $deleteTilesSuccessful = $deleteTilesStatement->execute(['planet_id' => $planetId]);

        $deletePlanetStatement = $this->pdo->prepare("DELETE FROM Planets WHERE id = :id");
        $deletePlanetSuccessful = $deletePlanetStatement->execute(['id' => $planetId]);

        if(!$deleteTilesSuccessful || !$deletePlanetSuccessful) {
            $this->pdo->rollBack();
            throw new Error("Something went wrong while attempting to delete the planet");
        }

        $success = $this->pdo->commit();
        if(!$success)
            throw new Error("Something went wrong while attempting to delete the planet");
    }

    public function updatePlanet(Planet $planet): void {
        $stmt = $this->pdo->prepare("UPDATE Planets SET name = :name WHERE id = :id");
        $success = $stmt->execute(['name' => $planet->name, 'id' => $planet->id]);
        if(!$success)
            throw new Error("Something went wrong while attempting to update the planet");
    }

    public function getTerrainTilesForPlanet(mixed $planetReference): array {
        $planetId = ($planetReference instanceof Planet) ? $planetReference->id : intval($planetReference);

        $stmt = $this->pdo->prepare("SELECT * FROM PlanetGridTiles WHERE planet_id = :planet_id");
        $stmt->execute(['planet_id' => $planetId]);

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new PlanetGridTile($row['id'], $row['planet_id'], $row['coord_x'], $row['coord_y'], $row['terrain_type_id']), $result);
    }

    public function getTerrainTileById(int $terrainTileId): ?PlanetGridTile {
        $stmt = $this->pdo->prepare("SELECT * FROM PlanetGridTiles WHERE id = :id");
        $stmt->execute(['id' => $terrainTileId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if(empty($row))
            return null;

        return new PlanetGridTile($row['id'], $row['planet_id'], $row['coord_x'], $row['coord_y'], $row['terrain_type_id']);
    }

    public function updateTerrainTile(PlanetGridTile $terrainTile): void {
        $updateStatement = $this->pdo->prepare("UPDATE PlanetGridTiles SET terrain_type_id = :terrain_type_id WHERE id = :id");
        $updateSuccessful = $updateStatement->execute(['id' => $terrainTile->id, 'terrain_type_id' => $terrainTile->terrainTypeUid]);
        if(!$updateSuccessful)
            throw new Error("Something went wrong while attempting to update the grid tile.");
    }
}