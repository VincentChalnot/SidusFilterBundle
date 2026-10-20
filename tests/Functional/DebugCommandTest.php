<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Tests\App\TestKernel;
use Sidus\FilterBundle\Tests\Support\PostsSource;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

final class DebugCommandTest extends TestCase
{
    private TestKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = TestKernel::create(
            ['posts' => [
                'provider' => 'in_memory',
                'options' => ['source' => PostsSource::class],
                'count' => 'none',
                'filters' => [
                    'title' => ['type' => 'text', 'attributes' => ['title', 'author.name'], 'options' => ['operators' => ['contains', 'not_equals']]],
                    'status' => ['type' => 'choice', 'options' => ['choices' => ['draft', 'published']], 'default' => 'published', 'locked' => true],
                    'es_only' => ['type' => 'whatever', 'providers' => ['elasticsearch']],
                ],
            ], 'other' => ['provider' => 'in_memory', 'options' => ['source' => PostsSource::class]]],
            static fn (ContainerConfigurator $c) => $c->services()->defaults()->autoconfigure()->set(PostsSource::class),
        );
        $this->kernel->boot();
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
    }

    private function execute(array $input): CommandTester
    {
        $tester = new CommandTester(new Application($this->kernel)->find('debug:sidus-filter'));
        $tester->execute($input);

        return $tester;
    }

    public function testListsTheLists(): void
    {
        $tester = $this->execute([]);

        self::assertSame(0, $tester->getStatusCode());
        self::assertMatchesRegularExpression('/posts\s+in_memory\s+none\s+2/', $tester->getDisplay());
        self::assertMatchesRegularExpression('/other\s+in_memory\s+auto\s+0/', $tester->getDisplay());
    }

    public function testDescribesAListWithItsActiveFiltersAndTheCriteriaTheyEmit(): void
    {
        $display = $this->execute(['code' => 'posts'])->getDisplay();

        self::assertStringContainsString('List "posts"', $display);
        self::assertStringContainsString('title, author.name', $display);
        self::assertMatchesRegularExpression('/title\s+title\s+text\s+title, author\.name\s+.*AnyOf/s', $display);
        self::assertStringContainsString('locked', $display);
        self::assertStringContainsString('"published"', $display);
        self::assertStringNotContainsString('es_only', $display, 'filters for another provider are not part of the list');
    }

    public function testUnknownListFails(): void
    {
        $tester = $this->execute(['code' => 'nope']);

        self::assertSame(1, $tester->getStatusCode());
        self::assertStringContainsString('Unknown list "nope", available: posts, other.', $tester->getDisplay());
    }
}
