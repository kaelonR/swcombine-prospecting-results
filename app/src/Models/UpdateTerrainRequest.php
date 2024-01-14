<?php

namespace SWCPR\Models;

class UpdateTerrainRequest {
    public int $id;
    public string $terrainTypeUid;

    public function __construct(int $id, string $terrainTypeUid)
    {
        $this->id = $id;
        $this->terrainTypeUid = $terrainTypeUid;
    }
}