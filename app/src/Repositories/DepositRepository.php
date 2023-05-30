<?php
namespace SWCPR\Repositories;

class DepositRepository extends RepositoryBase {

    function initDB(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS PlanetDeposits (id int NOT NULL AUTO_INCREMENT PRIMARY KEY, planet_grid_tile_id int NOT NULL REFERENCES PlanetGridTiles (id), deposit_type_id nvarchar(5) NOT NULL REFERENCES DepositTypes (uid), amount int NOT NULL, notes nvarchar(512))");
    }
}