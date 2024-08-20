<?php

namespace App\ApiResource\BatteryStat;

use Symfony\Component\Validator\Constraints as Assert;

class BatteryStatInput
{
    #[Assert\Positive]
    private int $level = 0;

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }
}