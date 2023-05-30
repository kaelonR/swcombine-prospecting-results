<?php
namespace SWCPR\Models;

class TerrainType {
    public string $uid;

    public string $name;

    public string $imgUrl;

    public function __construct($uid, $name, $imgUrl) {
        $this->uid = $uid;
        $this->name = $name;
        $this->imgUrl = $imgUrl;
    }
}