<?php
namespace Oro\Bundle\FlexibleEntityBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FlexibleEntityBundle\Doctrine\ORM\FlexibleQueryBuilder;
use Oro\Bundle\FlexibleEntityBundle\Exception\UnknownAttributeException;
use Oro\Bundle\FlexibleEntityBundle\Model\Behavior\TranslatableInterface;
use Oro\Bundle\FlexibleEntityBundle\Model\Behavior\ScopableInterface;
use Oro\Bundle\FlexibleEntityBundle\Entity\Attribute;
use Oro\Bundle\FlexibleEntityBundle\AttributeType\AbstractAttributeType;
use Oro\Bundle\FlexibleEntityBundle\Model\AbstractFlexible;

/**
 * Base repository for flexible entity
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2012 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/MIT MIT
 *
 */
class FlexibleEntityRepository extends EntityRepository implements TranslatableInterface, ScopableInterface
{
    /**
     * Flexible entity config
     * @var array
     */
    protected $flexibleConfig;

    /**
     * Locale code
     * @var string
     */
    protected $locale;

    /**
     * Scope code
     * @var string
     */
    protected $scope;

    /**
     * Entity alias
     * @var string
     */
    protected $entityAlias;

    /**
     * Get flexible entity config
     *
     * @return array $config
     */
    public function getFlexibleConfig()
    {
        return $this->flexibleConfig;
    }

    /**
     * Set flexible entity config

     * @param array $config
     *
     * @return FlexibleEntityRepository
     */
    public function setFlexibleConfig($config)
    {
        $this->flexibleConfig = $config;

        return $this;
    }

    /**
     * Return asked locale code or default one
     *
     * @return string
     */
    public function getLocale()
    {
        if (!$this->locale) {
            // use default locale
            $this->locale = $this->flexibleConfig['default_locale'];
        }

        return $this->locale;
    }

    /**
     * Set locale code
     *
     * @param string $code
     *
     * @return FlexibleEntityRepository
     */
    public function setLocale($code)
    {
        $this->locale = $code;

        return $this;
    }

    /**
     * Return asked scope code or default one
     *
     * @return string
     */
    public function getScope()
    {
        if (!$this->scope) {
            // use default scope
            $this->scope = $this->flexibleConfig['default_scope'];
        }

        return $this->scope;
    }

    /**
     * Set scope code
     *
     * @param string $code
     *
     * @return FlexibleEntityRepository
     */
    public function setScope($code)
    {
        $this->scope = $code;

        return $this;
    }

    /**
     * Finds attributes
     *
     * @param array $attributeCodes attribute codes
     *
     * @throws UnknownAttributeException
     *
     * @return array The objects.
     */
    public function getCodeToAttributes(array $attributeCodes)
    {
        // prepare entity attributes query
        $attributeAlias = 'Attribute';
        $attributeName = $this->flexibleConfig['attribute_class'];
        $attributeRepo = $this->_em->getRepository($attributeName);
        $qb = $attributeRepo->createQueryBuilder($attributeAlias);
        $qb->andWhere('Attribute.entityType = :type')->setParameter('type', $this->_entityName);

        // filter by code
        if (!empty($attributeCodes)) {
            $qb->andWhere($qb->expr()->in('Attribute.code', $attributeCodes));
        }

        // prepare associative array
        $attributes = $qb->getQuery()->getResult();
        $codeToAttribute = array();
        foreach ($attributes as $attribute) {
            $codeToAttribute[$attribute->getCode()]= $attribute;
        }

        // raise exception
        if (!empty($attributeCodes) and count($attributeCodes) != count($codeToAttribute)) {
            $missings = array_diff($attributeCodes, array_keys($codeToAttribute));
            throw new UnknownAttributeException(
                'Attribute(s) with code '.implode(', ', $missings).' not exists for entity '.$this->_entityName
            );
        }

        return $codeToAttribute;
    }

    /**
     * Find flexible attribute by code
     *
     * @param string $code
     *
     * @throws UnknownAttributeException
     *
     * @return AbstractEntityAttribute
     */
    public function findAttributeByCode($code)
    {
        $attributeName = $this->flexibleConfig['attribute_class'];
        $attributeRepo = $this->_em->getRepository($attributeName);
        $attribute = $attributeRepo->findOneBy(array('entityType' => $this->_entityName, 'code' => $code));

        return $attribute;
    }

    /**
     * TODO : should be remove to use the basic one by default and explicitely use createFlexibleQueryBuilder to add
     * join to related tables, should be updated in grid
     *
     * @param string $alias alias for entity
     *
     * @return QueryBuilder $qb
     */
    public function createQueryBuilder($alias)
    {
        return $this->createFlexibleQueryBuilder($alias);
    }

    /**
     * Create a new QueryBuilder instance that allow to automatically join on attribute values and allow doctrine
     * hydratation as real flexible entity, value, option and attributes
     *
     * @param string  $alias          alias for entity
     * @param boolean $attributeCodes add selects on values only for this attribute codes list
     *
     * @return FlexibleQueryBuilder $qb
     */
    public function createFlexibleQueryBuilder($alias, $attributeCodes = null)
    {
        $this->entityAlias = $alias;
        $qb = new FlexibleQueryBuilder($this->_em);

        $qb->setLocale($this->getLocale());
        $qb->setScope($this->getScope());

        $qb->select($alias, 'Value', 'Attribute', 'ValueOption', 'AttributeOptionValue')
            ->from($this->_entityName, $this->entityAlias);
        $this->addJoinToValueTables($qb, $alias);

        if (!empty($attributeCodes)) {
            $qb->where($qb->expr()->in('Attribute.code', $attributeCodes));
            $qb->orWhere($qb->expr()->isNull('Attribute.code'));
        }

        return $qb;
    }

    /**
     * Add join to values tables
     *
     * @param QueryBuilder $qb
     */
    protected function addJoinToValueTables(QueryBuilder $qb)
    {
        $qb->leftJoin($this->entityAlias.'.values', 'Value')
            ->leftJoin('Value.attribute', 'Attribute')
            ->leftJoin('Value.options', 'ValueOption')
            ->leftJoin('ValueOption.optionValues', 'AttributeOptionValue');
    }

    /**
     * Finds entities and attributes values by a set of criteria, same coverage than findBy
     *
     * @param array      $attributes attribute codes
     * @param array      $criteria   criterias
     * @param array|null $orderBy    order by
     * @param int|null   $limit      limit
     * @param int|null   $offset     offset
     *
     * @return array The objects.
     */
    public function findByWithAttributesQB(array $attributes = array(), array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
    {
        $qb = $this->createFlexibleQueryBuilder('Entity', $attributes);
        $codeToAttribute = $this->getCodeToAttributes($attributes);
        $attributes = array_keys($codeToAttribute);

        // add criterias
        if (!is_null($criteria)) {
            foreach ($criteria as $attCode => $attValue) {
                if (in_array($attCode, $attributes)) {
                    $attribute = $codeToAttribute[$attCode];
                    $qb->addAttributeFilter($attribute, '=', $attValue);
                } else {
                    $qb->andWhere($qb->expr()->eq($this->entityAlias.'.'.$attCode, $qb->expr()->literal($attValue)));
                }
            }
        }

        // add sorts
        if (!is_null($orderBy)) {
            foreach ($orderBy as $attCode => $direction) {
                if (in_array($attCode, $attributes)) {
                    $attribute = $codeToAttribute[$attCode];
                    $qb->addAttributeOrderBy($attribute, $direction);
                } else {
                    $qb->addOrderBy($this->entityAlias.'.'.$attCode, $direction);
                }
            }
        }

        // use doctrine paginator to avoid count problem with left join of values
        if (!is_null($offset) and !is_null($limit)) {
            $qb->setFirstResult($offset)->setMaxResults($limit);
            $paginator = new Paginator($qb->getQuery(), $fetchJoinCollection = true);

            return $paginator;
        }

        return $qb;
    }

    /**
     * Finds entities and attributes values by a set of criteria, same coverage than findBy
     *
     * @param array      $attributes attribute codes
     * @param array      $criteria   criterias
     * @param array|null $orderBy    order by
     * @param int|null   $limit      limit
     * @param int|null   $offset     offset
     *
     * @return array The objects.
     */
    public function findByWithAttributes(array $attributes = array(), array $criteria = null, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this
            ->findByWithAttributesQB($attributes, $criteria, $orderBy, $limit, $offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * TODO : allow grid integration, grid should directly use query builder
     * Apply a filter by attribute value
     *
     * @param QueryBuilder $qb             query builder to update
     * @param string       $attributeCode  attribute code
     * @param string|array $attributeValue value(s) used to filter
     * @param string       $operator       operator to use
     */
    public function applyFilterByAttribute(QueryBuilder $qb, $attributeCode, $attributeValue, $operator = '=')
    {
        $codeToAttribute = $this->getCodeToAttributes(array());
        $attributeCodes = array_keys($codeToAttribute);
        if (in_array($attributeCode, $attributeCodes)) {
            $attribute = $codeToAttribute[$attributeCode];
            $qb->addAttributeFilter($attribute, $operator, $attributeValue);

        } else {
            $field = $this->entityAlias.'.'.$attributeCode;
            $qb->andWhere($qb->prepareCriteriaCondition($field, $operator, $attributeValue));
        }
    }

    /**
     * TODO : allow grid integration, grid should directly use query builder
     * Apply a sort by attribute value
     *
     * @param QueryBuilder $qb            query builder to update
     * @param string       $attributeCode attribute code
     * @param string       $direction     direction to use
     */
    public function applySorterByAttribute(QueryBuilder $qb, $attributeCode, $direction)
    {
        $codeToAttribute = $this->getCodeToAttributes(array());
        $attributeCodes = array_keys($codeToAttribute);
        if (in_array($attributeCode, $attributeCodes)) {
            $attribute = $codeToAttribute[$attributeCode];
            $qb->addAttributeOrderBy($attribute, $direction);
        } else {
            $qb->addOrderBy($this->entityAlias.'.'.$attributeCode, $direction);
        }
    }

    /**
     * Find entity with attributes values
     *
     * @param int $id entity id
     *
     * @return Entity the entity
     */
    public function findWithAttributes($id)
    {
        $flexibles = $this->findByWithAttributes(array(), array('id' => $id));

        return count($flexibles) ? current($flexibles) : null;
    }

    /**
     * Load a flexible entity with only localized values
     *
     * @param integer $id
     *
     * @return AbstractFlexible
     */
    public function findWithLocalizedValuesAndSortedAttributes($id)
    {
        return $this
            ->findByWithAttributesQB(array(), array('id' => $id))
            ->orderBy('Attribute.sortOrder')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
