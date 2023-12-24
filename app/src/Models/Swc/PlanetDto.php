<?php
namespace SWCPR\Models\Swc;

class PlanetDto
{
    public string $uid;
    public string $name;
    public string $system;
    public int $size;
    public string $type;
    public array $grid;

    /**
     * @param string $uid
     * @param string $name
     * @param string $system
     * @param int $size
     * @param string $type
     * @param PlanetDtoGrid[] $grid
     */
    public function __construct(string $uid, string $name, string $system, int $size, string $type, array $grid)
    {
        $this->uid = $uid;
        $this->name = $name;
        $this->system = $system;
        $this->size = $size;
        $this->type = $type;
        $this->grid = $grid;
    }
}