<?php

namespace ModulIS\Generator;

class ComposerHandler
{
    public static function copy(\Composer\Script\Event $event)
    {
        $io = $event->getIO();

        $generatorBinDir = join(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'modul-is', 'generator', 'bin']) . DIRECTORY_SEPARATOR;
        $binDir = join(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bin']) . DIRECTORY_SEPARATOR;

        $count = 0;

        foreach(\Nette\Utils\Finder::findFiles('*')->from($generatorBinDir) as $file)
        {
            copy(realpath($file), $binDir . DIRECTORY_SEPARATOR . $file->getFilename());
            $count++;
        }

        $io->write('Number of files copied from: ' . realpath($generatorBinDir) . ' to: ' . realpath($binDir) . ' : ' . $count);
    }
}