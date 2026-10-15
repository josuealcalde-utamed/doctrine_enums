<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Enumeum\DoctrineEnum\Definition\DefinitionRegistry;
use Enumeum\DoctrineEnum\EnumUsage\TableColumnRegistry;
use Enumeum\DoctrineEnum\Type\EnumeumType;

class PostGenerateSchemaSubscriber implements EventSubscriber
{
    public function __construct(
        private readonly DefinitionRegistry $registry,
        private readonly TableColumnRegistry $columnRegistry,
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            ToolEvents::postGenerateSchema,
        ];
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $event): void
    {
//        var_dump(__METHOD__);
//
//        TypeRegistryLoader::load($this->registry->getDefinitions());

        if (!$event->getEntityManager()->getConnection()->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            return;
        }

        foreach ($event->getSchema()->getTables() as $table) {
            foreach ($table->getColumns() as $column) {
                if (!($enumType = $this->extractEnumType($column))) {
                    continue;
                }

                if ($definition = $this->registry->getDefinitionByEnum($enumType)) {
                    $this->registerType($definition->name);

                    if ($this->columnRegistry->isColumnExists($table->getName(), $column->getName())) {
                        $currentType = $this->columnRegistry->getColumnType($table->getName(), $column->getName());
                        if ($currentType !== $definition->name) {
                            $column->setPlatformOption(EnumeumType::CAST_OPTION, true);
                        }
                    }

                    // DBAL >= 4.5 resuelve el tipo por nombre; una instancia nueva no está en el registro.
                    if (method_exists($column, 'setTypeName')) {
                        $column->setTypeName($definition->name);
                    } else {
                        $column->setType(Type::getType($definition->name));
                    }
                }
            }
        }
    }

    private function registerType(string $name): void
    {
        $registry = Type::getTypeRegistry();
        if (!$registry->has($name)) {
            $registry->register($name, EnumeumType::create($name));
        }
    }

    private function extractEnumType(Column $column): ?string
    {
        if ($column->hasPlatformOption('enumType')) {
            return $column->getPlatformOption('enumType');
        }

        if (method_exists($column, 'hasCustomSchemaOption')) {
            // For Doctrine DBAL <3.0
            if ($column->hasCustomSchemaOption('enumType')) {
                return $column->getCustomSchemaOption('enumType');
            }
        }

        return null;
    }
}
