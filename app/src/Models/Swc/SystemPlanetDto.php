<?php
namespace SWCPR\Models\Swc;

class SystemPlanetDto {
    public string $uid;
    public string $name;

    public function __construct(string $uid, string $name) {
        $this->uid = $uid;
        $this->name = $name;
    }
}