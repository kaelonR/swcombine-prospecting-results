<?php
namespace SWCPR\Models\Swc;

class Link
{
    public string $href;

    public function __construct(string $href)
    {
        $this->href = $href;
    }
}