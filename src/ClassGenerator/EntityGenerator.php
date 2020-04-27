<?php

namespace ModulIS\Generator\ClassGenerator;

use Nette\Utils\Strings;

class EntityGenerator
{
    private $namespace;
    private $table;
    private $classname;
    private $baseDir;
    private $connection;
    private $datetimeFunctionArray = [];

    public function __construct
    (
        string $table,
        string $classname,
        string $namespace,
        string $baseDir,
        \Nette\Database\Connection $connection
    )
    {
        $this->table = $table;
        $this->namespace = $namespace;
        $this->classname = $classname;
        $this->baseDir = $baseDir;
        $this->connection = $connection;
    }

    /**
     * Generate entity class
     */
    public function generateEntity(): void
    {
        $class = "<?php"
            . PHP_EOL
            . "declare(strict_types=1);"
            . PHP_EOL
            . PHP_EOL
            ."namespace " . $this->namespace . "\\Entity;"
            . PHP_EOL
            . PHP_EOL
            . $this->generateAnnotation()
            . PHP_EOL
            . "class " . $this->classname .  "Entity extends \\ModulIS\\Entity"
            . PHP_EOL
            . "{"
            . implode(PHP_EOL, $this->datetimeFunctionArray)
            . "}"
            . PHP_EOL;

        $generateDir = join(DIRECTORY_SEPARATOR, [$this->baseDir, $this->namespace. 'Module', 'entity']);

        if(!is_dir($generateDir))
        {
            mkdir($generateDir, 0775, true);
        }

        file_put_contents($generateDir . DIRECTORY_SEPARATOR .  $this->classname . 'Entity.php', $class);
    }

    /**
     * Generates annotation string from table columns
     */
    private function generateAnnotation(): string
    {
        $annotationArray = [];
        $annotation = "/**" . PHP_EOL . ' * ';

        foreach($this->connection->query("DESCRIBE `$this->table`") as $column)
        {
            $property = '@property';

            $property .= Strings::lower($column['Extra']) == 'auto_increment' ? '-read' : null;
            $property .= ' ' . $this->getColType(Strings::lower($column['Type'])) . (Strings::lower($column['Null']) == 'yes' ? '|null' : null);
            $property .= ' $' . $column['Field'];

            if(Strings::startsWith($column['Type'], 'enum') || Strings::startsWith($column['Type'], 'char'))
            {
                $this->generateDial($column);
            }
            elseif($column['Type'] == 'datetime' || $column['Type'] == 'date')
            {
                $this->generateDatimeTransformation($column);
            }

            $annotationArray[$column['Field']] = $property;
        }

        return  $annotation . implode(PHP_EOL . ' * ', $annotationArray) . PHP_EOL . ' */';
    }

    /**
     * Column type detection
     */
    private function getColType(string $type): string
    {
        $outputArray = [];
        preg_match("/\w+/", $type, $outputArray);

        //types to replace
        $types = [
            'string' => [
                'char',
                'varchar',
                'tinytext',
                'text',
                'mediumtext',
                'longtext',
                'enum',
                'set',
                'date',
                'datetime',
                'timestamp',
                'time',
                'year'
            ],
            'int' => [
                'tinyint',
                'smallint',
                'mediumint',
                'int',
                'bigint'
            ]
        ];

        foreach($types as $typeKey => $array)
        {
            // if type is in replace array, replace it with key
            if(in_array(Strings::lower(trim($outputArray[0])), $array))
            {
                $outputArray[0] = $typeKey;
                break;
            }
        }

        return $outputArray[0];
    }

    /**
     * Generate Dial if $column is enum
     */
    private function generateDial(\Nette\Database\Row $column): void
    {
        $dialGenerator = new DialGenerator($this->classname, $this->namespace, $this->baseDir, $column);
        $dialGenerator->generateDial();
    }

    /**
     * Generate function for transforming datetime|date column to datetime object
     */
    private function generateDatimeTransformation(\Nette\Database\Row $column): void
    {
        $functionName = '';

        if(Strings::contains($column['Field'], '_'))
        {
            $counter = 0;
            foreach(explode('_', $column['Field']) as $namePart)
            {
                if($counter == 0)
                {
                    $functionName = $namePart;
                    $counter++;
                    continue;
                }

                $functionName .= Strings::firstUpper($namePart);
            }

            $functionName .= 'GetDatetime';
        }
        else
        {
            $functionName = $column['Field'] . 'GetDatetime';
        }

        if(Strings::lower($column['Null']) == 'yes')
        {
            $returnAnnotation = ": ?\Nette\Utils\DateTime";
            $return = "return \$this->". $column['Field'] . " ? new \Nette\Utils\DateTime(\$this->" . $column['Field'] .") : null";
        }
        else
        {
            $returnAnnotation = ": \Nette\Utils\DateTime";
            $return = "return new \Nette\Utils\DateTime(\$this->" . $column['Field'] . ")";
        }

        $functionString = PHP_EOL
                . "\tpublic function " . $functionName . "()" . $returnAnnotation
                . PHP_EOL
                . "\t{"
                . PHP_EOL
                . "\t\t" . $return . ";"
                . PHP_EOL
                . "\t}"
                . PHP_EOL;

        $this->datetimeFunctionArray[] = $functionString;
    }
}
