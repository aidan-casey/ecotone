<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\DataSetter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\EnricherConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\EnricherConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertyPath;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class ExpressionSetterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EnrichPayloadWithCompositeExpressionBuilder implements EnricherConverterBuilder
{
    /**
     * @var string
     */
    private $propertyPath;
    /**
     * @var string
     */
    private $expression;
    /**
     * @var string
     */
    private $pathToEnrichedContext;
    /**
     * @var string
     */
    private $dataMappingExpression;

    /**
     * ExpressionSetterBuilder constructor.
     *
     * @param string $propertyPath
     * @param string $expression
     * @param string $pathToEnrichedContext
     * @param string $dataMappingExpression
     */
    private function __construct(string $propertyPath, string $expression, string $pathToEnrichedContext, string $dataMappingExpression)
    {
        $this->propertyPath          = $propertyPath;
        $this->expression            = $expression;
        $this->pathToEnrichedContext = $pathToEnrichedContext;
        $this->dataMappingExpression = $dataMappingExpression;
    }

    /**
     * @param string $pathToEnrichInContext              path inside context, which will be enriched with mapped data
     * @param string $expressionToElementsInReplyMessage path to elements from reply message that will be added to current message. Must return array
     * @param string $pathToElementInInputMessagePayload Path to enriched context from input message. Must return array.
     * @param string $dataMappingExpression              Must return array. e.g "context['personId'] = reply.personId", where reply is reply message and personId is property from pathToEnrichedArray context
     *
     * @return EnrichPayloadWithCompositeExpressionBuilder
     */
    public static function createWithMapping(string $pathToEnrichInContext, string $expressionToElementsInReplyMessage, string $pathToElementInInputMessagePayload, string $dataMappingExpression) : self
    {
        return new self($pathToEnrichInContext, $expressionToElementsInReplyMessage, $pathToElementInInputMessagePayload, $dataMappingExpression);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): EnricherConverter
    {
        return new EnrichPayloadWithCompositeExpressionConverter(
            $referenceSearchService,
            $referenceSearchService->get(ExpressionEvaluationService::REFERENCE),
            DataSetter::create(),
            PropertyPath::createWith($this->propertyPath),
            $this->expression,
            $this->pathToEnrichedContext,
            $this->dataMappingExpression
        );
    }
}