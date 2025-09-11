<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class EnumeumType extends Type
{
    private string $name;

    public function getName(): string
    {
        return $this->name;
    }

    public static function create(string $name): static
    {
        $type = new static();
        $type->name = $name;

        return $type;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $this->name;
    }

    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        return [$this->name];
    }
}
