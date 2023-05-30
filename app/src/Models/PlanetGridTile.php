<?php
namespace SWCPR\Models;

class PlanetGridTile {
    public int $id;

    public int $planetId;

    public int $x;

    public int $y;

    public string $terrainTypeUid;

    public function __construct($id, $planetId, $x, $y, $terrainTypeUid) {
        $this->id = $id;
        $this->planetId = $planetId;
        $this->x = $x;
        $this->y = $y;
        $this->terrainTypeUid = $terrainTypeUid;
    }
}