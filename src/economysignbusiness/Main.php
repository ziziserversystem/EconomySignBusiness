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
        $version = $this->getDescription()->getVersion();
        $this->getLogger()->info("§6EconomySignBusiness §ev".$version);
        $this->getLogger()->info("§a製作者§2: §aOtorisanVardo");
        $this->getLogger()->info("§aTwitter§2: §ahttps://twitter.com/10ripon_obs");
        $this->getLogger()->info("§aForum§2: §ahttps://forum.mcbe.jp/members/17/");
        $this->getLogger()->info("§aLobi§2: §ahttps://web.lobi.co/user/ef7f70cd4c8c7e04dbf0f424d1f271a7ba68fc9f");
        $this->getLogger()->info("§2このプラグインにおいて何らかの問題が発生した場合は");
        $this->getLogger()->info("§2上記のツイッターかフォーラム、Lobiに連絡をお願いします。");
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