<?php

namespace ModulIS\Generator\ClassGenerator;

/**
 * @abstract
 */
abstract class ComponentGenerator
{
    protected $namespace;
    protected $classname;
    protected $generateDir;

    /**
     * Generate repository property for given component class
     */
    protected function generateRepository(): string
    {
        $propertyString = "\t/**"
            . PHP_EOL
            . "\t * @var \\" . $this->namespace . "\\Repository\\" . $this->classname . "Repository"
            . PHP_EOL
            . "\t */"
            . PHP_EOL
            . "\tprivate \$" . $this->classname . "Repository" . ";";

        return $propertyString;
    }

    /**
     * Generate constructor with repository for given component class
     */
    protected function generateConstructor(): string
    {
        $constructorString = "\tpublic function __construct"
            . PHP_EOL
            . "\t("
            . PHP_EOL
            . "\t\t\\" . $this->namespace . "\\Repository\\" . $this->classname . "Repository" . " \$" . $this->classname . "Repository"
            . PHP_EOL
            . "\t)"
            . PHP_EOL
            . "\t{"
            . PHP_EOL
            . "\t\t\$this->" . $this->classname . "Repository = \$" . $this->classname . "Repository;"
            . PHP_EOL
            . "\t}";

        return $constructorString;
    }
}