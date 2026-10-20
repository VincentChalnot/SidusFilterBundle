<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Layering;

use PHPUnit\Framework\TestCase;

/**
 * The core imports nothing optional; the optional adapters live in Form/, Http/ and Pagerfanta/ and are only
 * registered when their package is there.
 */
final class LayeringTest extends TestCase
{
    private const string SRC = __DIR__.'/../../src';

    /**
     * Namespace prefix => the only places of src/ allowed to use it.
     */
    private const array OPTIONAL = [
        'Symfony\Component\Form' => ['Form/', 'SidusFilterBundle.php'],
        'Symfony\Component\HttpFoundation' => ['Http/'],
        'Symfony\Component\Console' => ['Command/'],
        'Symfony\Bridge\Twig' => [],
        'Twig\\' => [],
        'Pagerfanta\\' => ['Pagerfanta/'],
        'Doctrine\\' => [],
        'Sidus\FilterBundle\Form' => ['Form/', 'SidusFilterBundle.php'],
        'Sidus\FilterBundle\Http' => ['Http/'],
        'Sidus\FilterBundle\Pagerfanta' => ['Pagerfanta/'],
    ];

    public function testOnlyTheAdaptersImportOptionalPackages(): void
    {
        $violations = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::SRC, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ('php' !== $file->getExtension()) {
                continue;
            }
            $relative = substr($file->getPathname(), \strlen(self::SRC) + 1);
            $code = (string) file_get_contents($file->getPathname());
            foreach (self::OPTIONAL as $namespace => $allowed) {
                if (1 !== preg_match('/(?<![A-Za-z])'.preg_quote($namespace, '/').'/', $code)) {
                    continue;
                }
                if (!array_filter($allowed, static fn (string $prefix): bool => str_starts_with($relative, $prefix))) {
                    $violations[] = $relative.' uses '.$namespace;
                }
            }
        }

        self::assertSame([], $violations);
    }

    public function testOnlyTestKitCodeUsesPhpunit(): void
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::SRC, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ('php' === $file->getExtension() && !str_contains($file->getPathname(), '/Test/')) {
                self::assertStringNotContainsString('PHPUnit', (string) file_get_contents($file->getPathname()), $file->getPathname());
            }
        }
    }

    public function testTheContainerCompilesAndServesWithoutTheOptionalPackages(): void
    {
        $output = [];
        exec(escapeshellarg(\PHP_BINARY).' '.escapeshellarg(__DIR__.'/../Support/boot_without_optional_packages.php').' 2>&1', $output, $status);
        $output = implode("\n", $output);

        self::assertSame(0, $status, $output);
        self::assertSame([
            'form_available' => false,
            'pagerfanta_available' => false,
            'twig_available' => false,
            'form_factory' => false,
            'ids' => [2, 1],
            'total' => 2,
            'http' => true,
        ], json_decode($output, true), 'the simulation hides the packages and the core still works');
    }
}
