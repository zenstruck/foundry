<?php

declare(strict_types=1);

namespace Zenstruck\Foundry\Object;

use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Object\Event\AfterInstantiate;

final class ValidationListener
{
    public function __construct(
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * @param AfterInstantiate<object> $event
     */
    public function __invoke(AfterInstantiate $event): void
    {
        if (!$event->factory->validationEnabled()) {
            return;
        }

        $violations = $this->validator->validate($event->object, groups: $event->factory->getValidationGroups());

        if ($violations->count() > 0) {
            throw new ValidationFailedException($event->object, $violations);
        }
    }
}
