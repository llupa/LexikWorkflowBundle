<?php

declare(strict_types=1);

namespace Lexik\Bundle\WorkflowBundle\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Yaml\Parser;

abstract class TestCase extends BaseTestCase
{
    /**
     * Returns a mock instance of a AuthorizationChecker.
     *
     * @return AuthorizationChecker
     */
    public function getMockAuthorizationChecker()
    {
        $checker = $this->getMockBuilder('Symfony\Component\Security\Core\Authorization\AuthorizationChecker')
            ->disableOriginalConstructor()
            ->getMock();

        return $checker;
    }

    protected function getConfig(): array
    {
        $yaml = <<<EOF
processes:
    document_proccess:
        start: step_create_doc
        end:   [ step_validate_doc, step_remove_doc ]
        steps:
            step_create_doc:
                roles: [ ROLE_ADMIN, ROLE_USER ]
                next_states:
                    validate:
                        target: step_validate_doc
                    remove:
                        target: step_remove_doc
                    validate_or_remove:
                        type: step_or
                        target:
                            step_validate_doc: "next_state_condition:isClean"
                            step_remove_doc:   ~
            step_validate_doc:
                roles: [ ROLE_ADMIN, ROLE_USER ]
            step_remove_doc:
                roles: [ ROLE_ADMIN ]
EOF;
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    protected function getSimpleConfig(): array
    {
        $yaml = <<<EOF
processes:
    document_proccess:
        start:
        steps: []
EOF;
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    protected function createSchema(EntityManager $em): void
    {
        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());
    }

    protected function getMockManagerRegistry(): ManagerRegistry
    {
        $mockRegistry = self::createMock(ManagerRegistry::class);

        $mockRegistry
            ->method('getManager')
            ->willReturn($this->getSqliteEntityManager());

        return $mockRegistry;
    }

    protected function getSqliteEntityManager(): EntityManager
    {
        // xml driver
        $xmlDriver = new SimplifiedXmlDriver([
            __DIR__.'/../Resources/config/doctrine' => 'Lexik\Bundle\WorkflowBundle\Entity',
        ]);

        // configuration mock
        $config = Setup::createAnnotationMetadataConfiguration([
            __DIR__.'/../Entity',
        ], false, null, null, false);
        $config->setMetadataDriverImpl($xmlDriver);
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('Proxy');
        $config->setAutoGenerateProxyClasses(true);
        $config->setClassMetadataFactoryName('Doctrine\ORM\Mapping\ClassMetadataFactory');
        $config->setDefaultRepositoryClassName('Doctrine\ORM\EntityRepository');

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $em = EntityManager::create($conn, $config);

        return $em;
    }
}
