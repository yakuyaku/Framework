<?php
namespace Wandu\Database;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Wandu\Caster\CastManagerInterface;
use Wandu\Database\Contracts\ConnectionInterface;
use Wandu\Database\Contracts\Entity\MetadataReaderInterface;
use Wandu\Database\Entity\MetadataReader;
use Wandu\Database\Migrator\MigrateAdapter;
use Wandu\Database\Migrator\MigrateAdapterInterface;
use Wandu\Database\Migrator\MigrateTemplate;
use Wandu\Database\Migrator\MigrateTemplateInterface;
use Wandu\DI\ContainerInterface;
use Wandu\DI\ServiceProviderInterface;
use function Wandu\Foundation\config;

class DatabaseServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $app)
    {
        $app->bind(Reader::class, AnnotationReader::class);
        $app->bind(MetadataReaderInterface::class, MetadataReader::class);
        $app->bind(Manager::class)->after(function (Manager $manager) {
            foreach (config('database.connections', []) as $name => $connection) {
                $manager->connect($connection, $name);
            }
        });

        $app->alias('database', Manager::class);
        $app->closure(ConnectionInterface::class, function (Manager $manager) {
            return $manager->connection();
        });
        $app->alias('connection', ConnectionInterface::class);

        $app->bind(MigrateTemplateInterface::class, MigrateTemplate::class);
        $app->bind(MigrateAdapterInterface::class, MigrateAdapter::class);
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $app)
    {
    }
}
