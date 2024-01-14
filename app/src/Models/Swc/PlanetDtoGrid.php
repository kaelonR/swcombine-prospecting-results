<?php

namespace SWCPR\Models\Swc;

class PlanetDtoGrid
{
    public int $x;
    public int $y;
    public PlanetDtoTerrain $terrain;

    public function __construct(int $x, int $y, PlanetDtoTerrain $terrain)
    {
        $this->x = $x;
        $this->y = $y;
        $this->terrain = $terrain;
    }
}