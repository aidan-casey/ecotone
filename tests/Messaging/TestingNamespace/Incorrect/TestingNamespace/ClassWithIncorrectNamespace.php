<?php
declare(strict_types=1);

namespace Incorrect\TestingNamespace\Wrong;

use Ecotone\Messaging\Annotation\ServiceContext;

class ClassWithIncorrectNamespaceAndClassName
{
    #[ServiceContext]
    public function someExtension(): array
    {
        return [];
    }
}