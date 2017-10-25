<?php

namespace Sidus\FilterBundle\Filter\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Sidus\FilterBundle\Filter\AbstractFilter;
use Sidus\FilterBundle\Filter\Type\Doctrine\DoctrineFilterTypeInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Doctrine's implementation for filter logic
 */
class DoctrineFilter extends AbstractFilter implements DoctrineFilterInterface
{
    /** @var DoctrineFilterTypeInterface */
    protected $filterType;

    /**
     * @param string                      $code
     * @param DoctrineFilterTypeInterface $filterType
     * @param array                       $options
     * @param array|null                  $attributes
     */
    public function __construct(
        $code,
        DoctrineFilterTypeInterface $filterType,
        array $options = null,
        array $attributes = null
    ) {
        $this->code = $code;
        $this->filterType = $filterType;
        $this->options = $options;
        $this->attributes = empty($attributes) ? [$code] : $attributes;
    }

    /**
     * @return DoctrineFilterTypeInterface
     */
    public function getFilterType()
    {
        return $this->filterType;
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $alias
     *
     * @return array
     */
    public function getDoctrineFormOptions(QueryBuilder $qb, $alias)
    {
        $defaultOptions = [
            'label' => $this->getLabel(),
            'required' => false,
        ];
        $typeOptions = $this->getFilterType()->getDoctrineFormOptions($this, $qb, $alias);

        return array_merge($defaultOptions, $typeOptions, $this->formOptions);
    }

    /**
     * @param FormInterface $form
     * @param QueryBuilder  $qb
     * @param string        $alias
     */
    public function handleForm(FormInterface $form, QueryBuilder $qb, $alias)
    {
        $this->getFilterType()->handleForm($this, $form, $qb, $alias);
    }

    /**
     * @param string $alias
     *
     * @return array
     */
    public function getFullAttributeReferences($alias)
    {
        $references = [];
        foreach ($this->getAttributes() as $attribute) {
            if (false === strpos($attribute, '.')) {
                $references[] = $alias.'.'.$attribute;
            } else {
                $references[] = $attribute;
            }
        }

        return $references;
    }
}
