<?php

namespace SimplyCodedSoftware\Messaging\Annotation\ParameterConverter;

use Doctrine\Common\Annotations\Annotation\Required;
use SimplyCodedSoftware\Messaging\Annotation\ParameterConverterAnnotation;

/**
 * Class HeaderParameterConverter
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation({"Annotation"})
 */
class HeaderParameterConverterAnnotation implements ParameterConverterAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $parameterName;
    /**
     * @var string
     * @Required()
     */
    public $headerName;
}