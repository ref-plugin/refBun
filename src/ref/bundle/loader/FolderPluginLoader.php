<?php

/**
 *
 *  ____  _             _         _____
 * | __ )| |_   _  __ _(_)_ __   |_   _|__  __ _ _ __ ___
 * |  _ \| | | | |/ _` | | '_ \    | |/ _ \/ _` | '_ ` _ \
 * | |_) | | |_| | (_| | | | | |   | |  __/ (_| | | | | | |
 * |____/|_|\__,_|\__, |_|_| |_|   |_|\___|\__,_|_| |_| |_|
 *                |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  Blugin team
 * @link    https://github.com/Blugin
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace ref\bundle\loader;

use pocketmine\plugin\PluginDescription;
use pocketmine\plugin\PluginEnableOrder;
use pocketmine\plugin\PluginLoader;
use pocketmine\Server;
use ref\bundle\traits\SingletonFactoryTrait;

use function array_merge;
use function file_get_contents;
use function is_dir;
use function is_file;

class FolderPluginLoader implements PluginLoader{
    use SingletonFactoryTrait;

    /** @noinspection PhpUndefinedFieldInspection */
    public function init() : void{
        $server = Server::getInstance();
        $pluginManager = $server->getPluginManager();
        $fileAssociations = [];
        (function(FolderPluginLoader $loader) use (&$fileAssociations){ //HACK : Closure bind hack to access inaccessible members
            /** @see \pocketmine\plugin\PluginManager::$fileAssociations */
            $fileAssociations = $this->fileAssociations;
            $this->fileAssociations = [$loader::class => $loader];
        })->call($pluginManager, $this);

        $pluginManager->loadPlugins($server->getPluginPath());
        $server->enablePlugins(PluginEnableOrder::STARTUP());

        (function(FolderPluginLoader $loader) use ($fileAssociations){ //HACK : Closure bind hack to access inaccessible members
            /** @see \pocketmine\plugin\PluginManager::$fileAssociations */
            $this->fileAssociations = array_merge($fileAssociations, $this->fileAssociations);
            unset($this->fileAssociations[$loader::class]);
        })->call($pluginManager, $this);
    }

    public function canLoadPlugin(string $path) : bool{
        return is_file($path . "/plugin.yml") && is_dir($path . "/src/");
    }

    public function loadPlugin(string $file) : void{
        $description = $this->getPluginDescription($file);
        if($description === null){
            return;
        }
        Server::getInstance()->getLoader()->addPath($description->getSrcNamespacePrefix(), "$file/src");
    }

    public function getPluginDescription(string $file) : ?PluginDescription{
        if(is_file($ymlFile = $file . "/plugin.yml") && !empty($yml = file_get_contents($ymlFile))){
            $description = new PluginDescription($yml);
            //Prevent load exists plugin
            return Server::getInstance()->getPluginManager()->getPlugin($description->getName()) === null ? $description : null;
        }

        return null;
    }

    public function getAccessProtocol() : string{
        return "";
    }
}
