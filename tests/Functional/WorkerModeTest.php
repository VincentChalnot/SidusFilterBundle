<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Registry\QueryHandlerRegistry;
use Sidus\FilterBundle\Tests\App\Entity\Post;
use Sidus\FilterBundle\Tests\App\TestKernel;
use Symfony\Component\HttpFoundation\Request;

/**
 * One booted kernel serving several requests, as FrankenPHP worker mode does: no state may leak between them.
 */
final class WorkerModeTest extends TestCase
{
    public function testConsecutiveRequestsAreIsolated(): void
    {
        $kernel = new TestKernel('test', true);
        $kernel->boot();
        $entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        new SchemaTool($entityManager)->createSchema($entityManager->getMetadataFactory()->getAllMetadata());
        foreach (['alpha', 'beta', 'gamma', 'alphabet'] as $title) {
            $entityManager->persist(new Post($title));
        }
        $entityManager->flush();
        $entityManager->clear();

        self::assertSame(
            ['titles' => ['alpha', 'alphabet'], 'page' => 1, 'total' => 2],
            $this->get($kernel, '/posts?form[filters][title]=alp'),
        );
        self::assertSame(
            ['titles' => ['beta', 'gamma'], 'page' => 2, 'total' => 4],
            $this->get($kernel, '/posts?page=2'),
        );
        self::assertSame(
            ['titles' => ['gamma'], 'page' => 1, 'total' => 1],
            $this->get($kernel, '/posts?form[filters][title]=gam'),
        );

        $kernel->shutdown();
    }

    public function testHandlersForTheSameCodeDoNotShareState(): void
    {
        $kernel = new TestKernel('test', true);
        $kernel->boot();
        $registry = $kernel->getContainer()->get('test.service_container')->get(QueryHandlerRegistry::class);
        self::assertInstanceOf(QueryHandlerRegistry::class, $registry);

        self::assertNotSame($registry->getQueryHandler('posts'), $registry->getQueryHandler('posts'));

        $kernel->shutdown();
    }

    private function get(TestKernel $kernel, string $uri): array
    {
        $response = $kernel->handle(Request::create($uri), catch: false);
        $kernel->terminate(Request::create($uri), $response);

        return json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }
}
