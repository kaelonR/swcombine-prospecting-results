<?php
namespace SWCPR\Clients;

use SWCPR\Models\Swc\PlanetDto;
use SWCPR\Models\Swc\PlanetDtoGrid;
use SWCPR\Models\Swc\PlanetDtoTerrain;
use SWCPR\Models\Swc\SystemDto;
use SWCPR\Models\Swc\SystemPlanetDto;

class StarWarsCombineClient extends HttpClient {
    /** @return SystemDto[] */
    function getSystems(): array {
        $cachedSystems = $this->tryGetCachedData('systems', SystemDto::class, 60 * 60 * 24);
        if($cachedSystems != null)
            return $cachedSystems;

        /** @var SystemDto[] $systems */
        $systems = [];
        $currentPage = 1;
        $totalPages = 0;
        $itemsPerPage = 0;

        while($totalPages === 0 || $currentPage <= $totalPages) {
            $startIndex = ($currentPage - 1) * $itemsPerPage + 1;
            $systemsResponse = $this->get("https://www.swcombine.com/ws/v2.0/galaxy/systems?start_index=$startIndex", ['Accept' => 'application/json']);
            $responseData = json_decode($systemsResponse->body);
            if ($totalPages === 0) {
                $totalPages = ceil($responseData->swcapi->systems->attributes->total / $responseData->swcapi->systems->attributes->count);
                $itemsPerPage = $responseData->swcapi->systems->attributes->count;
            }

            $apiSystemsList = $responseData->swcapi->systems->system;
            foreach($apiSystemsList as $apiSystem) {
                $systems[] = new SystemDto($apiSystem->attributes->uid, $apiSystem->attributes->name);
            }

            $currentPage++;
        }

        $this->cacheData('systems', $systems);
        return $systems;
    }

    /**
     * @param $systemUid string
     * @return SystemPlanetDto[]
     */
    function getPlanetsForSystem(string $systemUid): array {
        $cachedPlanets = $this->tryGetCachedData("{$systemUid}_planets", SystemPlanetDto::class);
        if($cachedPlanets !== null)
            return $cachedPlanets;

        $systemResponse = $this->get("https://www.swcombine.com/ws/v2.0/galaxy/systems/$systemUid", ['Accept' => 'application/json']);
        $responseData = json_decode($systemResponse->body);
        $apiPlanets = $responseData->swcapi->system->planets->planet;
        $planets = array_map(fn($x) => new SystemPlanetDto($x->attributes->uid, $x->attributes->name), $apiPlanets);

        $this->cacheData("{$systemUid}_planets", $planets);
        return $planets;
    }

    /**
     * @param string $planetUid
     * @return PlanetDto
     */
    function getPlanetInfo(string $planetUid): PlanetDto {
        $cachedInfo = $this->tryGetCachedData("planet_$planetUid", [PlanetDto::class, PlanetDtoGrid::class, PlanetDtoTerrain::class]);
        if ($cachedInfo !== null)
            return $cachedInfo[0];

        $planetResponse = $this->get("https://www.swcombine.com/ws/v2.0/galaxy/planets/$planetUid", ['Accept' => 'application/json']);
        $responseData = json_decode($planetResponse->body);
        $planetData = $responseData->swcapi->planet;
        $gridData = $planetData->grid->point;

        /** @var PlanetDtoGrid[] $parsedGrid */
        $parsedGrid = [];
        foreach($gridData as $point) {
            $attributes = $point->attributes;
            $terrain = new PlanetDtoTerrain($attributes->code, $attributes->uid, $point->value);
            $parsedGrid[] = new PlanetDtoGrid($attributes->x, $attributes->y, $terrain);
        }

        $planet = new PlanetDto($planetData->uid, $planetData->name, $planetData->location->system->value, $planetData->size, $planetData->type->value, $parsedGrid);
        $this->cacheData("planet_$planetUid", [$planet]);
        return $planet;
    }

    private function tryGetCachedData(string $fileName, mixed $allowedClasses, int $maxAgeInSeconds = -1): array|null
    {
        $filePath = $this->getCachePath($fileName);
        if (!file_exists($filePath) || $maxAgeInSeconds != -1 && (time() - filemtime($filePath)) > $maxAgeInSeconds)
            return null;

        $fileText = file_get_contents($filePath);
        $allowedClasses = is_array($allowedClasses) ? $allowedClasses : [$allowedClasses];
        return unserialize($fileText, ['allowed_classes' => $allowedClasses]);
    }

    private function cacheData(string $fileName, array $data): void {
        $filePath = $this->getCachePath($fileName);
        $fileText = serialize($data);
        file_put_contents($filePath, $fileText);
    }

    private function getCachePath(string $fileName): string
    {
        $cacheDir = join(DIRECTORY_SEPARATOR, [dirname(__DIR__, 2), 'cache']);
        if(!is_dir($cacheDir))
            mkdir($cacheDir, 0777, true);

        $sanitizedName = $this->sanitizeFileName($fileName);
        return join(DIRECTORY_SEPARATOR, [$cacheDir, $sanitizedName . '.txt']);
    }

    private function sanitizeFileName($name): string
    {
        $unsafe_characters = ["/", "\\", "?", "%", "*", ":", "|", "\"", "<", ">", "."];
        // Check if name is a Windows reserved filename; prepend with underscore if it is
        if (preg_match('/^(con|prn|aux|nul|com[0-9]|lpt[0-9])(\..*)?$/i', $name)) {
            $name = "_" . $name;
        }
        // Replace unsafe characters:
        $nameSafe = str_replace($unsafe_characters, "_", $name);
        // Make sure the name does not exceed 255 characters:
        return substr($nameSafe, 0, 255);
    }
}