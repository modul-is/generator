<?php

namespace ModulIS\Generator\ClassGenerator;

class RepositoryGenerator
{
    protected $namespace;
    protected $table;
    protected $entityPath;
    protected $classname;
    protected $baseDir;
    
    public function __construct
    (
        string $table,
        string $entity,
        string $classname,
        string $namespace,
        string $baseDir
    )
    {
        $this->table = $table;
        $this->namespace = $namespace;
        $this->classname = $classname;
        $this->entityPath = $entity;
        $this->baseDir = $baseDir;
    }

    /**
     * Generate repository class
     */
    public function generateRepository(): string
    {
        $class = "<?php"
            . PHP_EOL
            . "declare(strict_types=1);"
            . PHP_EOL
            . PHP_EOL
            ."namespace " . $this->namespace . "\\Repository;"
            . PHP_EOL
            . PHP_EOL
            . "class " . $this->classname .  "Repository extends \\ModulIS\\Repository"
            . PHP_EOL
            . "{"
            . PHP_EOL
            . "\tprotected \$table = '" . $this->table . "';"
            . PHP_EOL
            . PHP_EOL
            . "\tprotected \$entity = '\\" . $this->namespace . '\\Entity\\' . $this->classname  . "Entity';"
            . PHP_EOL
            . PHP_EOL
            . PHP_EOL
            . "\tpublic function getByID(\$id): ?" . $this->entityPath
            . PHP_EOL
            . "\t{"
            . PHP_EOL
            . "\t\treturn parent::getByID(\$id);"
            . PHP_EOL
            . "\t}"
            . PHP_EOL
            . PHP_EOL
            . PHP_EOL
            . "\tpublic function getBy(array \$criteria): ?" . $this->entityPath
            . PHP_EOL
            . "\t{"
            . PHP_EOL
            . "\t\treturn parent::getBy(\$criteria);"
            . PHP_EOL
            . "\t}"
            . PHP_EOL
            . "}"
            . PHP_EOL;

        $generateDir = join(DIRECTORY_SEPARATOR, [$this->baseDir, $this->namespace. 'Module', 'repository']);

        if(!is_dir($generateDir))
        {
            mkdir($generateDir, 0775, true);
        }

        file_put_contents($generateDir . DIRECTORY_SEPARATOR .  $this->classname . 'Repository.php', $class);

        return join('\\', [$this->namespace, 'Repository', $this->classname . 'Repository']);
    }
}
