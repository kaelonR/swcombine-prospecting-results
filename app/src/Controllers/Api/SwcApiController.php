<?php
namespace SWCPR\Controllers\Api;
use JetBrains\PhpStorm\NoReturn;
use SWCPR\Clients\StarWarsCombineClient;
use SWCPR\Models\Swc\SystemDto;

class SwcApiController extends ApiControllerBase {
    private readonly StarWarsCombineClient $starWarsCombineClient;

    public function __construct(StarWarsCombineClient $starWarsCombineClient) {
        $this->starWarsCombineClient = $starWarsCombineClient;
    }

    #[NoReturn] public function getSystems() {
        $systems = $this->starWarsCombineClient->getSystems();
        $this->respondJson($systems);
    }


    #[NoReturn] public function getPlanetsForSystem($systemName) {
        $planets = $this->starWarsCombineClient->getPlanetsForSystem($systemName);
        $this->respondJson($planets);
    }

    #[NoReturn] public function getPlanetInfo($planetUid) {
        $planet = $this->starWarsCombineClient->getPlanetInfo($planetUid);
        $this->respondJson($planet);
    }
}