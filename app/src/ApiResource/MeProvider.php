<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;

class MeProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return $this->security->getUser();
    }
}
