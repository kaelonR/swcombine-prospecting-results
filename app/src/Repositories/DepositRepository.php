<?php
namespace SWCPR\Repositories;

use Exception;
use PDO;
use SWCPR\Models\Deposit;

class DepositRepository extends RepositoryBase {

    public function initDB(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS PlanetDeposits (id int NOT NULL AUTO_INCREMENT PRIMARY KEY, planet_grid_tile_id int NOT NULL UNIQUE REFERENCES PlanetGridTiles (id), deposit_type_id nvarchar(5) NOT NULL REFERENCES DepositTypes (uid), amount int NOT NULL, notes nvarchar(512))");
    }

    /**
     * @return Deposit[]
     * @throws Exception
     */
    public function listForPlanet(int $planetId): array {
        $stmt = $this->pdo->prepare("SELECT PD.id, PGT.coord_x, PGT.coord_y, PD.deposit_type_id, PD.amount, PD.notes FROM PlanetDeposits PD
INNER JOIN PlanetGridTiles PGT ON PD.planet_grid_tile_id = PGT.id
INNER JOIN Planets P ON PGT.planet_id = P.id
WHERE P.id = :planetId");
        $success = $stmt->execute(['planetId' => $planetId]);
        if(!$success)
            throw new Exception("Something went wrong while attempting to retrieve the deposits for planet $planetId");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($row) => new Deposit(
            $row['id'],
            $row['coord_x'],
            $row['coord_y'],
            $row['deposit_type_id'],
            $row['amount'],
            $row['notes']),
        $rows);
    }

    /**
     * @throws Exception
     */
    public function getDeposit(int $planetId, int $depositId): ?Deposit {
        $stmt = $this->pdo->prepare("SELECT PD.id, PGT.planet_id, PGT.coord_x, PGT.coord_y, PD.deposit_type_id, PD.amount, PD.notes FROM PlanetDeposits PD
INNER JOIN PlanetGridTiles PGT ON PD.planet_grid_tile_id = PGT.id
WHERE PD.id = :depositId AND PGT.planet_id = :planetId");
        $success = $stmt->execute(['depositId' => $depositId, 'planetId' => $planetId]);
        if(!$success)
            throw new Exception("Could not find deposit with id $depositId.");
        if($stmt->rowCount() === 0)
            return null;

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return new Deposit(
            $row['id'],
            $row['coord_x'],
            $row['coord_y'],
            $row['deposit_type_id'],
            $row['amount'],
            $row['notes']
        );
    }

    /**
     * @throws Exception
     */
    public function addNewDeposit(int $planetId, Deposit $deposit): Deposit {
        $tileStmt = $this->pdo->prepare("SELECT id FROM PlanetGridTiles WHERE planet_id = :planet_id AND coord_x = :x AND coord_y = :y");
        $success = $tileStmt->execute(['planet_id' => $planetId, 'x' => $deposit->x, 'y' => $deposit->y]);
        if(!$success)
            throw new Exception("something went wrong while trying to retrieve tile for planet_id={$planetId}, coordinate x={$deposit->x}, y={$deposit->y}");

        $tile = $tileStmt->fetch(PDO::FETCH_ASSOC);
        if($tile == null)
            throw new Exception("Tile not found for planet_id={$planetId}, coordinate x={$deposit->x}, y={$deposit->y}.");

        $stmt = $this->pdo->prepare("INSERT INTO PlanetDeposits (planet_grid_tile_id, deposit_type_id, amount, notes) VALUES (:tile_id, :deposit_type_id, :amount, :notes)");
        $success = $stmt->execute(['tile_id' => $tile['id'], 'deposit_type_id' => $deposit->depositTypeUid, 'amount' => $deposit->amount, 'notes' => $deposit->notes]);
        if(!$success)
            throw new Exception('something went wrong while trying to update the deposit');

        $deposit->id = $this->pdo->lastInsertId();
        return $deposit;
    }

    /**
     * @throws Exception
     */
    public function updateDeposit(Deposit $deposit): void {
        $stmt = $this->pdo->prepare('UPDATE PlanetDeposits SET deposit_type_id = :deposit_type_id, amount = :amount, notes = :notes WHERE id = :id');
        $success = $stmt->execute(['id' => $deposit->id, 'deposit_type_id' => $deposit->depositTypeUid, 'amount' => $deposit->amount, 'notes' => $deposit->notes]);
        if(!$success)
            throw new Exception("Something went wrong while trying to update the deposit with id {$deposit->id}.");
    }

    /**
     * @throws Exception
     */
    public function deleteDeposit(mixed $depositReference): void {
        $depositId = ($depositReference instanceof Deposit) ? $depositReference->id : intval($depositReference);
        $stmt = $this->pdo->prepare('DELETE FROM PlanetDeposits WHERE id = :depositId');
        $success = $stmt->execute(['depositId' => $depositId]);
        if(!$success)
            throw new Exception("Something went wrong while trying to delete the deposit with id {$depositId}.");
    }
}