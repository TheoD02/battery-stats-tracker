<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\BatteryStat\BatteryStatInput;
use App\Entity\BatteryStat;
use Doctrine\ORM\EntityManagerInterface;

readonly class BatteryStatProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em
    )
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): BatteryStat
    {
        if (!$data instanceof BatteryStatInput) {
            throw new \InvalidArgumentException('Data should be an instance of BatteryStat');
        }

        $record = new BatteryStat();
        $record->setLevel($data->getLevel());

        $this->em->persist($record);
        $this->em->flush();

        return $record;
    }
}