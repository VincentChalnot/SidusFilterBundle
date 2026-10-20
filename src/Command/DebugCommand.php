<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Command;

use Sidus\FilterBundle\Definition\FilterDefinition;
use Sidus\FilterBundle\Definition\ListDefinition;
use Sidus\FilterBundle\Exception\UnknownListException;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;
use Sidus\FilterBundle\Registry\ListDefinitionRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'debug:sidus-filter', description: 'Lists the configured lists, or shows one list with its filters')]
final class DebugCommand extends Command
{
    public function __construct(
        private readonly ListDefinitionRegistry $definitions,
        private readonly FilterTypeRegistry $filterTypes,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('code', InputArgument::OPTIONAL, 'The code of a list');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $code = $input->getArgument('code');
        if (null === $code) {
            $rows = [];
            foreach ($this->definitions->codes() as $listCode) {
                $list = $this->definitions->get($listCode);
                $rows[] = [$list->code, $list->provider, $list->count, \count($list->filters)];
            }
            $io->table(['List', 'Provider', 'Count', 'Filters'], $rows);

            return Command::SUCCESS;
        }
        try {
            $list = $this->definitions->get($code);
        } catch (UnknownListException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
        $this->describe($io, $list);

        return Command::SUCCESS;
    }

    private function describe(SymfonyStyle $io, ListDefinition $list): void
    {
        $io->title(\sprintf('List "%s"', $list->code));
        $io->definitionList(
            ['Provider' => $list->provider],
            ['Options' => json_encode($list->options, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE)],
            ['Pagination' => \sprintf('%d per page, %d max', $list->perPage, $list->maxPerPage)],
            ['Count' => $list->count],
        );
        $rows = array_map(fn (FilterDefinition $filter): array => [
            $filter->publicName(),
            $filter->key,
            $filter->type,
            implode(', ', array_map('strval', $filter->attributes)),
            $filter->locked ? 'locked' : '',
            null === $filter->default ? '' : json_encode($filter->default, \JSON_UNESCAPED_UNICODE),
            implode(', ', array_map(static fn (string $class): string => substr(strrchr($class, '\\') ?: '\\'.$class, 1), $this->filterTypes->get($filter->type)->getRequiredOperations($filter))),
        ], $list->filters);
        $io->table(['Name', 'Key', 'Type', 'Attributes', 'Locked', 'Default', 'Criteria'], $rows);
    }
}
