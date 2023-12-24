<?php
namespace SWCPR\Models\Swc;

class SystemPlanetDto {
    public string $uid;
    public string $name;
    public string $href;

    public function __construct(string $uid, string $name) {
        $this->uid = $uid;
        $this->name = $name;
        $this->href = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/api/swc/planets/' . rawurlencode($uid);
    }
}