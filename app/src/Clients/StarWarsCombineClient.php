<?php
namespace SWCPR\Clients;

class StarWarsCombineClient extends HttpClient {
    function getSystems(): array {
        $systemsListResponse = $this->get('https://www.swcombine.com/navcomp/systems-js.php');

        $systems = [];
        $systemIndex = 0;
        while($systemIndex !== false) {
            $systemIndex = strpos($systemsListResponse->body, 'loadsystem(', $systemIndex + 1);
            if($systemIndex === false) continue;

            $systemEndIndex = strpos($systemsListResponse->body, ')', $systemIndex);
            $systemParts = explode(', ', substr($systemsListResponse->body, $systemIndex + 11, $systemEndIndex - $systemIndex - 11));
            $systemName = substr($systemParts[0], 1, -1);
            $systems[] = $systemName;
        }

        return $systems;
    }

    function getPlanetsForSystem(string $systemName): array {
        $systemResponse = $this->get('https://www.swcombine.com/ws/v2.0/galaxy/systems/' . $systemName, ['Accept' => 'application/json']);
        $responseData = json_decode($systemResponse->body, $associative = true);
        $planets = $responseData['swcapi']['system']['planets']['planet'];
        return array_map(fn($x) => ['uid' => $x['attributes']['uid'], 'name' => $x['attributes']['name']], $planets);
    }

    function getPlanetInfo(string $planetName): array {
        $planetResponse = $this->get('https://www.swcombine.com/ws/v2.0/galaxy/planets/' . $planetName, ['Accept' => 'application/json']);
        $responseData = json_decode($planetResponse->body, $associative = true);
        $planetData = $responseData['swcapi']['planet'];
        $gridData = $responseData['swcapi']['planet']['grid']['point'];
        $parsedGrid = [];
        foreach($gridData as $point) {
            $parsedGrid[] = ['x' => $point['attributes']['x'], 'y' => $point['attributes']['y'], 'code' => $point['attributes']['code'], 'uid' => $point['attributes']['uid'], 'type' => $point['value']];
        }

        return ['uid' => $planetData['uid'], 'name' => $planetData['name'], 'system' => $planetData['location']['system']['value'], 'size' => $planetData['size'], 'type' => $planetData['type']['value'], 'grid' => $parsedGrid];
    }
}