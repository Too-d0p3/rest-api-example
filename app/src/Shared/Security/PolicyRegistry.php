<?php

namespace App\Shared\Security;

use InvalidArgumentException;

class PolicyRegistry
{
    /**
     * @var array<class-string, EntityAccessPolicy>
     */
    private array $policies = [];

    public function register(string $className, EntityAccessPolicy $policy): void
    {
        $this->policies[$className] = $policy;
    }

    public function getPolicyFor(object $subject): EntityAccessPolicy
    {
        foreach ($this->policies as $className => $policy) {
            if (is_a($subject, $className)) {
                return $policy;
            }
        }

        throw new InvalidArgumentException("No policy registered for class " . get_class($subject));
    }

    public function getPolicyByClass(string $className): EntityAccessPolicy
    {
        return $this->policies[$className] ?? throw new InvalidArgumentException("No policy for $className");
    }
}
