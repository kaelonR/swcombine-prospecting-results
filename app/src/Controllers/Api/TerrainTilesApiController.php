<?php
namespace SWCPR\Controllers\Api;

use SWCPR\Controllers\Api\ApiControllerBase;
use SWCPR\Repositories\PlanetRepository;
use SWCPR\Repositories\TerrainTypeRepository;

class TerrainTilesApiController extends ApiControllerBase {
    private readonly PlanetRepository $planetRepository;

    private readonly TerrainTypeRepository $terrainTypeRepository;

    public function __construct(PlanetRepository $planetRepository, TerrainTypeRepository $terrainTypeRepository) {
        $this->planetRepository = $planetRepository;
        $this->terrainTypeRepository = $terrainTypeRepository;
    }

    public function updateTileTerrain($tileId) {
        $json = file_get_contents("php://input");
        $terrainTypeUid = json_decode($json);


        if(!is_string($terrainTypeUid) || strlen(trim($terrainTypeUid)) === 0 || !str_starts_with($terrainTypeUid, "24:"))
            $this->respondWithError(422, "terrain type UID is required, must be supplied as raw JSON string in the request body, and must be a valid terrain UID. Valid UIDs can be found at /api/definitions/terrain.");

        $tile = $this->planetRepository->getTerrainTileById($tileId);
        if(empty($tile))
            $this->respondWithError(404, "terrain tile could not be found");

        $terrainType = $this->terrainTypeRepository->getByUid($terrainTypeUid);
        if(empty($terrainType))
            $this->respondWithError(422, "Could not find a terrain type with uid $terrainTypeUid.");

        $tile->terrainTypeUid = $terrainType->uid;
        $this->planetRepository->updateTerrainTile($tile);
        $this->respondJson($tile);
    }
}