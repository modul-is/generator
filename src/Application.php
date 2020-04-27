<?php

namespace ModulIS\Generator;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

class Application
{
    public function run($args): string
    {
        $generator = new \ModulIS\Generator\ClassGenerator($args);
        return $generator->run();
    }
}

$app = new Application;

try
{
    $return = $app->run($argv);

    if($return)
    {
        echo $return . ' created and registered';
    }
    else
    {
        echo 'Unknown error occured';
    }

}
catch(\Exception $ex)
{
    echo $ex->getMessage();
}