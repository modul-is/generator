<?php

namespace ModulIS\Generator;

use Nette\Utils\Strings;
use Nette\Neon\Neon;

class ClassGenerator
{
    /**
     * Path to App folder
     *
     * @var string
     */
    private $baseDir;

    /**
     * @var string
     */
    private $table;

    /**
     * Module namespace for generated classes
     *
     * @var string
     */
    private $namespace;

    /**
     * Module name
     *
     * @var string
     */
    private $module;

    /**
     * Generate type
     *
     * @var string
     */
    private $type;

    /**
     * Class name for generated classes
     *
     * @var string
     */
    private $classname;

    /**
     * Namespace path for entity of given table
     *
     * @var string
     */
    private $entityPath;

    /**
     * Array of grid and form factories
     *
     * @var array
     */
    private $injectArray = [];

    /**
     * Array of grid and form components
     *
     * @var array
     */
    private $componentArray = [];

    /**
     * Array of presenter properties
     *
     * @var array
     */
    private $propertyArray = [];

    /**
     * Array of returned values based on what was generated
     *
     * @var array
     */
    private $returnArray = [];

    /**
     * @var \Nette\Database\Connection
     */
    private $connection;

    private static $typeArray = [
        'all',
        'entity',
        'repository',
        'form',
        'grid',
        'presenter',
        'template'
    ];

    public function __construct
    (
        array $args
    )
    {
        $this->baseDir = join(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'app']);
        $this->parseArgument($args);
    }

    public function run(): string
    {
        if($this->type == 'all')
        {
            $this->generateRepository();
            $this->generateEntity();
            $this->generateForm();
            $this->generateGrid();
            $this->generatePresenter();
            $this->generateTemplate();
        }
        else
        {
            $function = 'generate' . Strings::firstUpper($this->type);

            $this->$function();
        }

        $this->registerClass();

        $this->clearCache();

        return implode(', ', $this->returnArray);
    }

    private function generateEntity(): void
    {
        $entityGenerator = new \ModulIS\Generator\ClassGenerator\EntityGenerator($this->table, $this->classname, $this->namespace, $this->baseDir, $this->connection);
        $entityGenerator->generateEntity();
        $this->returnArray[] = 'Entity';
    }

    private function generateRepository(): void
    {
        $repositoryGenerator = new \ModulIS\Generator\ClassGenerator\RepositoryGenerator($this->table, $this->entityPath, $this->classname, $this->namespace, $this->baseDir);
        $this->injectArray[] = $repositoryGenerator->generateRepository();
        $this->returnArray[] = 'Repository';
    }

    private function generateForm(): void
    {
        $formGenerator = new \ModulIS\Generator\ClassGenerator\FormGenerator($this->table, $this->classname, $this->namespace, $this->baseDir, $this->entityPath, $this->connection);
        $this->componentArray[] = $formGenerator->generateForm();
        $this->injectArray[] = $formGenerator->generateFormFactory();
        $this->returnArray[] = 'Form';
    }

    private function generateGrid(): void
    {
        $gridGenerator = new \ModulIS\Generator\ClassGenerator\GridGenerator($this->table, $this->classname, $this->namespace, $this->baseDir, $this->connection);
        $this->componentArray[] = $gridGenerator->generateGrid();
        $this->injectArray[] = $gridGenerator->generateGridFactory();
        $this->returnArray[] = 'Grid';
    }

    private function generatePresenter(): void
    {
        $presenterGenerator = new \ModulIS\Generator\ClassGenerator\PresenterGenerator($this->classname, $this->module, $this->namespace, $this->baseDir, $this->injectArray, $this->componentArray);
        $this->propertyArray = $presenterGenerator->generatePresenter();
        $this->returnArray[] = 'Presenter';
    }

    private function generateTemplate(): void
    {
        $presenterGenerator = new \ModulIS\Generator\ClassGenerator\PresenterGenerator($this->classname, $this->module, $this->namespace, $this->baseDir, $this->injectArray, $this->componentArray, $this->propertyArray);
        $presenterGenerator->generatePresenterTemplate();
        $this->returnArray[] = 'Templates';
    }

    /**
     * Parse arguments from CMD for generators
     */
    private function parseArgument(array $rawArgs): void
    {
        $args = [];
        $i = 0;

        foreach($rawArgs as $rawArg)
        {
            if (preg_match('/^--([^=]+)=(.*)/', $rawArg, $matches))
            {
                $args[$matches[1]] = $matches[2];
            }
            else
            {
                $args[$i] = $rawArg;
                $i++;
            }
        }

        $argCount = count($args);
        $this->setConnection($args['db'] ?? null);

        if($argCount == 1)
        {
            throw new \ModulIS\Generator\Exception\MissingTableArgumentException('No table argument provided');
        }
        elseif($argCount > 5)
        {
            throw new \ModulIS\Generator\Exception\OverlimitArgumentException('Too many arguments provided');
        }

        if(!$this->checkTable($args[1]))
        {
            throw new \ModulIS\Generator\Exception\MissingTableException('Table with name "' . $args[1] . '" does not exist');
        }

        $this->table = $args[1];
        $this->module = isset($args['module'])
            ? Strings::endsWith($args['module'], 'Module') ? Strings::firstUpper($args['module']) : Strings::firstUpper($args['module']) . 'Module'
            : 'AdminModule';
        $this->namespace = str_replace('Module', '', $this->module);

        if(isset($args['type']))
        {
            if(in_array($args['type'], self::$typeArray))
            {
                $this->type = $args['type'];
            }
            else
            {
                throw new \ModulIS\Generator\Exception\WrongTypeException('Wrong type provided, use [' . implode(', ', self::$typeArray) . ']');
            }
        }
        else
        {
            $this->type = 'all';
        }

        $this->classname = Strings::contains($this->table, '_') ? $this->parseClassname() : Strings::firstUpper($this->table);
        $this->entityPath = '\\'. join('\\', [$this->namespace, 'Entity', $this->classname . 'Entity']);
    }

    /**
     * Replace _ in table name and capitalize letter after _
     */
    private function parseClassname(): string
    {
        $classname = '';

        foreach(explode('_', $this->table) as $namePart)
        {
            $classname .= Strings::firstUpper($namePart);
        }

        return $classname;
    }

    /**
     * Set connection to database from config.local.neon
     */
    private function setConnection(?string $database): void
    {
        $file = join(DIRECTORY_SEPARATOR, [$this->baseDir, 'config', 'local.neon']);
        $array = Neon::decode(file_get_contents($file));

        if($database)
        {
            $dsn = $array['database'][$database]['dsn'];
            $user = $array['database'][$database]['user'];
            $password = $array['database'][$database]['password'];
        }
        elseif(!empty($array['database']['default']))
        {
            $dsn = $array['database']['default']['dsn'];
            $user = $array['database']['default']['user'];
            $password = $array['database']['default']['password'];
        }
        else
        {
            $dsn = $array['database']['dsn'];
            $user = $array['database']['user'];
            $password = $array['database']['password'];
        }

        $this->connection = new \Nette\Database\Connection($dsn, $user, $password);
    }

    /**
     * Register class in neon.config for given module
     */
    private function registerClass(): void
    {
        $file = join(DIRECTORY_SEPARATOR, [$this->baseDir, $this->module, 'config.neon']);

        $array = file_exists($file) ? Neon::decode(file_get_contents($file)) : [];

        foreach($this->injectArray as $class)
        {
            if(!file_exists($file) || !in_array($class, $array['services']))
            {
                $array['services'][] = $class;
            }
        }

        if(!empty($array))
        {
            file_put_contents($file, Neon::encode($array, Neon::BLOCK));
        }
    }

    /**
     * Clear cache dir to avoid errors with newly generated files
     */
    private function clearCache(): void
    {
        $cacheDir = join(DIRECTORY_SEPARATOR, [$this->baseDir, '..', 'temp', 'cache']);

        if(is_dir($cacheDir))
        {
            foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($cacheDir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $path)
            {
                $path->isDir() && !$path->isLink() ? rmdir($path->getPathname()) : unlink($path->getPathname());
            }

            rmdir($cacheDir);
        }
    }

    /**
     * Return if table exist in given datatbase or not
     */
    private function checkTable(string $table): bool
    {
        $dbName = Strings::after($this->connection->getDsn(), 'name=');
        $sql = 'SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = "' . $dbName . '"
            AND table_name = "' . $table . '";';

        return count($this->connection->query($sql)->fetchAll()) > 0 ? true : false;
    }
}
