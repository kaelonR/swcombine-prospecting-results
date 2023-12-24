<?php

namespace SWCPR\Models\Swc;

class PlanetDtoGrid
{
    public int $x;
    public int $y;
    public string $code;
    public string $uid;
    public string $type;

    public function __construct(int $x, int $y, string $code, string $uid, string $type)
    {
        $this->x = $x;
        $this->y = $y;
        $this->code = $code;
        $this->uid = $uid;
        $this->type = $type;
    }
}