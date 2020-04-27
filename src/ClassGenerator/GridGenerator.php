<?php

namespace ModulIS\Generator\ClassGenerator;

use Nette\Utils\Strings;

class GridGenerator extends ComponentGenerator
{
    protected $namespace;
    protected $table;
    protected $classname;
    protected $baseDir;
    protected $connection;
    protected $handleArray;
    protected $actionArray;

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
        $this->generateDir = join(DIRECTORY_SEPARATOR, [$this->baseDir, $this->namespace. 'Module', 'grid', $this->classname . 'Grid']);

        if(!is_dir($this->generateDir))
        {
            mkdir($this->generateDir, 0775, true);
        }
    }

    /**
     * Generate and writte grid class
     */
    public function generateGrid(): string
    {
        $class = "<?php"
            . PHP_EOL
            . "declare(strict_types=1);"
            . PHP_EOL
            . PHP_EOL
            ."namespace " . $this->namespace . "\\Grid;"
            . PHP_EOL
            . PHP_EOL
            . "class " . $this->classname .  "Grid extends \\Code\\Component\\GridComponent"
            . PHP_EOL
            . "{"
            . PHP_EOL
            . $this->generateRepository()
            . PHP_EOL
            . PHP_EOL
            . PHP_EOL
            . $this->generateConstructor()
            . PHP_EOL
            . PHP_EOL
            . PHP_EOL
            . $this->generateGridComponent()
            . implode(PHP_EOL . PHP_EOL . PHP_EOL, $this->handleArray)
            . PHP_EOL
            . "}"
            . PHP_EOL;

        file_put_contents($this->generateDir . DIRECTORY_SEPARATOR .  $this->classname . 'Grid.php', $class);

        return join('\\', [$this->namespace, 'Grid', $this->classname . 'Grid']);
    }

    /**
     * Generate grid factory interface
     */
    public function generateGridFactory(): string
    {
        $class = "<?php"
            . PHP_EOL
            . "declare(strict_types=1);"
            . PHP_EOL
            . PHP_EOL
            ."namespace " . $this->namespace . "\\Grid;"
            . PHP_EOL
            . PHP_EOL
            . "interface I" . $this->classname .  "GridFactory"
            . PHP_EOL
            . "{"
            . PHP_EOL
            . "\tfunction create(): " . $this->classname . 'Grid;'
            . PHP_EOL
            . "}"
            . PHP_EOL;

        file_put_contents($this->generateDir . DIRECTORY_SEPARATOR .  'I' . $this->classname . 'GridFactory.php', $class);

        return join('\\', [$this->namespace, 'Grid', 'I' . $this->classname . 'GridFactory']);
    }

    private function generateGridComponent(): string
    {
        $componentString = "\t/**"
            . PHP_EOL
            . "\t * Create component " . $this->classname . 'Grid'
            . PHP_EOL
            . "\t */"
            . PHP_EOL
            . "\tpublic function createComponentGrid(): \\Code\\Component\\Datagrid"
            . PHP_EOL
            . "\t{"
            . PHP_EOL
            . "\t\t\$grid = \$this->getGrid();"
            . PHP_EOL
            . PHP_EOL
            . "\t\t\$grid->setPrimaryKey('id');"
            . PHP_EOL
            . "\t\t\$grid->setDataSource(\$this->" . $this->classname . "Repository->getTable());"
            . PHP_EOL
            . PHP_EOL
            . $this->generateColumn()
            . PHP_EOL
            . PHP_EOL
            . implode(PHP_EOL . PHP_EOL, $this->actionArray)
            . PHP_EOL
            . PHP_EOL
            . "\t\treturn \$grid;"
            . PHP_EOL
            . "\t}";

        return $componentString;
    }

    private function generateColumn(): string
    {
        $componentArray = [];

        foreach($this->connection->query("DESCRIBE `$this->table`") as $column)
        {
            $component = "\t\t\$grid->addColumn";
            $filter = "\t\t\t->setFilter";

            if($column['Field'] == 'id')
            {
                $this->generateHandle('Form', 'id');
                continue;
            }
            elseif(
                Strings::startsWith (Strings::lower($column['Type']), 'tinyint(1)') ||
                Strings::startsWith (Strings::lower($column['Type']), 'int(1)')
            )
            {
                $component .= "Bool('" . $column['Field'] . "', '')";
                $filter .= "Select(self::BOOL_FILTER_ARRAY)";

                if($column['Field'] == 'active')
                {
                    $this->generateHandle("delete" . $this->classname, 'active');
                }
                else
                {
                    $handleName = 'change';
                    if(strpos($column['Field'], '_') === false)
                    {
                        $handleName .= Strings::firstUpper($column['Field']);
                    }
                    else
                    {
                        foreach(explode('_', $column['Field']) as $partName)
                        {
                            $handleName .= Strings::firstUpper($partName);
                        }
                    }

                    $this->generateHandle($handleName, $column['Field']);
                }
            }
            elseif(Strings::startsWith(Strings::lower($column['Type']), 'enum'))
            {
                $dial = "\\" . $this->namespace . '\\Dial\\' . Strings::firstUpper($this->classname) . Strings::firstUpper($column['Field']) . "Dial::getList()";
                $component .= "Dial('" . $column['Field'] . "', '', " . $dial . ")";
                $filter .= "Select(self::FILTER_PROMPT + " . $dial . ")";
            }
            elseif(Strings::lower($column['Type']) == 'datetime')
            {
                $component .= "Datetime('" . $column['Field'] . "', '')"
                        .PHP_EOL
                        . "\t\t\t->setFormat('d.m.Y H:i:s')";

                $filter .= "DateRange()";
            }
            elseif(Strings::lower($column['Type']) == 'date')
            {
                $component .= "Datetime('" . $column['Field'] . "', '')"
                        .PHP_EOL
                        . "\t\t\t->setFormat('d.m.Y')";

                $filter .= "DateRange()";
            }
            else
            {
                $component .= "Text('" . $column['Field'] . "', '')";
                $filter .= "Text()";
            }

            $component .= PHP_EOL
                . "\t\t\t->setAlign('center')"
                . PHP_EOL
                . "\t\t\t->addCellAttributes([])"
                . PHP_EOL
                . "\t\t\t->setSortable()";

            $componentArray[] = $component . PHP_EOL . $filter . ";";
        }

        return implode(PHP_EOL . PHP_EOL, $componentArray);
    }

    /**
     * Set action and handle for generating
     */
    private function generateHandle(string $actionName, string $columnName): void
    {
        $actionSetting = PHP_EOL . "\t\t\t->setTitle('')";

        $handleString = "\t/**"
            . PHP_EOL
            . "\t * Handler for " . $actionName
            . PHP_EOL
            . "\t */"
            . PHP_EOL
            . "\tpublic function handle" . Strings::firstUpper($actionName) . "(\$id): void"
            . PHP_EOL
            . "\t{"
            . PHP_EOL
            . "\t\t$" . Strings::firstLower($this->classname) . "Entity = \$this->" . $this->classname . "Repository->getByID(\$id);";

        if($actionName === 'Form')
        {
            $actionName = Strings::firstLower($this->classname) . $actionName;
            $actionSetting .= PHP_EOL
            . "\t\t\t->setIcon('pencil')"
            . PHP_EOL
            . "\t\t\t->setClass('btn btn-sm btn-info')";
        }
        elseif($actionName === 'delete' . $this->classname)
        {
            $handleString .= PHP_EOL
            . "\t\t$" . Strings::firstLower($this->classname) . "Entity->active = 0;"
            . PHP_EOL
            . PHP_EOL
            . "\t\t\$this->" . $this->classname . "Repository->save(\$" . Strings::firstLower($this->classname) . "Entity);"
            . PHP_EOL
            . PHP_EOL
            . "\t\t\$this->getPresenter()->flashMessage('', 'success');"
            . PHP_EOL
            . "\t\t\$this->getPresenter()->redrawControl('flashMessages');"
            . PHP_EOL
            . "\t\t\$this['grid']->reload();";

            $actionName .= '!';
            $actionSetting .= PHP_EOL
            . "\t\t\t->setIcon('trash')"
            . PHP_EOL
            . "\t\t\t->setClass('btn btn-sm btn-danger')"
            . PHP_EOL
            . "\t\t\t->setConfirmation(new \Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation(''))";
        }
        else
        {
            $handleString .= PHP_EOL
            . "\t\t$" . Strings::firstLower($this->classname) . "Entity->" . $columnName . " = $" .
                        Strings::firstLower($this->classname) . "Entity->" . $columnName . " === 0 ? 1 : 0;"
            . PHP_EOL
            . PHP_EOL
            . "\t\t\$this->" . $this->classname . "Repository->save(\$" . Strings::firstLower($this->classname) . "Entity);"
            . PHP_EOL
            . PHP_EOL
            . "\t\t\$this->getPresenter()->flashMessage('', 'success');"
            . PHP_EOL
            . "\t\t\$this->getPresenter()->redrawControl('flashMessages');"
            . PHP_EOL
            . "\t\t\$this['grid']->reload();";

            $actionName .= '!';
            $actionSetting .= PHP_EOL
            . "\t\t\t->setIcon('sync')"
            . PHP_EOL
            . "\t\t\t->setClass('btn btn-sm btn-purple')"
            . PHP_EOL
            . "\t\t\t->setConfirmation(new \Ublaboo\DataGrid\Column\Action\Confirmation\StringConfirmation(''))";
        }

        $handleString .= PHP_EOL . "\t}";

        $actionHref = $actionName === Strings::firstLower($this->classname) . 'Form' ? $this->classname . ':' . $actionName : null;
        $actionString = "\t\t\$grid->addAction('" . $actionName . "', '', '" . $actionHref . "', ['id' => 'id'])" . $actionSetting . ";";

        $this->actionArray[] = $actionString;
        $this->handleArray[] = $actionName === Strings::firstLower($this->classname) . 'Form' ? null : $handleString;
    }
}