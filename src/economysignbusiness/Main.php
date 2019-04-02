<?php

namespace economysignbusiness;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\plugin\PluginBase;

use economysignbusiness\utils\API;
use economysignbusiness\utils\Provider;
use economysignbusiness\EventListener;

class Main extends PluginBase
{
    public function onEnable()
    {
        $this->api = new API($this);
        $this->provider = new Provider($this);
        $this->provider->openYaml();
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }

    public function onDisable()
    {
        $this->provider->closeYaml();
    }

    public function getApi()
    {
        return $this->api;
    }

    public function getProvider()
    {
        return $this->provider;
    }
}
