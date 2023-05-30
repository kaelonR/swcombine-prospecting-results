<?php
namespace SWCPR\Controllers\Api;
use SWCPR\Repositories\{DepositRepository, DepositTypeRepository, PlanetRepository, TerrainTypeRepository};

class InitApiController extends ApiControllerBase   {
    private TerrainTypeRepository $terrainTypeRepository;
    private DepositTypeRepository $depositTypeRepository;
    private PlanetRepository $planetRepository;
    private DepositRepository $depositRepository;

    public function __construct(TerrainTypeRepository $terrainTypeRepository, DepositTypeRepository $depositTypeRepository, PlanetRepository $planetRepository, DepositRepository $depositRepository) {
        $this->terrainTypeRepository = $terrainTypeRepository;
        $this->depositTypeRepository = $depositTypeRepository;
        $this->planetRepository = $planetRepository;
        $this->depositRepository = $depositRepository;
    }

    public function initDB() {
        $this->terrainTypeRepository->initDB();
        $this->depositTypeRepository->initDB();
        $this->planetRepository->initDB();
        $this->depositRepository->initDB();

        echo 'Database initiated successfully';
    }
}