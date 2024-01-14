<?php

namespace SWCPR\Models;

class Deposit
{
    public int $id;

    public int $x;

    public int $y;

    public string $depositTypeUid;

    public int $amount;

    public string $notes;

    public function __construct(
        int $id,
        int $x,
        int $y,
        string $depositTypeUid,
        int $amount,
        string $notes
    ) {
        $this->id = $id;
        $this->x = $x;
        $this->y = $y;
        $this->depositTypeUid = $depositTypeUid;
        $this->amount = $amount;
        $this->notes = $notes;
    }
}