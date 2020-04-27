<?php

namespace ModulIS\Generator\ClassGenerator;

use Nette\Utils\Strings;

class FormGenerator extends ComponentGenerator
{
    protected $namespace;
    protected $table;
    protected $classname;
    protected $baseDir;
    protected $connection;
    protected $entityPath;

    public function __construct
    (
        string $table,
        string $classname,
        string $namespace,
        string $baseDir,
        string $entityPath,
        \Nette\Database\Connection $connection
    )
    {
        $this->table = $table;
        $this->namespace = $namespace;
        $this->classname = $classname;
        $this->baseDir = $baseDir;
        $this->connection = $connection;
        $this->entityPath = $entityPath;
        $this->generateDir = join(DIRECTORY_SEPARATOR, [$this->baseDir, $this->namespace. 'Module', 'form', $this->classname . 'Form']);

        if(!is_dir($this->generateDir))
        {
            mkdir($this->generateDir, 0775, true);
        }
    }

    /**
     * Generate form class
     */
    public function generateForm(): string
    {
        $class = "<?php"
            . PHP_EOL
            . "declare(strict_types=1);"
            . PHP_EOL
            . PHP_EOL
            ."namespace " . $this->namespace . "\\Form;"
            . PHP_EOL
            . PHP_EOL
            . "class " . $this->classname .  "Form extends \\Code\\Component\\FormComponent"
            . PHP_EOL
            . "{"
            . PHP_EOL
            . $this->generateRepository()
            . PHP_EOL
            . PHP_EOL
            . $this->generateIdProperty()
            . PHP_EOL
            . PHP_EOL
            . PHP_EOL
            . $this->generateConstructor()
            . PHP_EOL
            . PHP_EOL
            . PHP_EOL
            . $this->generatePrepare()
            . PHP_EOL
            . PHP_EOL
            . PHP_EOL
            . $this->generateFormComponent()
            . PHP_EOL
            . PHP_EOL
            . PHP_EOL
            . $this->generateFormSuccess()
            . PHP_EOL
            . PHP_EOL
            . PHP_EOL
            . $this->generateSetup()
            . PHP_EOL
            . "}"
            . PHP_EOL;

        file_put_contents($this->generateDir . DIRECTORY_SEPARATOR .  $this->classname . 'Form.php', $class);

        return join('\\', [$this->namespace, 'Form', $this->classname . 'Form']);
    }

    /**
     * Generate form factory interface
     */
    public function generateFormFactory(): string
    {
        $class = "<?php"
            . PHP_EOL
            . "declare(strict_types=1);"
            . PHP_EOL
            . PHP_EOL
            ."namespace " . $this->namespace . "\\Form;"
            . PHP_EOL
            . PHP_EOL
            . "interface I" . $this->classname .  "FormFactory"
            . PHP_EOL
            . "{"
            . PHP_EOL
            . "\tfunction create(): " . $this->classname . 'Form;'
            . PHP_EOL
            . "}"
            . PHP_EOL;

        file_put_contents($this->generateDir . DIRECTORY_SEPARATOR .  'I' . $this->classname . 'FormFactory.php', $class);

        return join('\\', [$this->namespace, 'Form', 'I' . $this->classname . 'FormFactory']);
    }

    /**
     * Generate attached method for form
     */
    private function generatePrepare(): string
    {
        $var = Strings::firstLower($this->classname);

        $attachedString = "\tpublic function prepare(): void"
            . PHP_EOL
            . "\t{"
            . PHP_EOL
            . "\t\tif(\$this->id)"
            . PHP_EOL
            . "\t\t{"
            . PHP_EOL
            . "\t\t\t\$" . $var . "Entity = \$this->" . $this->classname . "Repository->getByID(\$this->id);"
            . PHP_EOL
            . PHP_EOL
            . "\t\t\tif($" . $var . "Entity)"
            . PHP_EOL
            . "\t\t\t{"
            . PHP_EOL
            . "\t\t\t\t\$this['form']->setDefaults(\$" . $var . "Entity->toArray());"
            . PHP_EOL
            . "\t\t\t}"
            . PHP_EOL
            . "\t\t}"
            . PHP_EOL
            . "\t}";

        return $attachedString;
    }

    private function generateIdProperty(): string
    {
        $propertyIdString = "\t/**"
            . PHP_EOL
            . "\t * @var int"
            . PHP_EOL
            . "\t */"
            . PHP_EOL
            . "\tprivate \$id;";

        return $propertyIdString;
    }

    /**
     * Generate setter method for internal id for form
     */
    private function generateSetup(): string
    {
        $setupString = "\tpublic function set" . $this->classname . "Id(\$id): void"
            . PHP_EOL
            . "\t{"
            . PHP_EOL
            . "\t\t\$this->id = \$id;"
            . PHP_EOL
            . "\t}";

        return $setupString;
    }

    /**
     * Generate form coponentn function
     */
    private function generateFormComponent(): string
    {
        $formString =  "\t/**"
            . PHP_EOL
            . "\t * Create component " . $this->classname . 'Form'
            . PHP_EOL
            . "\t */"
            . PHP_EOL
            . "\tpublic function createComponentForm(): \Code\Component\Form"
            . PHP_EOL
            . "\t{"
            . PHP_EOL
            . "\t\t\$form = new \Code\Component\Form;"
            . $this->generateComponent()
            . "\t\t\$form->addSubmit('save', 'Odeslat')"
            . PHP_EOL
            . "\t\t\t->setIcon('save')"
            . PHP_EOL
            . "\t\t\t->setColor('success');"
            . PHP_EOL
            . PHP_EOL
            . "\t\t\$form->onSuccess[] = [\$this, 'formSuccess'];"
            . PHP_EOL
            . PHP_EOL
            . "\t\treturn \$form;"
            . PHP_EOL
            . "\t}";

        return $formString;
    }

    /**
     * Generates form components(inputs)
     */
    private function generateComponent(): string
    {
        $componentArray = [];

        foreach($this->connection->query("DESCRIBE `$this->table`") as $column)
        {
            $component = "\t\t\$form->add";

            if($column['Field'] == 'id')
            {
                $component .= "Hidden('id')";

                $componentArray[] = $component . ";";
                continue;
            }
            elseif(
                Strings::startsWith (Strings::lower($column['Type']), 'tinyint(1)') ||
                Strings::compare(Strings::lower($column['Type']), 'int(1)') === 0
            )
            {
                $component .= "Checkbox('" . $column['Field'] . "', '')";
            }
            elseif(Strings::startsWith(Strings::lower($column['Type']), 'enum'))
            {
                $component .= "Select('" . $column['Field'] . "', '', \\" . $this->namespace . '\\Dial\\' . Strings::firstUpper($this->classname) . Strings::firstUpper($column['Field']) . "Dial::getList())";
                $component .= PHP_EOL
                    . "\t\t\t->setPrompt('~ Vyberte možnost ~ ')";
            }
            else
            {
                $component .= "Text('" . $column['Field'] . "', '')";

                if($this->colTypeInt($column['Type']))
                {
                    $component .= PHP_EOL
                        . "\t\t\t->addCondition(\$form::FILLED)"
                        . PHP_EOL
                        . "\t\t\t\t->addRule(\$form::NUMERIC, 'Zadaná hodnota musí být číslo.')"
                        . PHP_EOL
                        . "\t\t\t->endCondition()";
                }
            }

            if(Strings::lower($column['Null']) == 'no' &&
                !(
                    Strings::startsWith (Strings::lower($column['Type']), 'tinyint(1)') ||
                    Strings::startsWith (Strings::lower($column['Type']), 'int(1)')
                ))
            {
                $component .= PHP_EOL
                    . "\t\t\t->setRequired()";
            }

            if(Strings::lower($column['Type']) == 'datetime' || Strings::lower($column['Type']) == 'date')
            {
                $component.= PHP_EOL
                    . "\t\t\t->setAttribute('class', 'date-picker')";
            }

            $componentArray[] = $component . ";";
        }

        return  PHP_EOL . PHP_EOL . implode(PHP_EOL . PHP_EOL, $componentArray) . PHP_EOL . PHP_EOL;
    }

    /**
     * Generate success function for form
     */
    private function generateFormSuccess(): string
    {
        $componentArray = [];

        foreach($this->connection->query("DESCRIBE `$this->table`") as $column)
        {
            if($column['Field'] == 'id')
            {
                continue;
            }

            $string = "\t\t$" . Strings::firstLower($this->classname) . "Entity->" . $column['Field'] . " = ";

            if(Strings::lower($column['Null']) == 'yes')
            {
                $string .= "empty(\$values->" . $column['Field'] . ") ? null : ";
            }

            if(Strings::startsWith(Strings::lower($column['Type']), 'tinyint(1)') ||
                Strings::compare(Strings::lower($column['Type']), 'int(1)') === 0)
            {
                $string .= "intval(\$values->" . $column['Field'] . ");";
            }
            elseif(Strings::compare(Strings::lower($column['Type']), 'float'))
            {
                $string .= "(float) \$values->" . $column['Field'] . ";";
            }
            elseif(Strings::compare(Strings::lower($column['Type']), 'double'))
            {
                $string .= "(double) \$values->" . $column['Field'] . ";";
            }
            elseif(Strings::compare(Strings::lower($column['Type']), 'date'))
            {
                $string .= "(new \\Nette\\Utils\\Datetime(\$values->" . $column['Field'] . "))->format('Y-m-d');";
            }
            elseif(Strings::compare(Strings::lower($column['Type']), 'datetime'))
            {
                $string .= "(new \\Nette\\Utils\\Datetime(\$values->" . $column['Field'] . "))->format('Y-m-d H:i:s');";
            }
            elseif($this->colTypeInt($column['Type']))
            {
                $string .= "(int) \$values->" . $column['Field'] . ";";
            }
            else
            {
                $string .= "\$values->" . $column['Field'] . ";";
            }

            $componentArray[] = $string;
        }

        $successString = "\t/**"
            . PHP_EOL
            . "\t * Success function for " . $this->classname . 'Form'
            . PHP_EOL
            . "\t */"
            . PHP_EOL
            . "\tpublic function formSuccess(\Code\Component\Form \$form, \Nette\Utils\ArrayHash \$values): void"
            . PHP_EOL
            . "\t{"
            . PHP_EOL
            . "\t\t\$" . Strings::firstLower($this->classname) .
                "Entity = \$this->" . $this->classname . "Repository->getByID(\$this->id) ?: new "
                . $this->entityPath . ";"
            . PHP_EOL
            . PHP_EOL
            . implode(PHP_EOL, $componentArray)
            . PHP_EOL
            . PHP_EOL
            . "\t\t\$this->" . $this->classname . "Repository->save(\$" . Strings::firstLower($this->classname) .
                "Entity);"
            . PHP_EOL
            . PHP_EOL
            . "\t\t\$this->getPresenter()->flashMessage('', 'success');"
            . PHP_EOL
            . "\t\t\$this->getPresenter()->redirect('" . Strings::firstLower($this->classname) . "Grid');"
            . PHP_EOL
            . "\t}";

        return $successString;
    }

    /**
     * Returns if column type is number
     */
    private function colTypeInt(string $colType): bool
    {
        //types to replace
        $intArray = [
            'tinyint',
            'smallint',
            'mediumint',
            'int',
            'bigint'
        ];

        return in_array(Strings::before($colType, '('), $intArray);
    }
}