<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Form;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Form\ListFormFactory;
use Sidus\FilterBundle\Registry\QueryHandlerRegistry;
use Sidus\FilterBundle\Tests\App\TestKernel;
use Sidus\FilterBundle\Tests\Support\EvenIdBuilder;
use Sidus\FilterBundle\Tests\Support\PostsSource;
use Sidus\FilterBundle\Tests\Support\TwigFormRenderer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Twig render smoke tests: every theme renders every mapped filter type, a sort state and form errors.
 */
final class ThemeRenderingTest extends TestCase
{
    private TestKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = TestKernel::create(['posts' => ListFormTest::LIST], static function (ContainerConfigurator $c): void {
            $services = $c->services()->defaults()->autoconfigure();
            $services->set(PostsSource::class);
            $services->set(EvenIdBuilder::class);
        });
        $this->kernel->boot();
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
    }

    private function form(bool $withErrors = false): FormInterface
    {
        $container = $this->kernel->getContainer();
        $factory = $container->get(ListFormFactory::class);
        $form = $factory->create($container->get(QueryHandlerRegistry::class)->getDefinition('posts'));
        $input = $factory->submit($form, ['sort' => 'title', 'filters' => $withErrors ? ['views' => ['operator' => 'gt', 'value' => 'abc']] : []]);
        $factory->addViolations($form, $container->get(QueryHandlerRegistry::class)->createQueryHandler('posts')->handle($input));

        return $form;
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function themes(): iterable
    {
        yield 'base' => ['@SidusFilter/form/base.html.twig'];
        yield 'bootstrap 6' => ['@SidusFilter/form/bootstrap_6.html.twig'];
        yield 'bulma 1' => ['@SidusFilter/form/bulma_1.html.twig'];
        yield 'tailwind example' => ['@Examples/tailwind.html.twig'];
    }

    #[DataProvider('themes')]
    public function testEveryThemeRendersEveryFilterTypeTheSortAndTheDateRange(string $theme): void
    {
        $html = TwigFormRenderer::render($this->form(), $theme);

        self::assertStringContainsString('<form method="get">', $html);
        foreach (['title', 'summary', 'views', 'estatus', 'status', 'kind', 'published', 'no_author', 'has_summary', 'pattern', 'order'] as $name) {
            self::assertMatchesRegularExpression('/name="filters\['.$name.'\](\[|")/', $html, 'field '.$name);
        }
        self::assertStringContainsString('name="filters[order]" value="-title"', $html, 'first click on the primary column toggles to descending');
        self::assertStringContainsString('name="filters[order]" value="id"', $html);
        self::assertStringContainsString('aria-sort="ascending"', $html);
        self::assertStringContainsString('name="filters[published][from]"', $html);
        self::assertStringContainsString('>to<', $html, 'sidus.filter.date_range.to is translated');
        self::assertStringContainsString('Contains', $html, 'operator labels are translated');
        self::assertStringContainsString('name="page"', $html);
        self::assertStringNotContainsString('filters[locked]', $html);
        self::assertStringNotContainsString('filters[parity]', $html);
        $dom = new \DOMDocument();
        self::assertTrue($dom->loadHTML('<!DOCTYPE html><body>'.$html, \LIBXML_NOERROR | \LIBXML_NOWARNING), 'the output is parseable HTML');
    }

    public function testTheBaseThemeIsUnstyled(): void
    {
        $html = TwigFormRenderer::render($this->form(), '@SidusFilter/form/base.html.twig');

        self::assertStringNotContainsString('btn', $html);
        self::assertStringNotContainsString('class="input"', $html);
    }

    public function testBootstrapMarkup(): void
    {
        $html = TwigFormRenderer::render($this->form(true), '@SidusFilter/form/bootstrap_6.html.twig');

        self::assertStringContainsString('btn btn-outline-secondary', $html);
        self::assertStringContainsString('input-group', $html);
        self::assertStringContainsString('form-select', $html);
        self::assertStringContainsString('invalid-feedback', $html, 'violations render as form errors');
        self::assertStringContainsString('Expected a number.', $html);
    }

    public function testBulmaMarkup(): void
    {
        $html = TwigFormRenderer::render($this->form(true), '@SidusFilter/form/bulma_1.html.twig');

        self::assertStringContainsString('<div class="select', $html);
        self::assertStringContainsString('class="field"', $html);
        self::assertStringContainsString('class="control"', $html);
        self::assertStringContainsString('class="label', $html);
        self::assertStringContainsString('class="input', $html);
        self::assertStringContainsString('<p class="help is-danger">Expected a number.</p>', $html);
        self::assertStringContainsString('button is-active', $html);
    }

    public function testTranslationsAreComplete(): void
    {
        $en = Yaml::parseFile(\dirname(__DIR__, 2).'/translations/messages.en.yaml');
        $fr = Yaml::parseFile(\dirname(__DIR__, 2).'/translations/messages.fr.yaml');

        self::assertSame($this->flatten($en), $this->flatten($fr), 'en and fr define the same keys');
        self::assertSame('to', $en['sidus']['filter']['date_range']['to']);
        self::assertSame("jusqu'à", $fr['sidus']['filter']['date_range']['to']);
        self::assertStringContainsString('jusqu&#039;à', TwigFormRenderer::render($this->form(), '@SidusFilter/form/base.html.twig', 'fr'));
        self::assertStringContainsString('Contient', TwigFormRenderer::render($this->form(), '@SidusFilter/form/base.html.twig', 'fr'));
    }

    public function testNoTemplateUsesTheDeprecatedSpacelessTag(): void
    {
        foreach (glob(\dirname(__DIR__, 2).'/{templates,docs/examples}/{*,form/*}.twig', \GLOB_BRACE) as $file) {
            self::assertStringNotContainsString('spaceless', (string) file_get_contents($file), $file);
        }
    }

    /**
     * @param array<string, mixed> $tree
     *
     * @return list<string>
     */
    private function flatten(array $tree, string $prefix = ''): array
    {
        $keys = [];
        foreach ($tree as $key => $value) {
            $path = '' === $prefix ? (string) $key : $prefix.'.'.$key;
            array_push($keys, ...(\is_array($value) ? $this->flatten($value, $path) : [$path]));
        }
        sort($keys);

        return $keys;
    }
}
