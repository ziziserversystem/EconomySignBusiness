<?php

namespace economysignbusiness\utils;

use pocketmine\item\Item;
use onebone\economyapi\EconomyAPI;
use pocketmine\scheduler\Task;


class API
{

	const BLOCK_SIGN = [ 63, 68, 323 ];
	const PURCHASE_TAG = "§l§6[購入]";
	const SELL_TAG = "§l§b[売却]";
	const EXCHANGE_TAG = "§l§a[交換]";
	const REQUIRE_FIRST_LINE = ["purchase", "buy", "sell", "exchange", "trade"];
	

    /**
     * 親クラスの継承
     */
	public function __construct($owner)
	{
		$this->owner = $owner;
	}

    /**
     * データファイルのクラス
     */
    public function getProvider()
    {
        return $this->owner->getProvider();
    }

    /**
     * アイテムの購入処理
     * @param Player $player
     * @param  Block $tapBlock
     */
    public function purchaseItem($player, $tapBlock)
    {
        $xyz = $this->getProvider()->getCoordinate($tapBlock);
        $data = $this->getProvider()->getShopData($xyz);
        if ($data == false) {
            $player->sendMessage("§cデータがありません");
            return;
        }
        $price = $data["PRICE"];
        if ($this->hasEnoughMoney($player, $price)) {
            if ($player->isCreative()) {
                $player->sendMessage("§cクリエイティブモードでは購入できません");
                return;
            }
            $item = Item::get($data["ID"], $data["META"], $data["COUNT"]);
            if (!$player->getInventory()->canAddItem($item)) {
                $player->sendMessage("§c手持ちが一杯で持てません");
                return;
            }
            EconomyAPI::getInstance()->reduceMoney($player, $price);
            $player->getInventory()->addItem($item);
            $player->sendMessage("§a購入が完了しました");
        } else {
            $player->sendMessage("§cお金が足りません");
        }
    }

    /**
     * アイテムの売却処理
     * @param Player $player
     * @param  Block $tapBlock
     */
    public function sellItem($player, $tapBlock)
    {
        $xyz = $this->getProvider()->getCoordinate($tapBlock);
        $data = $this->getProvider()->getShopData($xyz);
        if ($data == false) {
            $player->sendMessage("§cデータがありません");
            return;
        }
        $price = $data["PRICE"];
        if ($player->isCreative()) {
            $player->sendMessage("§cクリエイティブモードでは売却できません");
            return;
        }
        $item = Item::get($data["ID"], $data["META"], $data["COUNT"]);
        if (!$player->getInventory()->contains($item)) {
            $player->sendMessage("§c資材が足りません");
            return;
        }
        EconomyAPI::getInstance()->addMoney($player, $price);
        $player->getInventory()->removeItem($item);
        $player->sendMessage("§a売却が完了しました");
    }

    /**
     * アイテムの交換処理
     * @param Player $player
     * @param  Block $tapBlock
     */
    public function exchangeItem($player, $tapBlock)
    {
        $xyz = $this->getProvider()->getCoordinate($tapBlock);
        $data = $this->getProvider()->getShopData($xyz);
        if ($data == false) {
            $player->sendMessage("§cデータがありません");
            return;
        }
        if ($player->isCreative()) {
            $player->sendMessage("§cクリエイティブモードでは取引できません");
            return;
        }
        $material = Item::get((int)$data["FROM"]["ID"], (int)$data["FROM"]["META"], (int)$data["FROM"]["COUNT"]);
        $goods = Item::get((int)$data["TO"]["ID"], (int)$data["TO"]["META"], (int)$data["TO"]["COUNT"]);
        if (!$player->getInventory()->contains($material)) {
            $player->sendMessage("§c資材が足りません");
            return;
        }
        $player->getInventory()->removeItem($material);
        $player->getInventory()->addItem($goods);
        $player->sendMessage("§a取引が完了しました");
    }

    /**
     * 十分なお金を持っているか
     * @param Player $player
     * @param    int $price
     * @return  bool
     */
    public function hasEnoughMoney($player, $price)
    {
        $wallet = EconomyAPI::getInstance()->myMoney($player);
        return ($wallet >= $price) ? true : false;
    }
	
	/**
     * 重複処理の確認
     * @param Player $player
     * @param  Block $block
     */
   
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
