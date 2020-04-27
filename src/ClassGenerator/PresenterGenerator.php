<?php

namespace ModulIS\Generator\ClassGenerator;

use Nette\Utils\Strings;

class PresenterGenerator
{
    private $classname;

    private $module;

    private $namespace;

    private $baseDir;

    /**
     * @var array
     */
    private $injectArray;

    /**
     * @var array
     */
    private $componentArray;

    /**
     * @var array
     */
    private $propertyArray;

    public function __construct
    (
        string $classname,
        string $module,
        string $namespace,
        string $baseDir,
        array $injectArray,
        array $componentArray,
        array $propertyArray = []
    )
    {
        $this->classname = $classname;
        $this->module = $module;
        $this->namespace = $namespace;
        $this->baseDir = $baseDir . DIRECTORY_SEPARATOR . $namespace . 'Module';
        $this->injectArray = $injectArray;
        $this->componentArray = $componentArray;
        $this->propertyArray = $propertyArray;
    }

    public function generatePresenter(): array
    {
        $class = "<?php"
            . PHP_EOL
            . "declare(strict_types=1);"
            . PHP_EOL
            . PHP_EOL
            ."namespace App\\" . $this->module . ";"
            . PHP_EOL
            . PHP_EOL
            . "class " . $this->classname .  "Presenter extends \\Code\\User\\AuthPresenter"
            . PHP_EOL
            . "{"
            . $this->generateInject()
            . $this->generateComponent()
            . $this->generateFormAction()
            . PHP_EOL
            . "}"
            . PHP_EOL;

        $generateDir = join(DIRECTORY_SEPARATOR, [$this->baseDir, 'presenter']);

        if(!is_dir($generateDir))
        {
            mkdir($generateDir, 0775, true);
        }

        file_put_contents($generateDir . DIRECTORY_SEPARATOR . $this->classname . 'Presenter.php', $class);

        return $this->propertyArray;
    }

    public function generatePresenterTemplate(): void
    {
        $generateDir = join(DIRECTORY_SEPARATOR, [$this->baseDir, 'templates', $this->classname]);

        if(!is_dir($generateDir))
        {
            mkdir($generateDir, 0775, true);
        }

        if(!$this->propertyArray)
        {
            $presenter = join(DIRECTORY_SEPARATOR, [$this->baseDir, 'presenter', $this->classname . 'Presenter.php']);

            if(file_exists($presenter))
            {
                include $presenter;

                preg_match('/namespace\s+(.+?);/', file_get_contents($presenter), $matches);

                $presenterNamespace = $matches[1] . '\\' . $this->classname . 'Presenter';
                $presenterReflection = new \ReflectionClass($presenterNamespace);

                foreach($presenterReflection->getProperties() as $property)
                {
                    if($property->class == $presenterNamespace)
                    {
                        if(Strings::contains($property->getName(), 'Factory'))
                        {
                            $this->propertyArray[] = $property->getName();
                        }
                    }
                }
            }
            else
            {
                throw new \ModulIS\Generator\Exception\MissingPresenterException('Cannot generate template without presenter');
            }
        }

        foreach($this->propertyArray as $property)
        {
            $componentName = Strings::firstLower(str_replace('Factory', '', substr($property, 1)));

            $templateString = "{block header}"
                . PHP_EOL
                . "\t"
                . PHP_EOL
                . "\t"
                . PHP_EOL
                . "{/block}"
                . PHP_EOL
                . PHP_EOL
                . "{block toolbox}"
                . PHP_EOL;

            if(Strings::contains(Strings::lower($componentName), 'form'))
            {
                $templateName = Strings::firstLower($this->classname) . 'Form.latte';
                $templateString .= "\t<a n:href=\"" . Strings::firstLower($this->classname) . 'Grid" class="btn btn-sm btn-outline-gray" title="Zpět na přehled">'
                    . PHP_EOL
                    . "\t\t{icon backward}"
                    . PHP_EOL
                    . "\t\t"
                    . PHP_EOL
                    . "\t</a>"
                    . PHP_EOL
                    . '{/block}'
                    . PHP_EOL
                    . PHP_EOL
                    . '{block content}'
                    . PHP_EOL
                    . PHP_EOL
                    . '{control ' . Strings::firstLower($componentName) . '}';
            }
            else
            {
                $templateName = Strings::firstLower($this->classname) . 'Grid.latte';
                $templateString .= "\t<a n:href=\"" . Strings::firstLower($this->classname) . 'Form" class="btn btn-sm btn-outline-gray">'
                    . PHP_EOL
                    . "\t\t{icon plus}"
                    . PHP_EOL
                    . "\t\t"
                    . PHP_EOL
                    . "\t</a>"
                    . PHP_EOL
                    . '{/block}'
                    . PHP_EOL
                    . PHP_EOL
                    . '{block content}'
                    . PHP_EOL
                    . PHP_EOL
                    . '{control ' . Strings::firstLower($componentName) . '}';
            }

            file_put_contents($generateDir . DIRECTORY_SEPARATOR . $templateName, $templateString);
        }
    }

    public function generateInject(): ?string
    {
        $array = [];

        if(!$this->injectArray && file_exists($this->baseDir))
        {
            $finder = \Nette\Utils\Finder::findFiles('I' . $this->classname . 'FormFactory.php', 'I' . $this->classname . 'GridFactory.php', $this->classname . 'Repository.php')
                ->from($this->baseDir);

            foreach($finder as $file)
            {
                include $file->getRealPath();

                preg_match('/namespace\s+(.+?);/', file_get_contents($file->getRealPath()), $matches);

                $this->injectArray[] = $matches[1] . '\\' . $file->getBasename('.php');
            }
        }

        foreach($this->injectArray as $key => $inject)
        {
            if(Strings::contains($inject, 'Factory'))
            {
                $this->propertyArray[] = Strings::after($inject, "\\", -1);
            }

            $propertyName = Strings::after($inject, "\\", -1);

            $injectString = "\t/**"
                . PHP_EOL
                . "\t * @inject"
                . PHP_EOL
                . "\t * @var \\" . $inject
                . PHP_EOL
                . "\t */"
                . PHP_EOL
                . "\tpublic \$" . $propertyName . ";";

            $array[] = $injectString;
        }

        return !empty($array) ? PHP_EOL . implode(PHP_EOL . PHP_EOL, $array) : null;
    }

    public function generateComponent(): ?string
    {
        $array = [];

        if(!$this->componentArray && file_exists($this->baseDir))
        {
            $finder = \Nette\Utils\Finder::findFiles($this->classname . 'Form.php', $this->classname . 'Grid.php')
                ->from($this->baseDir);

            foreach($finder as $file)
            {
                include $file->getRealPath();

                preg_match('/namespace\s+(.+?);/', file_get_contents($file->getRealPath()), $matches);

                $this->componentArray[] = $matches[1] . '\\' . $file->getBasename('.php');
            }
        }

        foreach($this->propertyArray as $key => $property)
        {
            $componentName = str_replace('Factory', '', substr($property, 1));

            $componentString = "\tpublic function createComponent" . $componentName . "(): \\" . $this->componentArray[$key]
                . PHP_EOL
                . "\t{"
                . PHP_EOL;

            if(Strings::contains(Strings::lower($componentName), 'form'))
            {
                $componentString .= "\t\t\$control = \$this->" . $property . "->create();"
                    . PHP_EOL
                    . "\t\t\$control->set" . $this->classname . "Id(\$this->getParameter('id'));"
                    . PHP_EOL
                    . PHP_EOL
                    . "\t\treturn \$control;";
            }
            else
            {
                $componentString .= "\t\treturn \$this->" . $property . "->create();";
            }

            $componentString .= PHP_EOL
                . "\t}";

            $array[] = $componentString;
        }

        return !empty($array) ? PHP_EOL . PHP_EOL . PHP_EOL . implode(PHP_EOL . PHP_EOL . PHP_EOL, $array) : null;
    }

    public function generateFormAction(): ?string
    {
        $form = join(DIRECTORY_SEPARATOR, [$this->baseDir, 'Form', $this->classname . 'Form', $this->classname . 'Form.php']);

        if(file_exists($form))
        {
            $functionString = PHP_EOL
                . PHP_EOL
                . PHP_EOL
                . "\tpublic function action" . $this->classname . "Form(\$id): void"
                . PHP_EOL
                . "\t{"
                . PHP_EOL
                . "\t\t\$this['" . Strings::firstLower($this->classname) . "Form']->prepare();"
                . PHP_EOL
                . "\t}";

            return $functionString;
        }

        return null;
    }
}
