<?php
namespace SWCPR\Controllers\Api;
use SWCPR\Clients\StarWarsCombineClient;

class SwcApiController extends ApiControllerBase {
    private readonly StarWarsCombineClient $starWarsCombineClient;

    public function __construct(StarWarsCombineClient $starWarsCombineClient) {
        $this->starWarsCombineClient = $starWarsCombineClient;
    }

    public function getSystems() {
        $systems = $this->starWarsCombineClient->getSystems();
        $systemsResponse = array_map(
            fn($system) => ['name' => $system, 'planets' => ['href' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/api/swc/systems/' . rawurlencode($system) . '/planets']],
            $systems
        );
        $this->respondJson($systemsResponse);
    }

    public function getPlanetsForSystem($systemName) {
        $planets = $this->starWarsCombineClient->getPlanetsForSystem($systemName);
        $planetsResponse = array_map(
            fn($planet) => [...$planet, 'href' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/api/swc/planets/' . rawurlencode($planet['name'])],
            $planets
        );
        $this->respondJson($planetsResponse);
    }

    public function getPlanetInfo($planetName) {
        $planet = $this->starWarsCombineClient->getPlanetInfo($planetName);
        $planetResponse = [...$planet, 'system' => [
            'name' => $planet['system'],
            'planets' => [
                'href' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/api/swc/systems/' . $planet['system'] . '/planets',
            ]
        ]];
        $this->respondJson($planetResponse);
    }
}