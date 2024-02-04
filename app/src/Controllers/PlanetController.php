<?php
namespace SWCPR\Controllers;

use SWCPR\Repositories\DepositRepository;
use SWCPR\Repositories\DepositTypeRepository;
use SWCPR\Repositories\PlanetRepository;
use SWCPR\Repositories\TerrainTypeRepository;
use Twig\Environment as Twig;

class PlanetController extends UIControllerBase {
    private readonly PlanetRepository $planetRepository;
    private readonly DepositTypeRepository $depositTypeRepository;
    private readonly TerrainTypeRepository $terrainTypeRepository;

    public function __construct(
        Twig $twig,
        PlanetRepository $planetRepository,
        DepositTypeRepository $depositTypeRepository,
        TerrainTypeRepository $terrainTypeRepository
    )
    {
        parent::__construct($twig);
        $this->planetRepository = $planetRepository;
        $this->depositTypeRepository = $depositTypeRepository;
        $this->terrainTypeRepository = $terrainTypeRepository;
    }

    public function index(): void {
        $planets = $this->planetRepository->list();
        $viewData = ['planets' => []];
        foreach($planets as $planet) {
            $resourceCounts = array_reduce($planet->deposits, function($counts, $deposit) {
                if(!array_key_exists($deposit->depositTypeUid, $counts))
                    $counts[$deposit->depositTypeUid] = 0;
                $counts[$deposit->depositTypeUid] += $deposit->amount;
                return $counts;
            }, []);
            uksort($resourceCounts, fn($a, $b) => substr($a, 3) <=> substr($b, 3));
            $viewData['planets'][] = ['id' => $planet->id, 'name' => $planet->name, 'system' => $planet->system, 'resources' => $resourceCounts];
        }

        $depositTypes = $this->depositTypeRepository->list();
        $viewData['depositTypes'] = $depositTypes;
        $this->render('planets/index.twig', $viewData);
    }

    public function viewPlanet(int $planetId): void {
        $planet = $this->planetRepository->getById($planetId);
        $terrainTypes = array_filter($this->terrainTypeRepository->list(), fn($x) => $x->uid != '24:16' && $x->uid != '24:17');
        $this->render('planets/planet.twig', ['planet' => $planet, 'terrainTypes' => $terrainTypes]);
    }

    public function addPlanet(): void {
        $this->render('planets/add-planet.twig');
    }

    public function importPlanet(): void {
        $this->render('planets/import-planet.twig');
    }
}