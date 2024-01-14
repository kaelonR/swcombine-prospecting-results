<?php
require_once '/app/init.php';

use FastRoute\RouteCollector;
use SWCPR\Controllers\{Api\DefinitionsApiController,
    Api\InitApiController,
    Api\PlanetApiController,
    Api\SwcApiController,
    Api\TerrainTilesApiController,
    HomeController,
    PlanetController};

function declareRoutes(RouteCollector $r) {
    $r->addRoute('GET', '/', function() { runRoute(HomeController::class, 'index'); });
    $r->addRoute('GET', '/planets', function() { runRoute(PlanetController::class, 'index'); });
    $r->addRoute('GET', '/add-planet', function() { runRoute(PlanetController::class, 'addPlanet'); });
    $r->addRoute('GET', '/add-planet/import', function() { runRoute(PlanetController::class, 'importPlanet');});

    $r->addGroup('/api', function (RouteCollector $r) {
        $r->addGroup('/swc', function (RouteCollector $r) {
            $r->addRoute('GET', '/systems', function() { runRoute(SwcApiController::class, 'getSystems');});
            $r->addRoute('GET', '/systems/{systemName}/planets', function($vars) { runRoute(SwcApiController::class, 'getPlanetsForSystem', $vars);});
            $r->addRoute('GET', '/planets/{planetUid}', function($vars) { runRoute(SwcApiController::class, 'getPlanetInfo', $vars);});
        });

        $r->addGroup('/definitions', function (RouteCollector $r) {
            $r->addRoute('GET', '/terrain', function() { runRoute(DefinitionsApiController::class, 'getTerrainTypes');});
            $r->addRoute('GET', '/deposits', function() { runRoute(DefinitionsApiController::class, 'getDepositTypes');});
        });

        $r->addGroup('/data', function (RouteCollector $r) {
            $r->addGroup('/planets', function (RouteCollector $r) {
                $r->addRoute('GET', '', function() { runRoute(PlanetApiController::class, 'listPlanets');});
                $r->addRoute('POST', '', function() { runRoute(PlanetApiController::class, 'addPlanet');});
                $r->addGroup('/{planetId}', function (RouteCollector $r) {
                    $r->addRoute('GET', '', function ($vars) { runRoute(PlanetApiController::class, 'getPlanet', $vars);});
                    $r->addRoute('DELETE', '', function($vars) { runRoute(PlanetApiController::class, 'deletePlanet', $vars);});
                    $r->addRoute('PUT', '/name', function($vars) { runRoute(PlanetApiController::class, 'updatePlanetName', $vars);});
                    $r->addRoute('PUT', '/system', function($vars) { runRoute(PlanetApiController::class, 'updatePlanetSystem', $vars);});
                    $r->addRoute('PUT', '/terrain', function($vars) { runRoute(PlanetApiController::class, 'updatePlanetTerrain', $vars);});
                });
            
                $r->addGroup('/{planetId}/deposits', function (RouteCollector $r) {
                    $r->addRoute('POST', '', function($vars) { runRoute(PlanetApiController::class, 'addDeposit', $vars);});
                    $r->addRoute('PUT', '/{depositId}', function($vars) { runRoute(PlanetApiController::class, 'updateDeposit', $vars);});
                    $r->addRoute('DELETE', '/{depositId}', function($vars) { runRoute(PlanetApiController::class, 'deleteDeposit', $vars);});
                });
            });
        });
    });
    $r->addRoute('GET', '/init', function() { runRoute(InitApiController::class, 'initDB'); });
}

$dispatcher = FastRoute\simpleDispatcher(function($r) { declareRoutes($r); });

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo 'Page not found';
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        http_response_code(405);
        echo 'Method not allowed';
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        $handler($vars);
        break;
}