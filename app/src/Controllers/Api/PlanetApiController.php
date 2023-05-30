<?php
namespace SWCPR\Controllers\Api;

use SWCPR\Models\Planet;
use SWCPR\Repositories\PlanetRepository;
use SWCPR\Repositories\TerrainTypeRepository;

class PlanetApiController extends ApiControllerBase {
    private readonly PlanetRepository $planetRepository;
    private readonly TerrainTypeRepository $terrainTypeRepository;

    public function __construct(PlanetRepository $planetRepository, TerrainTypeRepository $terrainTypeRepository) {
        $this->planetRepository = $planetRepository;
        $this->terrainTypeRepository = $terrainTypeRepository;
    }

    public function listPlanets() {
        $planets = $this->planetRepository->list();
        $this->respondJson($planets);
    }

    public function addPlanet() {
        $json = file_get_contents('php://input');
        $payload = json_decode($json);
        if(!isset($payload->name) || strlen(trim($payload->name)) === 0)
            $this->respondWithError(422, 'name is required');
        if(!isset($payload->size) || !is_numeric($payload->size) || $payload->size <= 0 || $payload->size > 30 || floor($payload->size) != $payload->size)
            $this->respondWithError(422, 'size is required and must be a whole number between 1 and 30.');
        if(!isset($payload->defaultTerrainType) || !str_starts_with($payload->defaultTerrainType, '24:'))
            $this->respondWithError(422, "defaultTerrainType is required and must be a valid terrain type UID. Valid UIDs are available at /api /definitions/terrain.");

        $defaultTerrainType = $this->terrainTypeRepository->getByUid($payload->defaultTerrainType);
        if(empty($defaultTerrainType))
            $this->respondWithError(422, "Could not find a terrain type with uid $payload->defaultTerrainType.");

        $planet = new Planet(0, $payload->name, $payload->size);
        $newPlanet = $this->planetRepository->addNewPlanet($planet, $defaultTerrainType);

        $this->respondJson($newPlanet, 201);
    }

    public function deletePlanet($planetId) {
        $this->planetRepository->deletePlanet($planetId);
        $this->respondStatusCode(204);
    }

    public function updatePlanetName($planetId) {
        $json = file_get_contents("php://input");
        $newName = json_decode($json);
        if(strlen(trim($newName)) === 0)
            $this->respondWithError(422, "name is required, and must be sent as raw JSON string.");

        $planet = $this->planetRepository->getById($planetId);
        if(empty($planet))
            $this->respondWithError(404, "Planet not found");

        $planet->name = $newName;
        try {
            $this->planetRepository->updatePlanet($planet);
            $this->respondJson($planet);
        } catch(\Exception $e) {
            $this->respondWithError(500, $e->getMessage());
        }
    }

    public function getTerrainTilesForPlanet($planetId) {
        $terrainTiles = $this->planetRepository->getTerrainTilesForPlanet($planetId);
        if(empty($terrainTiles))
            $this->respondWithError(404, "planet not found");

        $tilesResponse = array_map(fn($tile) => [
            'id' => $tile->id,
            'x' => $tile->x,
            'y' => $tile->y,
            'terrain' => [
                'uid' => $tile->terrainTypeUid,
            ]
        ],$terrainTiles);

        $this->respondJson($tilesResponse);
    }

}