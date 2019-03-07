<?php

namespace economysignbusiness;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\Item;
use pocketmine\tile\Sign;
use pocketmine\scheduler\Task;

use economysignbusiness\utils\API;
use economysignbusiness\utils\NameManager;
use onebone\economyapi\EconomyAPI;

class EventListener implements Listener
{
	
	public $cooltime;

    public function __construct($owner)
    {
        $this->owner = $owner;
    }


    public function onTap(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
		$name = $player->getName();
        if (!in_array($block->getId(), API::BLOCK_SIGN)) return;
        $tile = $player->getLevel()->getTile($block);
        if ($tile instanceof Sign) {
            $line = $tile->getText();
            if (!isset($line[0])) return;
            $tag = $line[0];
            if ($tag !== API::PURCHASE_TAG && $tag !== API::SELL_TAG && $tag !== API::EXCHANGE_TAG) {
                return;
            }
            $unit = EconomyAPI::getInstance()->getMonetaryUnit();
			
			if (!isset($this->cooltime[$name])) {
                $this->checkDoProgress($player, $block, $name);
                return;
            }
		if ($block->asVector3() != $this->cooltime[$name]) {
                $this->checkDoProgress($player, $block, $name);
                return;
            }
            unset($this->cooltime[$name]);
            
            switch ($line[0]) {
                case API::PURCHASE_TAG:
                    $this->getApi()->purchaseItem($player, $block);
                    break;

                case API::SELL_TAG:
                    $this->getApi()->sellItem($player, $block);
                    break;

                case API::EXCHANGE_TAG:
                    $this->getApi()->exchangeItem($player, $block);
                    break;
            }
        }
    }


    public function onBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if (!in_array($block->getId(), API::BLOCK_SIGN)) return;
        $tile = $player->getLevel()->getTile($block);
        if ($tile instanceof Sign) {
            $line = $tile->getText();
            if (!isset($line[0])) return;
            $tag = $line[0];
            if ($tag !== API::PURCHASE_TAG && $tag !== API::SELL_TAG && $tag !== API::EXCHANGE_TAG) {
                return;
            }
            if (!$player->isOp()) {
            	$player->sendMessage("§c> 削除できる権限がありません");
            	$event->setCancelled();
            	return;
            }
            switch ($line[0]) {
                case API::PURCHASE_TAG:
                case API::SELL_TAG:
                case API::EXCHANGE_TAG:
                    $this->getProvider()->removeShopData($block);
                    $player->sendMessage("§a> 削除しました");
                    break;
            }
        }
    }


    public function onChange(SignChangeEvent $event)
    {
        $player = $event->getPlayer();
        $line = $event->getLines();
        if (empty($line[0])) return;
        if (empty($line[1])) return;
        if (empty($line[2])) return;
        if (!in_array($line[0], API::REQUIRE_FIRST_LINE)) return;
        if (!$player->isOp()) {
            $player->sendMessage("§c> 製作できる権限がありません");
            return;
        }
        switch ($line[0]) {
            case "buy":
            case "purchase":
                if (empty($line[3])) return;
                $item = explode(":", $line[1]);
                if (count($item) == 1) $item[1] = 0;
                if (!ctype_digit($item[0])) {
                    $player->sendMessage("§c> ID(数字)を書き込んでください");
                    return;
                }
                $itemName = Item::get((int)$item[0], (int)$item[1])->getName();
                if (!ctype_digit($line[2])) {
                    $player->sendMessage("§c> 数値を書き込んでください");
                    return;
                }
                $amount = (int) $line[2];
                $unit = EconomyAPI::getInstance()->getMonetaryUnit();
                if (!ctype_digit($line[3])) {
                    $player->sendMessage("§c> 数値を書き込んでください");
                    return;
                }
                $price = (int) $line[3];
                $event->setLine(0, API::PURCHASE_TAG);
                $event->setLine(1, "§l".$itemName);
                $event->setLine(2, "§l".$amount);
                $event->setLine(3, "§l".$unit.$price);
                $this->getProvider()->setShopDataOfSellAndPurchase($event->getBlock(), $item[0], $item[1], $amount, $price);
                $player->sendMessage("§a> 販売看板を作りました");
                break;

            case "sell":
                if (empty($line[3])) return;
                $item = explode(":", $line[1]);
                if (count($item) == 1) $item[1] = 0;
                if ($item[1] == null) {
                    $player->sendMessage("§c> しっかりとID:METAの形で書き込んでください");
                    return;
                }
                $itemName = Item::get((int)$item[0], (int)$item[1])->getName();
                if (!ctype_digit($line[2])) {
                    $player->sendMessage("§c> 数値を書き込んでください");
                    return;
                }
                $amount = (int) $line[2];
                $unit = EconomyAPI::getInstance()->getMonetaryUnit();
                if (!ctype_digit($line[3])) {
                    $player->sendMessage("§c> 数値を書き込んでください");
                    return;
                }
                $price = (int) $line[3];
                $event->setLine(0, API::SELL_TAG);
                $event->setLine(1, "§l".$itemName);
                $event->setLine(2, "§l".$amount);
                $event->setLine(3, "§l".$unit.$price);
                $this->getProvider()->setShopDataOfSellAndPurchase($event->getBlock(), $item[0], $item[1], $amount, $price);
                $player->sendMessage("§a> 売却看板を作りました");
                break;

            case "exchange":
            case "trade":
                $material = explode(":", $line[1]);
                $goods = explode(":", $line[2]);
                if (count($material) < 3) return;
                $materialItem = Item::get((int)$material[0], (int)$material[1])->getName();
                if (count($goods) < 3) return;
                $goodsItem = Item::get((int)$goods[0], (int)$goods[1])->getName();
                $event->setLine(0, API::EXCHANGE_TAG);
                $event->setLine(1, "§l送: ".$materialItem." ".$material[2]."個");
                $event->setLine(2, "§l受: ".$goodsItem." ".$goods[2]."個");
                if (!ctype_digit($goods[2])) return;
                $this->getProvider()->setShopDataOfExchange($event->getBlock(), $material[0], $material[1], $material[2], $goods[0], $goods[1], $goods[2]);
                $player->sendMessage("§a> トレード看板を作りました");
                break;
        }
    }

    public function getServer()
    {
        return $this->owner->getServer();
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function getApi()
    {
        return $this->owner->api;
    }

    public function getProvider()
    {
        return $this->owner->provider;
    }
	 public function checkDoProgress($player, $block, $name)
    {
        $player->sendMessage("§bもう一度タッチしてください");
	$this->cooltime[$name] = $block->asVector3();
        $handler = $this->owner->getScheduler()->scheduleDelayedTask(
            new class($this->owner, $name) extends Task
            {
                function __construct($owner, $name)
                {
                    $this->owner = $owner;
					$this->name = $name;
                }

                function onRun(int $tick)
                {
				unset($this->cooltime[$this->name]);
                }
            }, 3*20
        );
}
}