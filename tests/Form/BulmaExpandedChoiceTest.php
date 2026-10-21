<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Form;

use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Form\ListFormFactory;
use Sidus\FilterBundle\Registry\QueryHandlerRegistry;
use Sidus\FilterBundle\Tests\App\TestKernel;
use Sidus\FilterBundle\Tests\Support\PostsSource;
use Sidus\FilterBundle\Tests\Support\TwigFormRenderer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Forms;

/**
 * The bulma_1 theme renders expanded choices (checkboxes / radios) and single checkboxes with one label per option,
 * in the Bulma markup: `.checkboxes` / `.radios` wrapping `label.checkbox` / `label.radio` around the input.
 */
final class BulmaExpandedChoiceTest extends TestCase
{
    private const THEME = '@SidusFilter/form/bulma_1.html.twig';

    private TestKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = TestKernel::create(['posts' => [
            'provider' => 'in_memory',
            'options' => ['source' => PostsSource::class],
            'filters' => [
                'status' => [
                    'type' => 'choice',
                    'options' => ['choices' => ['Draft' => 'draft', 'Published' => 'published', 'Archived' => 'archived'], 'multiple' => true],
                    'default' => ['published'],
                    'form' => ['options' => ['expanded' => true]],
                ],
                'kind' => [
                    'type' => 'choice',
                    'options' => ['choices' => ['Alpha' => 'a', 'Beta' => 'b']],
                    'attributes' => ['kind'],
                    'default' => 'b',
                    'form' => ['options' => ['expanded' => true, 'placeholder' => null]],
                ],
                'no_author' => ['type' => 'null', 'attributes' => ['author'], 'form' => ['label' => 'Without author']],
                'title' => ['type' => 'text'],
            ],
        ]], static function (ContainerConfigurator $c): void {
            $c->services()->defaults()->autoconfigure()->set(PostsSource::class);
        });
        $this->kernel->boot();
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
    }

    private function dom(): \DOMXPath
    {
        $container = $this->kernel->getContainer();
        $factory = $container->get(ListFormFactory::class);
        $form = $factory->create($container->get(QueryHandlerRegistry::class)->getDefinition('posts'));
        $html = TwigFormRenderer::render($form, self::THEME);

        $dom = new \DOMDocument();
        $dom->loadHTML('<!DOCTYPE html><body>'.$html, \LIBXML_NOERROR | \LIBXML_NOWARNING);

        return new \DOMXPath($dom);
    }

    private function nodes(\DOMXPath $xpath, string $query): int
    {
        return $xpath->query($query)->length;
    }

    public function testAnExpandedMultipleChoiceIsACheckboxesGroupWithOneWrappingLabelPerOption(): void
    {
        $xpath = $this->dom();
        $group = '//div[@id="filters_status"]';

        self::assertSame(1, $this->nodes($xpath, $group.'[contains(concat(" ", normalize-space(@class), " "), " checkboxes ")]'), 'the wrapper is .checkboxes');
        foreach (['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'] as $value => $label) {
            self::assertSame(1, $this->nodes($xpath, $group.'//label[normalize-space(.)="'.$label.'"]'), $label.' is labelled exactly once');
            self::assertSame(1, $this->nodes($xpath, $group.'//label[@class="checkbox"][normalize-space(.)="'.$label.'"]/input[@type="checkbox"][@value="'.$value.'"]'), $label.' input is wrapped by label.checkbox');
        }
        self::assertSame(3, $this->nodes($xpath, $group.'//label'), 'no other label in the group');
        self::assertSame(0, $this->nodes($xpath, $group.'//label[contains(concat(" ", normalize-space(@class), " "), " label ")]'), 'no Bulma field label inside the group');
        self::assertSame(1, $this->nodes($xpath, $group.'//input[@checked]'), 'only the default is checked');
        self::assertSame(1, $this->nodes($xpath, $group.'//input[@value="published"][@checked]'));
    }

    public function testAnExpandedSingleChoiceIsARadiosGroupWithOneWrappingLabelPerOption(): void
    {
        $xpath = $this->dom();
        $group = '//div[@id="filters_kind"]';

        self::assertSame(1, $this->nodes($xpath, $group.'[contains(concat(" ", normalize-space(@class), " "), " radios ")]'), 'the wrapper is .radios');
        foreach (['a' => 'Alpha', 'b' => 'Beta'] as $value => $label) {
            self::assertSame(1, $this->nodes($xpath, $group.'//label[normalize-space(.)="'.$label.'"]'), $label.' is labelled exactly once');
            self::assertSame(1, $this->nodes($xpath, $group.'//label[@class="radio"][normalize-space(.)="'.$label.'"]/input[@type="radio"][@value="'.$value.'"]'), $label.' input is wrapped by label.radio');
        }
        self::assertSame(2, $this->nodes($xpath, $group.'//label'), 'no other label in the group');
        self::assertSame(1, $this->nodes($xpath, $group.'//input[@value="b"][@checked]'));
    }

    public function testTheGroupKeepsOneFieldLabelAndNoCheckboxesClassLeaksToOtherFields(): void
    {
        $xpath = $this->dom();

        self::assertSame(1, $this->nodes($xpath, '//label[@class="label"][normalize-space(.)="Status"]'), 'the filter label stays once, on the field');
        self::assertSame(0, $this->nodes($xpath, '//input[@id="filters_title"]/ancestor::*[contains(@class, "checkboxes") or contains(@class, "radios")]'));
        self::assertSame(1, $this->nodes($xpath, '//div[contains(concat(" ", normalize-space(@class), " "), " checkboxes ")]'));
        self::assertSame(1, $this->nodes($xpath, '//div[contains(concat(" ", normalize-space(@class), " "), " radios ")]'));
    }

    public function testASingleCheckboxIsLabelledOnce(): void
    {
        $xpath = $this->dom();

        self::assertSame(1, $this->nodes($xpath, '//label[normalize-space(.)="Without author"]'), 'one label, no duplicate');
        self::assertSame(1, $this->nodes($xpath, '//label[@class="checkbox"][normalize-space(.)="Without author"]/input[@type="checkbox"][@name="filters[no_author]"]'));
    }

    public function testAStandaloneRadioAndALabellessCheckboxAreLabelledOnceOrNotAtAll(): void
    {
        $form = Forms::createFormFactory()->createNamed('f', FormType::class)
            ->add('only', RadioType::class, ['label' => 'Only one', 'required' => false])
            ->add('quiet', CheckboxType::class, ['label' => false, 'required' => false]);
        $dom = new \DOMDocument();
        $dom->loadHTML('<!DOCTYPE html><body>'.TwigFormRenderer::render($form, self::THEME), \LIBXML_NOERROR | \LIBXML_NOWARNING);
        $xpath = new \DOMXPath($dom);

        self::assertSame(1, $this->nodes($xpath, '//label[normalize-space(.)="Only one"]'), 'the radio label is rendered once');
        self::assertSame(1, $this->nodes($xpath, '//label[@class="radio"][normalize-space(.)="Only one"]/input[@type="radio"]'));
        self::assertSame(2, $this->nodes($xpath, '//label'), 'a false label renders no text, only the wrapping label.checkbox');
        self::assertSame('', trim($xpath->query('//label[@class="checkbox"]')->item(0)->textContent ?? 'missing'), 'no text for a false label');
    }
}
