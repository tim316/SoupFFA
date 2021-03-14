<?php
namespace Tim\SoupFFA\listener;

use pocketmine\entity\Entity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as SF;
use pocketmine\utils\Config;
use pocketmine\plugin\PluginBase;
use Tim\SoupFFA\Main;


class EventListener implements Listener{
    public $prefix = "§l§7[§dSoupFFA§7]§r§a";

    public function onEntityDamage(EntityDamageEvent $event): void{
        $entity = $event->getEntity();
        $main = Main::getMain();

        if(!$entity instanceof Entity) return;
        if($event instanceof EntityDamageByEntityEvent){
            $damager = $event->getDamager();

            if($entity->getNameTag() == "Battlepass"){
                $main->openBattleForm($damager);
            }
            if ($entity->getNameTag() == "Händler"){
                $main->openHändlerForm($damager);
            }
        }
    }




    public function onJoin(PlayerJoinEvent $event)
    {
        $main = Main::getMain();
        $player = $event->getPlayer();
        $main->openJoinForm($player);
        $main->setItems($player);
        $pName = $player->getName();
        if (!is_dir($main->getDataFolder() . "Database/"))
            mkdir($main->getDataFolder() . "Database/");
        Main::$uConfig[$pName] = new Config($main->getDataFolder() . "Database/$pName.yml", Config::YAML, [
            "kills" => 0,
            "deaths" => 0,
            "K/D" => 0,
            "Mana" => 0,
            "Coins" => 0,
            "Stufe1" => 0,
            "Stufe2" => 0,
            "Stufe3" => 0,
            "Stufe4" => 0,
            "Stufe5" => 0,
            "Stufe6" => 0,
            "Stufe7" => 0,
            "Stufe8" => 0,
            "Stufe9" => 0,
            "Stufe10" => 0]);
        $mana = Main::$uConfig[$pName]->get("Mana",0);
        if ($mana !== 0){
            Main::$uConfig[$pName]->set("Mana", 0);
            Main::$uConfig[$pName]->save();
        }
        $event->setJoinMessage($this->prefix . "§a[§b+§a]" . $player->getName());
        $player->sendMessage(SF::GRAY . "Willkommen in " . SF::LIGHT_PURPLE . "SoupFFA" . SF::GRAY . "!");
        foreach ($event->getPlayer()->getServer()->getOnlinePlayers() as $OnPly) {
            $OnPly->sendPopup(SF::GRAY . $pName . SF::GREEN . " will kämpfen!");
        }
    }

    public function onRespawn(PlayerRespawnEvent $event)
    {
        $player = $event->getPlayer();
        $main = Main::getMain();
        if (isset($main->diamondshoeslist[$player->getName()])) {
            $main->setItemsDiamondShoes($player);
        }
        if (isset($main->diamondheadlist[$player->getName()])) {
            $main->setItemsDiamondHelm($player);
        }
        if (isset($main->diomandhoselist[$player->getName()])) {
            $main->setItemsDiamondHose($player);
        }
        if (isset($main->diamondplatelist[$player->getName()])) {
            $main->setIteamsDiamondPlate($player);
        }
        if (!isset($main->diamondshoeslist[$player->getName()]) and !isset($main->diamondheadlist[$player->getName()]) and !isset($main->diomandhoselist[$player->getName()]) and !isset($main->diamondplatelist[$player->getName()]) ){
            $main->setItems($player);
        }





    }

    public function onJoinTitle(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $fadeInTime = 1* 20;
        $fadeOutTime = 1 * 20;
        $displayTime = 3 * 20;
        $player->addTitle("§6SoupFFA", "§aCryptoniaMC", $fadeInTime, $displayTime, $fadeOutTime);
    }

    public function onPlace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        if ($player->getLevel()->getFolderName() === "soupffa1" or $player->getLevel()->getFolderName() === "soupffa2") {
            if (!$event->getPlayer()->isOp()) {
                $event->setCancelled(true);
            }
        }

    }

    public function onBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        if ($player->getLevel()->getFolderName() === "soupffa1" or $player->getLevel()->getFolderName() === "soupffa2") {
            if (!$event->getPlayer()->isOp()) {
                $event->setCancelled(true);
            }
        }

    }
    
    public function onDrop(PlayerDropItemEvent $event)
    {
        $player = $event->getPlayer();
        if ($player->getLevel()->getFolderName() === "soupffa1" or $player->getLevel()->getFolderName() === "soupffa2" ) {
            if (!$event->getPlayer()->isOp()) {
                $event->setCancelled(true);
            }
        }
    }

   public function onPickUp(InventoryPickupItemEvent $event)
   {
           $event->setCancelled(true);
   }


    public function onTransaction(InventoryTransactionEvent $event)
    {
        $player = $event->getTransaction()->getSource()->getPlayer();
        if ($player->getLevel()->getFolderName() === "soupffa1" or $player->getLevel()->getFolderName() === "soupffa2") {
            if (!$event->getTransaction()->getSource()->getPlayer()->isOp()) {
                $event->setCancelled(true);

            }
        }
    }

    public function onDeath(PlayerDeathEvent $event)
    {
        $player = $event->getPlayer();
        $player->sendPopup("§6- 10 Mana");
        $entity = $event->getEntity();

        if ($entity instanceof Player) {
            $event->setDrops([]);
        }
        $player = $event->getPlayer();
        $killer = $player->getLastDamageCause();
        $player = $event->getPlayer();
        $pName = $player->getName();
        $lastDam = $player->getLastDamageCause();
        $main = Main::getMain();
        if ($lastDam instanceof EntityDamageByEntityEvent) {
            $killer = $lastDam->getDamager();
            if ($killer instanceof Player) {
                $kName = $killer->getName();
                $mana = Main::$uConfig[$pName]->get("Mana",0);
                if ($mana !== 0){
                    Main::$uConfig[$pName]->set("Mana", Main::$uConfig[$pName]->get("Mana",0) - 10);
                }
                Main::$uConfig[$pName]->set("deaths", Main::$uConfig[$pName]->get("deaths", 0) + 1);
                Main::$uConfig[$pName]->set("K/D", $main->getKillToDeathRatio($pName));
                Main::$uConfig[$pName]->save();
                Main::$uConfig[$kName]->set("Mana", Main::$uConfig[$kName]->get("Mana",0) + 10);
                Main::$uConfig[$kName]->set("kills", Main::$uConfig[$kName]->get("kills", 0) + 1);
                Main::$uConfig[$kName]->set("K/D", $main->getKillToDeathRatio($kName));
                Main::$uConfig[$kName]->save();
            }
        }
        if ($killer instanceof EntityDamageByEntityEvent) {
            $killer = $killer->getDamager();
            if ($killer instanceof Player) {
                $killer->sendPopup("§6+ 10 Mana");
                $event->setDeathMessage($this->prefix . SF::GREEN . $player->getName() . SF::GRAY . " wurde von " . SF::GREEN . $killer->getName() . SF::GRAY . " getötet!");
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event){
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($item->getId() == 282) {
            $event->setCancelled(true);
            $player->getInventory()->removeItem($item);
            $player->setHealth(20);
            $player->setFood(20);
        }
    }



}