<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/foundry package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\Foundry\Object;

use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Object\Event\AfterInstantiate;

final class ValidationListener
{
    public function __construct(
        private readonly ValidatorInterface $validator,
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
