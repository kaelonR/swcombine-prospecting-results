<?php

namespace SWCPR\Models\Swc;

class PlanetDtoTerrain {
    public string $code;
    public string $uid;
    public string $name;

    public function __construct(string $code, string $uid, string $name)
    {
        $this->code = $code;
        $this->uid = $uid;
        $this->name = $name;
    }
}