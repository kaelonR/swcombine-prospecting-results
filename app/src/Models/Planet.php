<?php
namespace SWCPR\Models;

class Planet {
    public int $id;

    public string $name;

    public string $system;

    public int $size;

    /**
     * @var PlanetGridTile[]
     */
    public array $terrain;

    /**
     * @var Deposit[]
     */
    public array $deposits;

    /**
     * @param int $id
     * @param string $name
     * @param string $system
     * @param int $size
     * @param PlanetGridTile[] $grid
     * @param Deposit[] $deposits;
     */
    public function __construct(int $id, string $name, string $system, int $size, array $terrain, array $deposits) {
        $this->id = $id;
        $this->name = $name;
        $this->system = $system;
        $this->size = $size;
        $this->terrain = $terrain;
        $this->deposits = $deposits;
    }
}