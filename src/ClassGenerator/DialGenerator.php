<?php

namespace ModulIS\Generator\ClassGenerator;

use Nette\Utils\Strings;

class DialGenerator
{
    private $classname;
    private $namespace;
    private $baseDir;
    private $constantArray;

    public function __construct
    (
        string $classname,
        string $namespace,
        string $baseDir,
        \Nette\Database\Row $column
    )
    {
        $this->namespace = $namespace;
        $this->classname = $classname . Strings::firstUpper($column['Field']);
        $this->baseDir = $baseDir;
        $this->constantArray = $this->parseColumn($column);
    }

    /**
     * Generate dial class for given column
     */
    public function generateDial(): void
    {
        $class = "<?php"
            . PHP_EOL
            . "declare(strict_types=1);"
            . PHP_EOL
            . PHP_EOL
            ."namespace " . $this->namespace . "\\Dial;"
            . PHP_EOL
            . PHP_EOL
            . "class " . $this->classname . "Dial extends \\Code\\Component\\Dial"
            . PHP_EOL
            . "{"
            . PHP_EOL
            . $this->generateConstant()
            . $this->generateTranslate()
            . PHP_EOL
            . "}"
            . PHP_EOL;

        $generateDir = join(DIRECTORY_SEPARATOR, [$this->baseDir, $this->namespace. 'Module', 'dial']);

        if(!is_dir($generateDir))
        {
            mkdir($generateDir, 0775, true);
        }

        file_put_contents($generateDir . DIRECTORY_SEPARATOR .  $this->classname . 'Dial.php', $class);
    }

    /**
     * Generate constants from column
     */
    private function parseColumn(\Nette\Database\Row  $column): array
    {
        $constantString = "";
        if(Strings::startsWith($column['Type'], 'enum'))
        {
            $prefix = "enum(";
            $suffix = ")";

            $constantString = str_replace($prefix, '', str_replace($suffix, '', $column['Type']));
        }

        $array = [];

        foreach(explode(',', $constantString) as $key => $constant)
        {
            $array[$key] = $constant;
        }

        return $array;
        }

    /**
     * Generate constant string
     */
    private function generateConstant(): string
    {
        $constantString = "";

        foreach($this->constantArray as $key => $constant)
        {
            $constantString .= "\tpublic const TMP" . $key . " = " . $constant . ";" . PHP_EOL . PHP_EOL;
        }

        return $constantString;
    }

    /**
     * Generate translate function for dial
     */
    private function generateTranslate(): string
    {
        $counter = 0;
        $constantCount = count($this->constantArray);

        foreach($this->constantArray as $key => $constant)
        {
            if($counter === 0)
            {
                $translateArray = "\t\t\tself::TMP" . $key . " => " . $constant . ",";
            }
            elseif($counter === $constantCount - 1)
            {
                $translateArray .= PHP_EOL . "\t\t\tself::TMP" . $key . " => " . $constant;
            }
            else
            {
                $translateArray .= PHP_EOL . "\t\t\tself::TMP" . $key . " => " . $constant . ",";
            }

            $counter++;
        }

        $functionString = PHP_EOL
            . "\t/**"
            . PHP_EOL
            . "\t * Translate of items"
            . PHP_EOL
            . "\t */"
            . PHP_EOL
            . "\tpublic static function translate(): array"
            . PHP_EOL
            . "\t{"
            . PHP_EOL
            . "\t\treturn ["
            . PHP_EOL
            . $translateArray
            . PHP_EOL
            . "\t\t];"
            . PHP_EOL
            . "\t}";

        return $functionString;
    }
}