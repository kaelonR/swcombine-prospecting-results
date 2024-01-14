<?php

namespace SWCPR\Models;

class PlanetListing {
    public int $id;

    public string $name;

    public string $system;

    public int $size;

    public function __construct(int $id, string $name, string $system, int $size)
    {
        $this->id = $id;
        $this->name = $name;
        $this->system = $system;
        $this->size = $size;
    }
}