<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Config\ConfigurationException;

/**
 * Interface ClassConfigurationReader
 * @package SimplyCodedSoftware\Messaging\Config\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ClassMetadataReader
{
    /**
     * @param string $className
     * @param string $annotationName
     * @return string[] method names
     */
    public function getMethodsWithAnnotation(string $className, string $annotationName) : array;

    /**
     * @param string $className
     * @param string $methodName
     * @param string $annotationName
     * @return object return annotation class
     * @throws ConfigurationException if annotation not found in method
     */
    public function getAnnotationForMethod(string $className, string $methodName, string $annotationName);

    /**
     * @param string $className
     * @param string $annotationName
     * @return object return annotation class
     * @throws ConfigurationException if annotation not found in class
     */
    public function getAnnotationForClass(string $className, string $annotationName);
}