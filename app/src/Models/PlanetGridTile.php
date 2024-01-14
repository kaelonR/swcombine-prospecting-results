<?php
namespace SWCPR\Models;

class PlanetGridTile {
    public int $id;

    public int $x;

    public int $y;

    public string $terrainTypeUid;

    public function __construct($id, $x, $y, $terrainTypeUid) {
        $this->id = $id;
        $this->x = $x;
        $this->y = $y;
        $this->terrainTypeUid = $terrainTypeUid;
    }
}