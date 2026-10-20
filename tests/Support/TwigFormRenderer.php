<?php

declare(strict_types=1);

namespace Sidus\FilterBundle\Tests\Support;

use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

/**
 * Renders a form with a standalone Twig environment: the Symfony form layouts, `@SidusFilter` pointing at the
 * bundle templates and the shipped translations.
 */
final class TwigFormRenderer
{
    public static function render(FormInterface $form, string $theme, string $locale = 'en'): string
    {
        $root = \dirname(__DIR__, 2);
        $loader = new FilesystemLoader();
        $loader->addPath(\dirname((string) new \ReflectionClass(FormExtension::class)->getFileName(), 2).'/Resources/views/Form');
        $loader->addPath($root.'/templates', 'SidusFilter');
        $loader->addPath($root.'/docs/examples', 'Examples');
        $twig = new Environment($loader, ['strict_variables' => true, 'cache' => false]);

        $translator = new Translator($locale);
        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addResource('yaml', $root.'/translations/messages.'.$locale.'.yaml', $locale);

        $renderer = new \Symfony\Component\Form\FormRenderer(new TwigRendererEngine([$theme], $twig));
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([FormRenderer::class => static fn (): FormRenderer => $renderer]));
        $twig->addExtension(new FormExtension());
        $twig->addExtension(new TranslationExtension($translator));

        return $twig->createTemplate('{{ form_start(form) }}{{ form_widget(form) }}{{ form_end(form) }}')->render(['form' => $form->createView()]);
    }
}
