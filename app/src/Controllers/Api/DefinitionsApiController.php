<?php
namespace SWCPR\Controllers\Api;

use JetBrains\PhpStorm\NoReturn;
use SWCPR\Repositories\DepositTypeRepository;
use SWCPR\Repositories\TerrainTypeRepository;

class DefinitionsApiController extends ApiControllerBase
{
    private readonly TerrainTypeRepository $terrainTypeRepository;
    private readonly DepositTypeRepository $depositTypeRepository;

    public function __construct(TerrainTypeRepository $terrainTypeRepository, DepositTypeRepository $depositTypeRepository) {
        $this->terrainTypeRepository = $terrainTypeRepository;
        $this->depositTypeRepository = $depositTypeRepository;
    }

    #[NoReturn] public function getTerrainTypes(): void {
        $terrainTypes = $this->terrainTypeRepository->list();
        $terrainTypesResponse = array_map(
            fn($terrainType) => ['uid' => $terrainType->uid, 'name' => $terrainType->name, 'img' => ['src' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $terrainType->imgUrl]],
            $terrainTypes
        );
        $this->respondJson($terrainTypesResponse);
    }

    #[NoReturn] public function getDepositTypes(): void {
        $depositTypes = $this->depositTypeRepository->list();
        $depositTypesResponse = array_map(
            fn($depositType) => ['uid' => $depositType->uid, 'name' => $depositType->name, 'img' => ['src' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $depositType->imgUrl]],
            $depositTypes
        );
        $this->respondJson($depositTypesResponse);
    }
}