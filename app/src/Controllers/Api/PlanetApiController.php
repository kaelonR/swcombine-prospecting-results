<?php
namespace SWCPR\Controllers\Api;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use SWCPR\Models\Deposit;
use SWCPR\Models\Planet;
use SWCPR\Models\PlanetGridTile;
use SWCPR\Models\UpdateTerrainRequest;
use SWCPR\Repositories\DepositRepository;
use SWCPR\Repositories\DepositTypeRepository;
use SWCPR\Repositories\PlanetRepository;
use SWCPR\Repositories\TerrainTypeRepository;

class PlanetApiController extends ApiControllerBase {
    private readonly PlanetRepository $planetRepository;
    private readonly TerrainTypeRepository $terrainTypeRepository;
    private readonly DepositRepository $depositRepository;
    private readonly DepositTypeRepository $depositTypeRepository;

    public function __construct(
        PlanetRepository $planetRepository,
        TerrainTypeRepository $terrainTypeRepository,
        DepositRepository $depositRepository,
        DepositTypeRepository $depositTypeRepository)
    {
        $this->planetRepository = $planetRepository;
        $this->terrainTypeRepository = $terrainTypeRepository;
        $this->depositRepository = $depositRepository;
        $this->depositTypeRepository = $depositTypeRepository;
    }

    #[NoReturn] public function listPlanets(): void
    {
        $planets = $this->planetRepository->list();
        $this->respondJson($planets);
    }

    #[NoReturn] public function getPlanet($planetId): void {
        try {
            $planet = $this->planetRepository->getById($planetId);
            if(empty($planet))
                $this->respondWithError(404, "Planet not found.");
            $this->respondJson($planet);
        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }

    #[NoReturn] public function addPlanet(): void
    {
        $json = file_get_contents('php://input');
        $payload = json_decode($json);

        if(!isset($payload->name) || strlen(trim($payload->name)) === 0)
            $this->respondWithError(422, 'name is required');
        if(!isset($payload->system) || strlen(trim($payload->system)) === 0)
            $this->respondWithError(422, 'system is required');
        if(!isset($payload->size) || !is_numeric($payload->size) || $payload->size <= 0 || $payload->size > 30 || floor($payload->size) != $payload->size)
            $this->respondWithError(422, 'size is required and must be a whole number between 1 and 30.');

        $defaultTerrainTypeSet = isset($payload->defaultTerrainType) && str_starts_with($payload->defaultTerrainType, '24:');
        $gridSet = isset($payload->grid) && count($payload->grid) === $payload->size ** 2;
        if(!($defaultTerrainTypeSet xor $gridSet)) {
            $this->respondWithError(422, 'must either set defaultTerrainType (which must be a uid starting with 24:), or grid, which must be an array of grid tiles that has exactly the square of the planet\'s size as amount of items.');
        }

        if($defaultTerrainTypeSet) {
            $defaultTerrainType = $this->terrainTypeRepository->getByUid($payload->defaultTerrainType);
            if (empty($defaultTerrainType))
                $this->respondWithError(422, "Could not find a terrain type with uid $payload->defaultTerrainType.");

            $newPlanet = $this->planetRepository->addNewPlanet($payload->name, $payload->system, $payload->size, $defaultTerrainType);
            $this->respondJson($newPlanet, 201);
        }

        if($gridSet) {
           $grid = array_map(fn($tile) =>
            new PlanetGridTile(0, $tile->x, $tile->y, $tile->terrain->uid),
           $payload->grid);

            try {
                $newPlanet = $this->planetRepository->addNewPlanetWithGrid($payload->name, $payload->system, $payload->size, $grid);
                $this->respondJson($newPlanet, 201);
            } catch (Exception $e) {
                $this->respondWithError(500, $e->getMessage());
            }
        }
    }

    #[NoReturn] function updatePlanetName(int $planetId): void
    {
        $json = file_get_contents('php://input');
        if(strlen(trim($json)) === 0)
            $this->respondWithError(400, 'body is required.');

        $newName = json_decode($json);
        if(strlen(trim($newName)) === 0)
            $this->respondWithError(422, 'name is required, and must be sent as raw JSON string.');

        $planet = $this->planetRepository->getById($planetId);
        if(empty($planet))
            $this->respondWithError(404, 'Planet not found');

        $planet->name = $newName;
        try {
            $this->planetRepository->updatePlanet($planet);
            $this->respondJson($planet);
        } catch(Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }
    
    #[NoReturn] function updatePlanetSystem(int $planetId): void {
        $json = file_get_contents('php://input');
        if(strlen(trim($json)) === 0)
            $this->respondWithError(400, 'body is required.');

        $newSystemName = json_decode($json);
        if(strlen(trim($newSystemName)) === 0)
            $this->respondWithError(422, 'system name is required, and must be sent as raw JSON string.');

        $planet = $this->planetRepository->getById($planetId);
        if(empty($planet))
            $this->respondWithError(404, 'Planet not found.');

        $planet->system = $newSystemName;
        try {
            $this->planetRepository->updatePlanet($planet);
            $this->respondJson($planet);
        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }

    #[NoReturn] public function updatePlanetTerrain(int $planetId): void {
        $json = file_get_contents('php://input');
        if(strlen(trim($json)) === 0)
            $this->respondWithError(400, 'body is required.');

        $payload = json_decode($json);
        if(!isset($payload->grid) || !is_array($payload->grid))
            $this->respondWithError(422, "grid is required, and must be an array of tiles to be updated");
        if(count($payload->grid) === 0)
            $this->respondStatusCode(204);

        $terrainTypes = $this->terrainTypeRepository->list();
        $terrainTypeCache = array_reduce($terrainTypes, function($cache, $terrainType) {$cache[$terrainType->uid] = true; return $cache;}, []);
        $tilesToUpdate = [];

        foreach($payload->grid as $tile) {
            if(empty($tile->id) || !is_numeric($tile->id))
                $this->respondWithError(422, "for each tile, id is required and must be a number.");
            if(empty($tile->terrainTypeUid) || !str_starts_with($tile->terrainTypeUid, "24:"))
                $this->respondWithError(422, "for each tile, terrainTypeUid is required and must be a uid starting with '24:'.");
            if(!array_key_exists($tile->terrainTypeUid, $terrainTypeCache)) {
                $this->respondWithError(422, "Could not find a terrain type with uid $tile->terrainTypeUid.");
            }
            $tilesToUpdate[] = new UpdateTerrainRequest($tile->id, $tile->terrainTypeUid);
        }

        try {
            $this->planetRepository->updatePlanetTerrainTiles($planetId, $tilesToUpdate);
            $this->respondStatusCode(204);
        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }

    #[NoReturn] public function deletePlanet(int $planetId): void {
        try {
            $this->planetRepository->deletePlanet($planetId);
            $this->respondStatusCode(204);
        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }

    #[NoReturn] public function addDeposit(int $planetId): void
    {
        $json = file_get_contents("php://input");
        if (strlen(trim($json)) === 0)
            $this->respondWithError(400, 'body is required');

        $planet = $this->planetRepository->getById($planetId);
        if (empty($planet))
            $this->respondWithError(404, "Planet not found.");

        $maxXY = $planet->size - 1;
        $payload = json_decode($json);
        if (!isset($payload->x) || $payload->x < 0 || $payload->x > $maxXY || floor($payload->x) != $payload->x)
            $this->respondWithError(422, "x is required, and must be a whole number between 0 and $maxXY.");
        if (!isset($payload->y) || $payload->y < 0 || $payload->y > $maxXY || floor($payload->y) != $payload->y)
            $this->respondWithError(422, "y is required, and must be a whole number between 0 and $maxXY.");
        if (!isset($payload->depositTypeUid) || !str_starts_with($payload->depositTypeUid, '16:'))
            $this->respondWithError(422, "depositTypeUid is required, and must be a uid starting with '16:'.");
        $depositType = $this->depositTypeRepository->getByUid($payload->depositTypeUid);
        if(empty($depositType))
            $this->respondWithError(422, "Could not find a deposit type with uid $payload->depositTypeUid.");
        if(!isset($payload->amount) || !is_numeric($payload->amount) || $payload->amount <= 0 || floor($payload->amount) != $payload->amount)
            $this->respondWithError(422, 'Amount is required and must be a whole number higher than 0.');

        $notes = !empty($payload->notes) ? $payload->notes : '';
        $deposit = new Deposit(0, $payload->x, $payload->y, $payload->depositTypeUid, $payload->amount, $notes);

        try {
            $newDeposit = $this->depositRepository->addNewDeposit($planetId, $deposit);
            $this->respondJson($newDeposit, 201);
        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }

    #[NoReturn] public function updateDeposit(int $planetId, int $depositId): void {
        $json = file_get_contents("php://input");
        if(strlen(trim($json)) === 0)
            $this->respondWithError(400, 'body is required');

        $payload = json_decode($json);
        $deposit = null;
        try {
            $deposit = $this->depositRepository->getDeposit($planetId, $depositId);
        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
        if(empty($deposit))
            $this->respondWithError(404, "could not find deposit with id $depositId under planet $planetId.");

        if(isset($payload->depositTypeUid) && str_starts_with($payload->depositTypeUid, '16:')) {
            $depositType = $this->depositTypeRepository->getByUid($payload->depositTypeUid);
            if(empty($depositType))
                $this->respondWithError(422, "Could not find a deposit type with uid $payload->depositTypeUid.");
            $deposit->depositTypeUid = $payload->depositTypeUid;
        }

        if(isset($payload->amount)) {
            if($payload->amount <= 0 || !is_numeric($payload->amount) || floor($payload->amount) != $payload->amount) {
                $this->respondWithError(422, 'Amount must be more than 0, and must be a whole integer.');
            }
            $deposit->amount = $payload->amount;
        }

        if(isset($payload->notes)) {
            $deposit->notes = $payload->notes;
        }

        try {
            $this->depositRepository->updateDeposit($deposit);
            $this->respondJson($deposit);
        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }

    #[NoReturn] public function deleteDeposit(int $planetId, int $depositId): void {
        $deposit = null;
        try {
            $deposit = $this->depositRepository->getDeposit($planetId, $depositId);
        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
        if(empty($deposit))
            $this->respondWithError(404, "could not find deposit with id $depositId under planet $planetId.");

        try {
            $this->depositRepository->deleteDeposit($deposit);
            $this->respondStatusCode(204);
        } catch (Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }
}