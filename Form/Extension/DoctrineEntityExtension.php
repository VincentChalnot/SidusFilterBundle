<?php
/*
 * This file is part of the Sidus/FilterBundle package.
 *
 * Copyright (c) 2015-2021 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sidus\FilterBundle\Form\Extension;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Fixing default EntityType to accept ids as defaults
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class DoctrineEntityExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(
            new CallbackTransformer(
                static function ($value) use ($options) {
                    $entityManager = $options['em'];
                    if (!$entityManager instanceof EntityManagerInterface) {
                        throw new \UnexpectedValueException('Missing EntityManager in options');
                    }

                    $callback = static function ($item) use ($entityManager, $options) {
                        if (null === $item || is_a($item, $options['class'])) {
                            return $item;
                        }

                        return $entityManager->find($options['class'], $item);
                    };

                    if (null !== $value && $options['multiple'] && interface_exists(Collection::class)) {
                        return array_map($callback, $value);
                    }

                    return $callback($value);
                },
                static function ($value) {
                    return $value;
                },
            ),
        );
    }

    public static function getExtendedTypes(): iterable
    {
        return [EntityType::class];
    }
}
