<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\AnnotationToBuilder;

use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\AnnotationConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\ClassLocator;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\ClassMetadataReader;

/**
 * Class BaseAnnotationConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\AnnotationToBuilder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class BaseAnnotationConfiguration implements AnnotationConfiguration
{
    /**
     * @var ClassLocator
     */
    protected $classLocator;
    /**
     * @var ClassMetadataReader
     */
    protected $classMetadataReader;

    /**
     * @inheritDoc
     */
    public function setClassLocator(ClassLocator $classLocator): void
    {
        $this->classLocator = $classLocator;
    }

    /**
     * @inheritDoc
     */
    public function setClassMetadataReader(ClassMetadataReader $classMetadataReader): void
    {
        $this->classMetadataReader = $classMetadataReader;
    }
}