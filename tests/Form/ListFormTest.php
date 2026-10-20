<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Form;

use PHPUnit\Framework\TestCase;
use Sidus\FilterBundle\Form\ListFormFactory;
use Sidus\FilterBundle\Registry\QueryHandlerRegistry;
use Sidus\FilterBundle\Tests\App\TestKernel;
use Sidus\FilterBundle\Tests\Support\EvenIdBuilder;
use Sidus\FilterBundle\Tests\Support\PostsSource;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Form\FormInterface;

final class ListFormTest extends TestCase
{
    public const array LIST = [
        'provider' => 'in_memory',
        'options' => ['source' => PostsSource::class],
        'pagination' => ['per_page' => 3, 'max_per_page' => 10],
        'filters' => [
            'title' => ['type' => 'text', 'options' => ['operators' => ['contains', 'starts_with']], 'form' => ['label' => 'Titre']],
            'summary' => ['type' => 'text'],
            'views' => ['type' => 'number', 'options' => ['operators' => ['eq', 'gt']]],
            'exact_status' => ['type' => 'exact', 'attributes' => ['status'], 'name' => 'estatus'],
            'status' => ['type' => 'choice', 'options' => ['choices' => ['draft', 'published', 'archived'], 'multiple' => true], 'default' => ['draft']],
            'kind' => ['type' => 'choice', 'options' => ['choices' => ['a' => 1, 'b' => 2]], 'attributes' => ['id']],
            'locked' => ['type' => 'exact', 'attributes' => ['status'], 'default' => 'published', 'locked' => true],
            'published' => ['type' => 'date_range', 'attributes' => ['publishedAt'], 'options' => ['timezone' => 'Europe/Paris'], 'default' => ['from' => '2026-01-01', 'to' => '2026-12-31']],
            'no_author' => ['type' => 'null', 'attributes' => ['author']],
            'has_summary' => ['type' => 'not_empty', 'attributes' => ['summary']],
            'pattern' => ['type' => 'regexp', 'attributes' => ['title'], 'options' => ['operators' => ['matches']]],
            'parity' => ['type' => 'native', 'options' => ['builder' => EvenIdBuilder::class], 'providers' => ['in_memory']],
            'order' => ['type' => 'sort', 'options' => ['columns' => ['id' => 'id', 'title' => 'title'], 'default' => [['column' => 'title', 'direction' => 'desc']]]],
        ],
    ];

    private TestKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = TestKernel::create(['posts' => self::LIST], static function (ContainerConfigurator $c): void {
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

    private function factory(): ListFormFactory
    {
        return $this->kernel->getContainer()->get(ListFormFactory::class);
    }

    private function create(): FormInterface
    {
        return $this->factory()->create($this->kernel->getContainer()->get(QueryHandlerRegistry::class)->getDefinition('posts'));
    }

    public function testBuildsOneFieldPerActiveNonLockedFilterWithAMapper(): void
    {
        $form = $this->create();

        self::assertSame('', $form->getName());
        self::assertSame('GET', $form->getConfig()->getMethod());
        self::assertSame(
            ['title', 'summary', 'views', 'estatus', 'status', 'kind', 'published', 'no_author', 'has_summary', 'pattern', 'order'],
            array_keys($form->get('filters')->all()),
            'locked and mapper-less (native) filters have no field; the public name is the field name',
        );
        self::assertTrue($form->has('page'));
    }

    public function testInitialDataIsTheNormalisedDefault(): void
    {
        $filters = $this->create()->get('filters');

        self::assertSame(['draft'], $filters->get('status')->getData());
        self::assertSame(['from' => '2026-01-01', 'to' => '2026-12-31'], array_map(static fn ($d) => $d->setTimezone(new \DateTimeZone('Europe/Paris'))->format('Y-m-d'), $filters->get('published')->getData()));
        self::assertSame('title', $filters->get('order')->getViewData() ? ltrim($filters->get('order')->getViewData(), '-') : null);
        self::assertSame('-title', $filters->get('order')->getViewData());
        self::assertNull($filters->get('title')->getData()['value'] ?? null);
        self::assertSame('contains', $filters->get('title')->get('operator')->getData());
    }

    public function testSubmittedViewDataBecomesRawInput(): void
    {
        $form = $this->create();

        $input = $this->factory()->submit($form, [
            'filters' => [
                'title' => ['operator' => 'starts_with', 'value' => 'hel'],
                'summary' => 'x',
                'views' => ['operator' => 'gt', 'value' => '20'],
                'status' => ['published', 'draft'],
                'kind' => '2',
                'published' => ['from' => '2026-02-01', 'to' => ''],
                'no_author' => '1',
                'pattern' => 'h.*',
                'locked' => 'draft',
                'parity' => 'even',
            ],
            'sort' => 'title',
            'page' => '2',
            'per_page' => '5',
        ]);

        self::assertSame([
            'title' => ['operator' => 'starts_with', 'value' => 'hel'],
            'summary' => 'x',
            'views' => ['operator' => 'gt', 'value' => '20'],
            'estatus' => '',
            'status' => ['published', 'draft'],
            'kind' => '2',
            'published' => ['from' => '2026-02-01', 'to' => ''],
            'no_author' => '1',
            'has_summary' => null,
            'pattern' => 'h.*',
            'order' => 'title',
        ], $input->filters);
        self::assertSame('2', $input->page);
        self::assertSame('5', $input->perPage);
    }

    public function testASubmittedFormGivesTheSameResultAsTheRawInput(): void
    {
        $registry = $this->kernel->getContainer()->get(QueryHandlerRegistry::class);
        $form = $this->create();
        $input = $this->factory()->submit($form, ['filters' => ['title' => ['operator' => 'contains', 'value' => 'hello'], 'status' => ['draft', 'published'], 'published' => ['from' => '', 'to' => '']]]);

        $result = $registry->createQueryHandler('posts')->handle($input);

        self::assertSame([1], array_column($result->items(), 'id'), 'the locked status filter keeps drafts out');
        self::assertTrue($result->violations()->isEmpty());
        self::assertSame('hello', $result->state()['filters']['title']['value']);
    }

    public function testNothingSubmittedKeepsTheDefaults(): void
    {
        $input = $this->factory()->submit($this->create(), []);

        self::assertSame(['draft'], $input->filters['status']);
        self::assertSame('-title', $input->filters['order']);
        self::assertSame('2026-01-01', $input->filters['published']['from']);
        self::assertNull($input->page);
    }

    public function testViolationsBecomeFormErrorsOnTheClosestField(): void
    {
        $registry = $this->kernel->getContainer()->get(QueryHandlerRegistry::class);
        $form = $this->create();
        $input = $this->factory()->submit($form, [
            'filters' => [
                'views' => ['operator' => 'gt', 'value' => 'abc'],
                'kind' => '9',
                'published' => ['from' => 'yesterday', 'to' => ''],
                'order' => 'nope',
            ],
            'page' => 'x',
        ]);

        $result = $registry->createQueryHandler('posts')->handle($input);
        $this->factory()->addViolations($form, $result);

        $errors = static fn (FormInterface $field): array => array_map(static fn ($e) => $e->getMessage(), iterator_to_array($field->getErrors()));
        self::assertFalse($form->isValid());
        self::assertContains('Expected a number.', $errors($form->get('filters')->get('views')->get('value')), 'the form may add its own transformation error next to it');
        self::assertContains('This value is not a valid choice.', $errors($form->get('filters')->get('kind')));
        self::assertContains('Expected an ISO 8601 date or date-time.', $errors($form->get('filters')->get('published')->get('from')));
        self::assertNotSame([], $errors($form->get('filters')->get('order')));
        self::assertContains('Expected a positive integer.', $errors($form->get('page')));
        self::assertSame('x', $input->page);
        self::assertSame('x', $form->get('page')->getViewData(), 'the invalid value stays in the form so the user can fix it');
    }

    public function testEachCallBuildsANewForm(): void
    {
        self::assertNotSame($this->create(), $this->create());
    }
}
