<?php

namespace economysignbusiness\utils;

use pocketmine\item\Item;
use onebone\economyapi\EconomyAPI;
use pocketmine\scheduler\PluginTask;
use pocketmine\utils\Config;
use pocketmine\block\Block;

class Provider
{
	public function __construct($owner)
	{
		$this->owner = $owner;
	}

    /**
     * Yamlの取得
     */
    public function openYaml()
    {
        if(!file_exists($this->owner->getDataFolder())) {//configを入れるフォルダが有るかチェック
            @mkdir($this->owner->getDataFolder(), 0744, true);//なければ作成
        }
        $this->config = new Config($this->owner->getDataFolder()."shop.yml", Config::YAML);
    }

    /**
     * Yamlの保存
     */
    public function closeYaml()
    {
        $this->config->save();
    }

    /**
     * 商売データの取得
     * @param String $coordinate
     * @return array|bool
     */
    public function getShopData($coordinate)
    {
        $str_xyz = $this->getCoordinate($coordinate);
        if ($this->config->exists($str_xyz)) {
            return $this->config->get($str_xyz);
        } else {
            return false;
        }
    }

    /**
     * 商売データの削除
     * @param String $coordinate
     */
    public function removeShopData($coordinate)
    {
        $str_xyz = $this->getCoordinate($coordinate);
        if (!$this->config->exists($str_xyz)) return null;
        $this->config->remove($str_xyz);
        $this->config->save();
    }

    /**
     * 売却と購入の処理
     * @param String|Block $coordinate
     * @param int $id
     * @param int $damage
     * @param int $amount
     * @param int $price
     */
    public function setShopDataOfSellAndPurchase($coordinate, $id, $damage, $amount, $price)
    {
        $str_xyz = $this->getCoordinate($coordinate);
        if ($this->config->exists($str_xyz)) {
            $this->config->remove($str_xyz);
            $this->config->save();
        }
        $this->config->set($str_xyz, [
            "ID" => $id,
            "META" => $damage,
            "COUNT" => $amount,
            "PRICE" => $price
        ]);
        $this->config->save();
    }

    /**
     * 交換の処理
     * @param String|Block $coordinate
     * @param int $idA
     * @param int $damageA
     * @param int $amountA
     * @param int $idB
     * @param int $damageB
     * @param int $amountB
     */
    public function setShopDataOfExchange($coordinate, $idA, $damageA, $amountA, $idB, $damageB, $amountB)
    {
        $str_xyz = $this->getCoordinate($coordinate);
        if ($this->config->exists($str_xyz)) {
            $this->config->remove($str_xyz);
            $this->config->save();
        }
        $this->config->set($str_xyz, [
            "FROM" => [
                "ID" => $idA,
                "META" => $damageA,
                "COUNT" => $amountA
            ],
            "TO" => [
                "ID" => $idB,
                "META" => $damageB,
                "COUNT" => $amountB
            ]
        ]);
        $this->config->save();
    }

    /**
     * 交換の処理
     * @param array|Block $coordinate
     * @return String
     */
    public function getCoordinate($coordinate)
    {
        if ($coordinate instanceof Block) {
            $coordinate = (int)$coordinate->x.",".(int)$coordinate->y.",".(int)$coordinate->z;
        }
        if (is_array($coordinate)) {
            $coordinate = (int)$coordinate[0].",".(int)$coordinate[1].",".(int)$coordinate[2];
        }
        return $coordinate;
    }
}
