<?php

namespace Pim\Bundle\CatalogBundle\Doctrine\MongoDBODM;

use Doctrine\MongoDB\Query\Builder;
use Doctrine\MongoDB\Query\Expr;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pim\Bundle\CatalogBundle\AttributeType\AbstractAttributeType;
use Pim\Bundle\CatalogBundle\Doctrine\CompletenessGeneratorInterface;
use Pim\Bundle\CatalogBundle\Model\ChannelInterface;
use Pim\Component\Catalog\LocaleInterface;
use Pim\Bundle\CatalogBundle\Repository\ChannelRepositoryInterface;
use Pim\Bundle\CatalogBundle\Repository\FamilyRepositoryInterface;
use Pim\Component\Catalog\Model\AttributeInterface;
use Pim\Component\Catalog\Model\FamilyInterface;
use Pim\Component\Catalog\Model\ProductInterface;

/**
 * Generate the completeness when Product are in MongoDBODM
 * storage. Please note that the generation for several products
 * is done on the MongoDB via a JS generated by the application via HTTP.
 *
 * This generator is only able to generate completeness for one product
 *
 * @author    Benoit Jacquemont <benoit@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CompletenessGenerator implements CompletenessGeneratorInterface
{
    /** @var DocumentManager */
    protected $documentManager;

    /** @var string */
    protected $productClass;

    /** @var ChannelRepositoryInterface */
    protected $channelRepository;

    /** @var FamilyRepositoryInterface */
    protected $familyRepository;

    /**
     * Constructor
     *
     * @param DocumentManager            $documentManager
     * @param string                     $productClass
     * @param ChannelRepositoryInterface $channelRepository
     * @param FamilyRepositoryInterface  $familyRepository
     */
    public function __construct(
        DocumentManager $documentManager,
        $productClass,
        ChannelRepositoryInterface $channelRepository,
        FamilyRepositoryInterface $familyRepository
    ) {
        $this->documentManager   = $documentManager;
        $this->productClass      = $productClass;
        $this->channelRepository = $channelRepository;
        $this->familyRepository  = $familyRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function generateMissingForProduct(ProductInterface $product)
    {
        $this->generate($product);

        $this->documentManager->refresh($product);
    }

    /**
     * {@inheritdoc}
     */
    public function generateMissingForChannel(ChannelInterface $channel)
    {
        $this->generate(null, $channel);
    }

    /**
     * {@inheritdoc}
     */
    public function generateMissing()
    {
        $this->generate();
    }

    /**
     * Generate missing completenesses for a channel if provided or a product
     * if provided. CAUTION: the product must be already flushed to the DB
     *
     * @param \Pim\Component\Catalog\Model\ProductInterface $product
     * @param ChannelInterface $channel
     */
    protected function generate(ProductInterface $product = null, ChannelInterface $channel = null)
    {
        $productsQb = $this->documentManager->createQueryBuilder($this->productClass);

        $familyReqs = $this->getFamilyRequirements($product, $channel);

        $this->applyFindMissingQuery($productsQb, $product, $channel);

        $products = $productsQb->select('family', 'normalizedData')
            ->hydrate(false)
            ->getQuery()
            ->execute();

        foreach ($products as $product) {
            $familyId = $product['family'];
            $completenesses = $this->getCompletenesses($product['normalizedData'], $familyReqs[$familyId]);
            $this->saveCompletenesses($product['_id'], $completenesses);
        }
    }

    /**
     * Generate completenesses data from normalized data from product and
     * its family requirements. Only missing completenesses are generated.
     *
     * @param array $normalizedData
     * @param array $normalizedReqs
     *
     * @return array $completenesses
     */
    public function getCompletenesses(array $normalizedData, array $normalizedReqs)
    {
        $completenesses = [];
        $allCompletenesses = false;

        if ((!isset($normalizedData['completenesses'])) ||
            (null === $normalizedData['completenesses']) ||
            (0 === count($normalizedData['completenesses']))
        ) {
            $missingComps = array_keys($normalizedReqs);
            $allCompletenesses = true;
        } else {
            $missingComps = array_diff(array_keys($normalizedReqs), array_keys($normalizedData['completenesses']));
        }

        $normalizedData = array_filter(
            $normalizedData,
            function ($value) {
                return (null !== $value);
            }
        );

        $dataFields = array_keys($normalizedData);

        foreach ($missingComps as $missingComp) {
            $requiredCount = $this->getRequiredCount($normalizedReqs, $missingComp);
            $missingCount  = $this->getMissingCount($normalizedReqs, $normalizedData, $dataFields, $missingComp);

            $ratio = round(($requiredCount - $missingCount) / $requiredCount * 100);

            $compObject = [
                'missingCount'  => $missingCount,
                'requiredCount' => $requiredCount,
                'ratio'         => $ratio,
                'channel'       => $normalizedReqs[$missingComp]['channel'],
                'locale'        => $normalizedReqs[$missingComp]['locale']
            ];

            $completenesses[$missingComp] = [
                'object' => $compObject,
                'ratio'  => $ratio
            ];
        }

        return ['completenesses' => $completenesses, 'all' => $allCompletenesses];
    }

    /**
     * @param array  $normalizedReqs
     * @param string $missingComp
     *
     * @return int
     */
    protected function getRequiredCount(array $normalizedReqs, $missingComp)
    {
        $attributesReqs = $normalizedReqs[$missingComp]['reqs']['attributes'];
        $pricesReqs     = $normalizedReqs[$missingComp]['reqs']['prices'];

        return count($attributesReqs) + count($pricesReqs);
    }

    /**
     * @param array  $normalizedReqs
     * @param array  $normalizedData
     * @param array  $dataFields
     * @param string $missingComp
     *
     * @return int
     */
    protected function getMissingCount(array $normalizedReqs, array $normalizedData, array $dataFields, $missingComp)
    {
        $attributesReqs = $normalizedReqs[$missingComp]['reqs']['attributes'];
        $pricesReqs     = $normalizedReqs[$missingComp]['reqs']['prices'];

        $missingAttributes = array_diff($attributesReqs, $dataFields);

        $missingPricesCount = count($pricesReqs);

        foreach ($pricesReqs as $priceField => $currencies) {
            if (isset($normalizedData[$priceField]) &&
                count(array_diff($currencies, array_keys($normalizedData[$priceField]))) === 0) {
                $missingPricesCount--;
            }
        }

        return count($missingAttributes) + $missingPricesCount;
    }

    /**
     * Save the completenesses data for the product directly to MongoDB.
     *
     * @param string $productId
     * @param array  $compData
     */
    protected function saveCompletenesses($productId, array $compData)
    {
        $completenesses = $compData['completenesses'];
        $all = $compData['all'];

        $collection = $this->documentManager->getDocumentCollection($this->productClass);

        $query = ['_id' => $productId];
        $options = ['multiple' => false];

        if ($all) {
            $compObjects = [];
            $normalizedComps = [];

            foreach ($completenesses as $key => $value) {
                $compObject = $value['object'];
                $compObject['_id'] = new \MongoId();
                $compObjects[] = $compObject;
                $normalizedComps[$key] = $value['ratio'];
            }
            $normalizedComps = ['normalizedData.completenesses' => $normalizedComps];

            $compObject = ['$set' => ['completenesses' => $compObjects]];
            $collection->update($query, $compObject, $options);

            $normalizedComp = ['$set' => $normalizedComps];
            $collection->update($query, $normalizedComp, $options);
        } else {
            foreach ($completenesses as $key => $value) {
                $compObject = ['$push' => ['completenesses' => $value['object']]];

                $collection->update($query, $compObject, $options);

                $normalizedComp = ['$set' => ['normalizedData.completenesses.'.$key => $value['ratio']]];
                $collection->update($query, $normalizedComp, $options);
            }
        }
    }

    /**
     * Generate family requirements information to be used to
     * calculate completenesses.
     *
     * @param ProductInterface $product
     * @param ChannelInterface $channel
     *
     * @return array
     */
    protected function getFamilyRequirements(ProductInterface $product = null, ChannelInterface $channel = null)
    {
        $selectFamily = null;

        if (null !== $product) {
            $selectFamily = $product->getFamily();
        }
        $families = $this->familyRepository->getFullFamilies($selectFamily, $channel);
        $familyRequirements = [];

        foreach ($families as $family) {
            $reqsByChannels = [];
            $channels = [];

            foreach ($family->getAttributeRequirements() as $attributeReq) {
                $channel = $attributeReq->getChannel();

                $channels[$channel->getCode()] = $channel;

                if (!isset($reqsByChannels[$channel->getCode()])) {
                    $reqsByChannels[$channel->getCode()] = [];
                }

                $reqsByChannels[$channel->getCode()][] = $attributeReq;
            }

            $familyRequirements[$family->getId()] = $this->getFieldsNames($channels, $reqsByChannels);
        }

        return $familyRequirements;
    }

    /**
     * Generate fields name that should be present and not null for the product
     * to be defined as complete for channels and family
     * Familyreqs must be indexed by channel code
     *
     * @param ChannelInterface[] $channels
     * @param array              $familyReqs
     *
     * @return array
     */
    protected function getFieldsNames(array $channels, array $familyReqs)
    {
        $fields = [];
        foreach ($channels as $channel) {
            foreach ($channel->getLocales() as $locale) {
                $expectedCompleteness = $channel->getCode().'-'.$locale->getCode();
                $fields[$expectedCompleteness] = [];
                $fields[$expectedCompleteness]['channel'] = $channel->getId();
                $fields[$expectedCompleteness]['locale'] = $locale->getId();
                $fields[$expectedCompleteness]['reqs'] = [];
                $fields[$expectedCompleteness]['reqs']['attributes'] = [];
                $fields[$expectedCompleteness]['reqs']['prices'] = [];

                foreach ($familyReqs[$channel->getCode()] as $requirement) {
                    $attribute = $requirement->getAttribute();
                    $fieldName = $this->getNormalizedFieldName($attribute, $channel, $locale);

                    $shouldExistInLocale = !$attribute->isLocaleSpecific() || $attribute->hasLocaleSpecific($locale);

                    if ($shouldExistInLocale) {
                        if (AbstractAttributeType::BACKEND_TYPE_PRICE === $attribute->getBackendType()) {
                            $fields[$expectedCompleteness]['reqs']['prices'][$fieldName] = [];
                            foreach ($channel->getCurrencies() as $currency) {
                                $fields[$expectedCompleteness]['reqs']['prices'][$fieldName][] = $currency->getCode();
                            }
                        } else {
                            $fields[$expectedCompleteness]['reqs']['attributes'][] = $fieldName;
                        }
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Get the name of a normalized data field
     *
     * @param AttributeInterface $attribute
     * @param ChannelInterface   $channel
     * @param LocaleInterface    $locale
     *
     * @return string
     */
    protected function getNormalizedFieldName(
        AttributeInterface $attribute,
        ChannelInterface $channel,
        LocaleInterface $locale
    ) {
        $suffix = '';

        if ($attribute->isLocalizable()) {
            $suffix = sprintf('-%s', $locale->getCode());
        }
        if ($attribute->isScopable()) {
            $suffix .= sprintf('-%s', $channel->getCode());
        }

        return $attribute->getCode() . $suffix;
    }

    /**
     * Apply the query part to search for product where the completenesses
     * are missing. Apply only to the channel or product if provided.
     *
     * @param Builder          $productsQb
     * @param ProductInterface $product
     * @param ChannelInterface $channel
     */
    protected function applyFindMissingQuery(
        Builder $productsQb,
        ProductInterface $product = null,
        ChannelInterface $channel = null
    ) {
        if (null !== $product) {
            $productsQb->field('_id')->equals($product->getId());
        } else {
            $combinations = $this->getChannelLocaleCombinations($channel);

            if (!empty($combinations)) {
                foreach ($combinations as $combination) {
                    $expr = new Expr();
                    $expr->field('normalizedData.completenesses.'.$combination)->exists(false);
                    $productsQb->addOr($expr);
                }
            }
        }

        $productsQb->field('family')->notEqual(null);
    }

    /**
     * Generate a list of potential completeness value from existing channel
     * or from the provided channel
     *
     * @param ChannelInterface $channel
     *
     * @return array
     */
    protected function getChannelLocaleCombinations(ChannelInterface $channel = null)
    {
        $channels = [];
        $combinations = [];

        if (null !== $channel) {
            $channels = [$channel];
        } else {
            $channels = $this->channelRepository->getFullChannels();
        }

        foreach ($channels as $channel) {
            $locales = $channel->getLocales();
            foreach ($locales as $locale) {
                $combinations[] = $channel->getCode().'-'.$locale->getCode();
            }
        }

        return $combinations;
    }

    /**
     * {@inheritdoc}
     */
    public function schedule(ProductInterface $product)
    {
        $product->getCompletenesses()->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function scheduleForFamily(FamilyInterface $family)
    {
        $productQb = $this->documentManager->createQueryBuilder($this->productClass);

        $productQb
            ->update()
            ->multiple(true)
            ->field('family')->equals($family->getId())
            ->field('completenesses')->unsetField()
            ->field('normalizedData.completenesses')->unsetField()
            ->getQuery()
            ->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function scheduleForChannelAndLocale(ChannelInterface $channel, LocaleInterface $locale)
    {
        $productQb = $this->documentManager->createQueryBuilder($this->productClass);

        $pullExpr = $productQb->expr()
                ->addAnd($productQb->expr()->field('channel')->equals($channel->getId()))
                ->addAnd($productQb->expr()->field('locale')->equals($locale->getId()));

        $productQb
            ->update()
            ->multiple(true)
            ->field(
                sprintf('normalizedData.completenesses.%s-%s', $channel->getCode(), $locale->getCode())
            )->unsetField()
            ->field('completenesses')->pull($pullExpr)
            ->getQuery()
            ->execute();
    }
}
