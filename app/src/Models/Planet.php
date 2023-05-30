<?php
namespace SWCPR\Models;

class Planet {
    public int $id;

    public string $name;

    public int $size;

    public function __construct($id, $name, $size) {
        $this->id = $id;
        $this->name = $name;
        $this->size = $size;
    }
}