<?php

/*
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

namespace blugin\tool\dev\virion;

use blugin\tool\dev\utils\Utils;
use pocketmine\Server;

class VirionLoader{
    /** @var VirionLoader */
    private static $instance = null;

    public static function getInstance() : VirionLoader{
        if(self::$instance === null){
            self::$instance = new VirionLoader();
        }
        return self::$instance;
    }

    /** @var Virion[] */
    private $virions = [];

    public function __construct(){
        foreach(["virions/", "plugins/_virions/", "plugins/virions/"] as $subdir){
            if(!is_dir($dir = Server::getInstance()->getDataPath() . $subdir))
                continue;

            foreach(Utils::readDirectory($dir) as $path){
                $virion = Virion::from($dir . $path);
                if($virion !== null){
                    $this->register($virion);
                }
            }
        }
    }

    public function register(Virion $virion) : void{
        $server = Server::getInstance();
        if(isset($this->virions[$virion->getName()])){
            $server->getLogger()->error("Could not load virion '" . $virion->getName() . "': virion exists");
            return;
        }
        $this->virions[$virion->getName()] = $virion;
        $server->getLoader()->addPath($virion->getPath() . "src/");

        $server->getLogger()->info("Loading virion '{$virion->getName()}' v{$virion->getVersion()} (antigen: {$virion->getAntigen()})");
    }

    public function getVirion(string $name) : ?Virion{
        return $this->virions[$name] ?? null;
    }
}
