<?php

namespace SWCPR\Models;

class PlanetListing {
    public int $id;

    public string $name;

    public string $system;

    /**
     * @var Deposit[]
     */
    public array $deposits;

    /**
     * @param int $id
     * @param string $name
     * @param string $system
     * @param Deposit[] $deposits
     */
    public function __construct(int $id, string $name, string $system, array $deposits)
    {
        $this->id = $id;
        $this->name = $name;
        $this->system = $system;
        $this->deposits = $deposits;
    }
}