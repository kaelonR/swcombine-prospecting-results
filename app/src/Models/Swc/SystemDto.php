<?php
namespace SWCPR\Models\Swc;

class SystemDto {
    public string $uid;
    public string $name;
    public Link $planets;

    public function __construct(string $uid, string $name) {
        $this->uid = $uid;
        $this->name = $name;
        $this->planets = new Link($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/api/swc/systems/' . rawurlencode($uid) . '/planets');
    }
}