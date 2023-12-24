<?php
namespace SWCPR\Controllers\Api;
use SWCPR\Clients\StarWarsCombineClient;
use SWCPR\Models\Swc\SystemDto;

class SwcApiController extends ApiControllerBase {
    private readonly StarWarsCombineClient $starWarsCombineClient;

    public function __construct(StarWarsCombineClient $starWarsCombineClient) {
        $this->starWarsCombineClient = $starWarsCombineClient;
    }

    public function getSystems() {
        $systems = $this->starWarsCombineClient->getSystems();
        $this->respondJson($systems);
    }


    public function getPlanetsForSystem($systemName) {
        $planets = $this->starWarsCombineClient->getPlanetsForSystem($systemName);
        $this->respondJson($planets);
    }

    public function getPlanetInfo($planetName) {
        $planet = $this->starWarsCombineClient->getPlanetInfo($planetName);
        $this->respondJson($planet);
    }
}