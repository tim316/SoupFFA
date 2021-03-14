<?php

declare(strict_types=1);

namespace Tim\SoupFFA;

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\utils\TextFormat as SF;
use pocketmine\utils\TextFormat as TF;
use pocketmine\event\Listener;
use Tim\SoupFFA\listener\EventListener;
use pocketmine\utils\Config;
use pocketmine\Server;
use Tim\SoupFFA\Scoreboard;
use Tim\SoupFFA\ScoreboardTask;
use pocketmine\scheduler\Task as PluginTask;
use pocketmine\event\entity\EntityDamageEvent;

class Main extends PluginBase implements Listener{
    public $prefix = "§l§7[§dSoupFFA§7]§r§a";
    public $diamondshoeslist = [];
    public $diomandhoselist = [];
    public $diamondplatelist = [];
    public $diamondheadlist = [];
    private static $main;
    /** @var Config[]  */
    public static array $uConfig = [];

    public function onEnable()
    {
        $this->getServer()->getLogger()->info(TF::GOLD . "SoupFFA geladen!");
        #Register Listener
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        #Register Task
        $this->getScheduler()->scheduleRepeatingTask(new \Tim\SoupFFA\ScoreboardTask($this), 20);
        #Register Database
        @mkdir($this->getDataFolder() . "Database/");
        #Register Instance
        self::$main = $this;


    }

    public static function getMain(): self{
        return self::$main;
    }


    public function scoreboard(): void
    {
        foreach ($this->getServer()->getOnlinePlayers() as $players) {
            $pName = $players->getName();
            $onlineplayer = count($this->getServer()->getOnlinePlayers());
            $maxplayers = $this->getServer()->getMaxPlayers();
            if (!isset(self::$uConfig[$pName])) return;

            //Create
            \Tim\SoupFFA\Scoreboard::removeScoreboard($players, "SoupFFA");
            Scoreboard::createScoreboard($players, "§l§7[§dSoupFFA§7]§r§a ".$onlineplayer."§8/§a".$maxplayers, "SoupFFA");
            //Set Entrys
            Scoreboard::setScoreboardEntry($players, 1, "§7Mana [§a".$this->getMana($pName)."§7]    §7Coins [§a".$this->getCoins($pName)."§7]", "SoupFFA");
            Scoreboard::setScoreboardEntry($players, 2, "§5§lKD:", "SoupFFA");
            Scoreboard::setScoreboardEntry($players, 3, "§l§7>>§r§c " . self::getKillToDeathRatio($pName), "SoupFFA");
            Scoreboard::setScoreboardEntry($players, 4, "§2", "SoupFFA");
            Scoreboard::setScoreboardEntry($players, 5, "§5§lPing:", "SoupFFA");
            Scoreboard::setScoreboardEntry($players, 6, "§l§7>>§r§c " . $players->getPing(), "SoupFFA");
            Scoreboard::setScoreboardEntry($players, 7, "§7 ", "SoupFFA");
            Scoreboard::setScoreboardEntry($players, 8, "§5§lMap:", "SoupFFA");
            Scoreboard::setScoreboardEntry($players, 9, "§l§7>>§r§c " .$players->getLevel()->getName(), "SoupFFA");
            Scoreboard::setScoreboardEntry($players, 10, "§7", "SoupFFA");
            Scoreboard::setScoreboardEntry($players,11,"§l§acryptoniamc.de","SoupFFA");
        }
    }

    public function getKillToDeathRatio(string $pName): string
    {
        $kills = self::$uConfig[$pName]->get("kills", 0);
        $deaths = self::$uConfig[$pName]->get("deaths", 0);
        if($deaths !== 0) {
            $ratio = $kills / $deaths;
            if($ratio !== 0) {
                return number_format($ratio, 1);
            }
        }
        return "0.0";
    }

    public function openJoinForm($player)
    {
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createModalForm(function (Player $player, $data) {
            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0;
                    $this->openShopForm($player);
                    break;

                case 1;
                    $player->sendMessage($this->prefix . "§a Viel Spaß!");
                    break;

            }

        });

        $form->setTitle("§l§6SoupFFA");
        $form->setContent("§7Wilkommen in §6SoupFFA!\n§7Für jeden Kill bekommst du §b10 §aMana!\n§7Bei jedem Tod werden dir §b10 §aMana §7abgezogen!\nBeim §cHändler§7 kannst du §aMana §7gegen §eCoins §7tauschen\nMit §eCoins§7 kannst du dir im §dBattlepass §7Vorteile kaufen!\n§cACHTUNG! Bei jedem Leave wird dein Mana zurückgesetzt!\n§aViel Spaß!\n§6CryptoniaMC ");
        $form->setButton1("§l§cIch will kämpfen!");
        $form->setButton2("§l§aMenü");
        $form->sendToPlayer($player);
        return $form;

    }

    public function openShopForm($player){
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createSimpleForm(function (Player $player, int $data = null) {
            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0;
                    $this->openBattleForm($player);
                    break;

                case 1;
                    $this->openHändlerForm($player);
                    break;




            }

        });
        $form->setTitle($this->prefix . "§a Menü");
        $form->setContent("Wähle aus, wie du fortfahren willst!");
        $form->addButton("§bBattlepass",0,"textures/items/apple_golden");
        $form->addButton("§cHändler",0,"textures/items/book_writable");
        $form->sendToPlayer($player);
        return $form;
    }

    public function openHändlerForm($player){
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createSimpleForm(function (Player $player, int $data = null) {
            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0;
                    $pName = $player->getName();
                    $mana = $this->getMana($pName);
                    if ($mana > 99){
                        $player->sendMessage($this->prefix . TextFormat::GREEN . "Erfolgreich gekauft");
                        $player->sendPopup(TextFormat::GOLD . "+10 Coins");
                        self::$uConfig[$pName]->set("Coins", self::$uConfig[$pName]->get("Coins",0) + 10);
                        self::$uConfig[$pName]->set("Mana", self::$uConfig[$pName]->get("Mana",0) - 100);
                        self::$uConfig[$pName]->save();
                    }else{
                        $player->sendMessage($this->prefix . TextFormat::RED. "Nicht genug Mana!");
                    }
                    break;

                case 1;
                    $pName = $player->getName();
                    $mana = $this->getMana($pName);
                    if ($mana > 179){
                        $player->sendMessage($this->prefix . TextFormat::GREEN . "Erfolgreich gekauft");
                        $player->sendPopup(TextFormat::GOLD . "+20 Coins");
                        self::$uConfig[$pName]->set("Coins", self::$uConfig[$pName]->get("Coins",0) + 20);
                        self::$uConfig[$pName]->set("Mana", self::$uConfig[$pName]->get("Mana",0) - 180);
                        self::$uConfig[$pName]->save();
                    }else{
                        $player->sendMessage($this->prefix . TextFormat::RED. "Nicht genug Mana!");
                    }
                    break;

                case 2;
                    $pName = $player->getName();
                    $mana = $this->getMana($pName);
                    if($mana > 399){
                        $player->sendMessage($this->prefix . TextFormat::GREEN . "Erfolgreich gekauft");
                        $player->sendPopup(TextFormat::GOLD . "+50 Coins");
                        self::$uConfig[$pName]->set("Coins", self::$uConfig[$pName]->get("Coins",0) + 50);
                        self::$uConfig[$pName]->set("Mana", self::$uConfig[$pName]->get("Mana",0) - 400);
                        self::$uConfig[$pName]->save();
                    }else{
                        $player->sendMessage($this->prefix . TextFormat::RED. "Nicht genug Mana!");
                    }
                    break;




            }

        });
        $form->setTitle($this->prefix . "§a Händler");
        $form->setContent("Tausche §aMana §fgegen §cCoins!\n§f       §aMana:§7[".TF::GREEN.self::$uConfig[$player->getName()]->get(TF::WHITE."Mana", 0).TF::WHITE."§7]§f         §eCoins:§7[".TF::GREEN.self::$uConfig[$player->getName()]->get("Coins", 0). "§7]" );
        $form->addButton("§a10 §eCoins§7[§c100 Mana§7]",0,"textures/items/apple_golden");
        $form->addButton("§a20 §eCoins§7[§c180 Mana§7]",0,"textures/items/apple_golden");
        $form->addButton("§a50 §eCoins§7[§c400 Mana§7]",0,"textures/items/apple_golden");
        $form->sendToPlayer($player);
        return $form;

    }

    public function getMana($pName){
        return self::$uConfig[$pName]->get("Mana", 0);
    }


    public function openBattleForm($player){
        $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
        $form = $api->createSimpleForm(function (Player $player, int $data = null) {
            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0; //Speed Effect
                    $pName = $player->getName();
                    $Coins = $this->getCoins($pName);
                    $Stufe1 = $this->getStufe1($pName);
                    $Stufe2 = $this->getStufe2($pName);
                    $Stufe3 = $this->getStufe3($pName);
                    if ($Stufe1 !== 1) {
                        if ($Coins > 9) {
                            $player->sendMessage($this->prefix . " Erfolgreich freigeschaltet");
                            self::$uConfig[$pName]->set("Stufe1", self::$uConfig[$pName]->get("Stufe1", 0) + 1);
                            self::$uConfig[$pName]->set("Coins", self::$uConfig[$pName]->get("Coins", 0) - 10);
                            self::$uConfig[$pName]->save();
                            $effect = Effect::getEffect(1);
                            $duration = 20 * 20;
                            $amplification = 0;
                            $visivle = false;
                            $instance = new EffectInstance($effect, $duration, $amplification, $visivle);
                            $player->addEffect($instance);
                        } else {
                            $player->sendMessage($this->prefix . " Nicht genug Coins!");
                        }
                    }
                    if ($Stufe1 == 1) {
                        $player->sendMessage($this->prefix . " Erfolgreich ausgewählt!");
                        $effect = Effect::getEffect(1);
                        $duration = 20 * 20;
                        $amplification = 0;
                        $visivle = false;
                        $instance = new EffectInstance($effect, $duration, $amplification, $visivle);
                        $player->addEffect($instance);
                    }
                    break;

                case 1; //20 Coins
                    $pName = $player->getName();
                    $Coins = $this->getCoins($pName);
                    $Stufe1 = $this->getStufe1($pName);
                    $Stufe2 = $this->getStufe2($pName);
                    $Stufe3 = $this->getStufe3($pName);
                    if ($Stufe1 == 1) {
                        if ($Stufe2 !== 1) {
                            if ($Coins > 9) {
                                $player->sendMessage($this->prefix . " Erfolgreich eingelöst!");
                                self::$uConfig[$pName]->set("Stufe2", self::$uConfig[$pName]->get("Stufe2", 0) + 1);
                                self::$uConfig[$pName]->set("Coins", self::$uConfig[$pName]->get("Coins", 0) + 20);
                                self::$uConfig[$pName]->set("Coins", self::$uConfig[$pName]->get("Coins", 0) - 10);
                                self::$uConfig[$pName]->save();
                            } else {
                                $player->sendMessage($this->prefix . " Nicht genug Coins!");
                            }
                        }
                        if ($Stufe2 == 1) {
                            $player->sendMessage($this->prefix . " Bereits eingelöst!");
                        }
                    } else {
                        $player->sendMessage($this->prefix . " Schalte erst die Stufe davor frei!");
                    }
                    break;
                case 2; //Diamond Helm
                    $pName = $player->getName();
                    $Coins = $this->getCoins($pName);
                    $Stufe1 = $this->getStufe1($pName);
                    $Stufe2 = $this->getStufe2($pName);
                    $Stufe3 = $this->getStufe3($pName);
                    if ($Stufe1 == 1 and $Stufe2 == 1) {
                        if ($Stufe3 !== 1) {
                            if ($Coins > 9) {
                                $player->sendMessage($this->prefix . " Erfolgreich freigeschaltet!");
                                self::$uConfig[$pName]->set("Stufe3", self::$uConfig[$pName]->get("Stufe3", 0) + 1);
                                self::$uConfig[$pName]->set("Coins", self::$uConfig[$pName]->get("Coins", 0) - 10);
                                self::$uConfig[$pName]->save();
                                $this->setItemsDiamondHelm($player);
                                if (isset($this->diamondshoeslist[$player->getName()])) {
                                    unset($this->diamondshoeslist[$player->getName()]);
                                }
                                if (isset($this->diamondheadlist[$player->getName()])) {
                                    unset($this->diamondheadlist[$player->getName()]);
                                }
                                if (isset($this->diomandhoselist[$player->getName()])) {
                                    unset($this->diomandhoselist[$player->getName()]);
                                }
                                if (isset($this->diamondplatelist[$player->getName()])) {
                                    unset($this->diamondplatelist[$player->getName()]);
                                }
                                $this->diamondheadlist[$player->getName()] = $player->getName();
                            } else {
                                $player->sendMessage($this->prefix . " Nicht genug Coins!");
                            }
                        }
                        if ($Stufe3 == 1) {
                            $player->sendMessage($this->prefix . " Erfolgreich ausgewählt!");
                            $this->setItemsDiamondHelm($player);
                            if (isset($this->diamondshoeslist[$player->getName()])) {
                                unset($this->diamondshoeslist[$player->getName()]);
                            }
                            if (isset($this->diamondheadlist[$player->getName()])) {
                                unset($this->diamondheadlist[$player->getName()]);
                            }
                            if (isset($this->diomandhoselist[$player->getName()])) {
                                unset($this->diomandhoselist[$player->getName()]);
                            }
                            if (isset($this->diamondplatelist[$player->getName()])) {
                                unset($this->diamondplatelist[$player->getName()]);
                            }
                            $this->diamondheadlist[$player->getName()] = $player->getName();
                        }
                    } else {
                        $player->sendMessage($this->prefix . " Schalte erst die Stufe davor frei!");
                    }
                    break;
                case 3; //50 Mana
                    $pName = $player->getName();
                    $Coins = $this->getCoins($pName);
                    $Stufe1 = $this->getStufe1($pName);
                    $Stufe2 = $this->getStufe2($pName);
                    $Stufe3 = $this->getStufe3($pName);
                    $Stufe4 = $this->getStufe4($pName);
                    if ($Stufe1 == 1 and $Stufe2 == 1 and $Stufe3 == 1) {
                        if ($Stufe4 !== 1) {
                            if ($Coins > 9) {
                                $player->sendMessage($this->prefix . " Erfolgreich eingelöst!");
                                self::$uConfig[$pName]->set("Stufe4", self::$uConfig[$pName]->get("Stufe4", 0) + 1);
                                self::$uConfig[$pName]->set("Mana", self::$uConfig[$pName]->get("Mana", 0) + 50);
                                self::$uConfig[$pName]->set("Coins", self::$uConfig[$pName]->get("Coins", 0) - 10);
                                self::$uConfig[$pName]->save();
                            } else {
                                $player->sendMessage($this->prefix . " Nicht genug Coins!");
                            }
                        }

                        if ($Stufe4 == 1) {
                            $player->sendMessage($this->prefix . " Bereits eingelöst!");
                        }
                    } else {
                        $player->sendMessage($this->prefix . " Schalte erst die Stufe davor frei!");
                    }
                    break;

                case 4; //Enderperle
                    $pName = $player->getName();
                    $Coins = $this->getCoins($pName);
                    $Stufe1 = $this->getStufe1($pName);
                    $Stufe2 = $this->getStufe2($pName);
                    $Stufe3 = $this->getStufe3($pName);
                    $Stufe4 = $this->getStufe4($pName);
                    $Stufe5 = $this->getStufe5($pName);
                    if ($Stufe1 == 1 and $Stufe2 == 1 and $Stufe3 == 1 and $Stufe4 == 1) {
                        if ($Stufe5 !== 1) {
                            if ($Coins > 9) {
                                $player->sendMessage($this->prefix . " Erfolgreich freigeschaltet");
                                self::$uConfig[$pName]->set("Stufe5", self::$uConfig[$pName]->get("Stufe5", 0) + 1);
                                self::$uConfig[$pName]->set("Coins", self::$uConfig[$pName]->get("Coins", 0) - 10);
                                self::$uConfig[$pName]->save();
                                $this->setItemsEnderpearl($player);
                            }else{
                                $player->sendMessage($this->prefix-" Nicht genug Coins!");
                            }
                        }
                        if ($Stufe5 == 1) {
                            $player->sendMessage($this->prefix . " Erfolgreich ausgewählt!!");
                            $this->setItemsEnderpearl($player);
                        }
                    }else{
                        $player->sendMessage($this->prefix." Schalte erst die Stufe davor frei!");
                    }
                    break;
                case 5; //Jumpboost
                    $pName = $player->getName();
                    $Coins = $this->getCoins($pName);
                    $Stufe1 = $this->getStufe1($pName);
                    $Stufe2 = $this->getStufe2($pName);
                    $Stufe3 = $this->getStufe3($pName);
                    $Stufe4 = $this->getStufe4($pName);
                    $Stufe5 = $this->getStufe5($pName);
                    $Stufe6 = $this->getStufe6($pName);
                    if ($Stufe1 == 1 and $Stufe2 == 1 and $Stufe3 == 1 and $Stufe4 == 1 and $Stufe5 == 1) {
                        if ($Stufe6 !== 1) {
                            if ($Coins > 9) {
                                $player->sendMessage($this->prefix . " Erfolgreich freigeschaltet");
                                self::$uConfig[$pName]->set("Stufe6", self::$uConfig[$pName]->get("Stufe6", 0) + 1);
                                self::$uConfig[$pName]->set("Coins", self::$uConfig[$pName]->get("Coins", 0) - 10);
                                self::$uConfig[$pName]->save();
                                $effect = Effect::getEffect(8);
                                $duration = 20 * 20;
                                $amplification = 0;
                                $visible = false;
                                $instance = new EffectInstance($effect, $duration, $amplification, $visible); //Effect Instance
                                $player->addEffect($instance);

                            } else {
                                $player->sendMessage($this->prefix . " Nicht genug Coins!");
                            }
                        }
                        if ($Stufe6 == 1) {
                            $player->sendMessage($this->prefix . " Erfolgreich ausgewählt!");
                            $effect = Effect::getEffect(8);
                            $duration = 20 * 20;
                            $amplification = 0;
                            $visible = false;
                            $instance = new EffectInstance($effect, $duration, $amplification, $visible); //Effect Instance
                            $player->addEffect($instance);
                        }
                    }else{
                        $player->sendMessage($this->prefix." Schalte erst die Stufe davor frei!");
                    }
                    break;
                case 6; //Diamond Schuhe
                    $pName = $player->getName();
                    $Coins = $this->getCoins($pName);
                    $Stufe1 = $this->getStufe1($pName);
                    $Stufe2 = $this->getStufe2($pName);
                    $Stufe3 = $this->getStufe3($pName);
                    $Stufe4 = $this->getStufe4($pName);
                    $Stufe5 = $this->getStufe5($pName);
                    $Stufe6 = $this->getStufe6($pName);
                    $Stufe7 = $this->getStufe7($pName);
                    if ($Stufe1 == 1 and $Stufe2 == 1 and $Stufe3 == 1 and $Stufe4 == 1 and $Stufe5 == 1 and $Stufe6 == 1) {
                        if ($Stufe7 !== 1) {
                            if ($Coins > 9) {
                                $player->sendMessage($this->prefix . " Erfolgreich freigeschaltet!");
                                self::$uConfig[$pName]->set("Stufe7", self::$uConfig[$pName]->get("Stufe7", 0) + 1);
                                self::$uConfig[$pName]->set("Coins", self::$uConfig[$pName]->get("Coins", 0) - 10);
                                self::$uConfig[$pName]->save();
                                $this->setItemsDiamondShoes($player);
                                if (isset($this->diamondshoeslist[$player->getName()])) {
                                    unset($this->diamondshoeslist[$player->getName()]);
                                }
                                if (isset($this->diamondheadlist[$player->getName()])) {
                                    unset($this->diamondheadlist[$player->getName()]);
                                }
                                if (isset($this->diomandhoselist[$player->getName()])) {
                                    unset($this->diomandhoselist[$player->getName()]);
                                }
                                if (isset($this->diamondplatelist[$player->getName()])) {
                                    unset($this->diamondplatelist[$player->getName()]);
                                }
                                $this->diamondheadlist[$player->getName()] = $player->getName();
                            } else {
                                $player->sendMessage($this->prefix . " Nicht genug Coins!");
                            }
                        }
                        if ($Stufe7 == 1) {
                            $player->sendMessage($this->prefix . " Erfolgreich ausgewählt!");
                            $this->setItemsDiamondShoes($player);
                            if (isset($this->diamondshoeslist[$player->getName()])) {
                                unset($this->diamondshoeslist[$player->getName()]);
                            }
                            if (isset($this->diamondheadlist[$player->getName()])) {
                                unset($this->diamondheadlist[$player->getName()]);
                            }
                            if (isset($this->diomandhoselist[$player->getName()])) {
                                unset($this->diomandhoselist[$player->getName()]);
                            }
                            if (isset($this->diamondplatelist[$player->getName()])) {
                                unset($this->diamondplatelist[$player->getName()]);
                            }
                            $this->diamondheadlist[$player->getName()] = $player->getName();
                        }
                    } else {
                        $player->sendMessage($this->prefix . " Schalte erst die Stufe davor frei!");
                    }
                    break;
                case 7; //150 Mana
                    $pName = $player->getName();
                    $Coins = $this->getCoins($pName);
                    $Stufe1 = $this->getStufe1($pName);
                    $Stufe2 = $this->getStufe2($pName);
                    $Stufe3 = $this->getStufe3($pName);
                    $Stufe4 = $this->getStufe4($pName);
                    $Stufe5 = $this->getStufe5($pName);
                    $Stufe6 = $this->getStufe6($pName);
                    $Stufe7 = $this->getStufe7($pName);
                    $Stufe8 = $this->getStufe8($pName);
                    $Stufe9 = $this->getStufe9($pName);
                    $Stufe10 = $this->getStufe10($pName);
                    if ($Stufe1 == 1 and $Stufe2 == 1 and $Stufe3 == 1 and $Stufe4 == 1 and $Stufe5 == 1 and $Stufe6 == 1 and $Stufe7 == 1) {
                        if ($Stufe8 !== 1) {
                            if ($Coins > 9) {
                                $player->sendMessage($this->prefix . " Erfolgreich eingelöst!");
                                self::$uConfig[$pName]->set("Stufe8", self::$uConfig[$pName]->get("Stufe8", 0) + 1);
                                self::$uConfig[$pName]->set("Mana", self::$uConfig[$pName]->get("Mana", 0) + 150);
                                self::$uConfig[$pName]->set("Coins", self::$uConfig[$pName]->get("Coins", 0) - 10);
                                self::$uConfig[$pName]->save();
                            } else {
                                $player->sendMessage($this->prefix . " Nicht genug Coins!");
                            }
                        }

                        if ($Stufe8 == 1) {
                            $player->sendMessage($this->prefix . " Bereits eingelöst!");
                        }
                    } else {
                        $player->sendMessage($this->prefix . " Schalte erst die Stufe davor frei!");
                    }
                    break;
                case 8; //Diamond Hose
                    $pName = $player->getName();
                    $Coins = $this->getCoins($pName);
                    $Stufe1 = $this->getStufe1($pName);
                    $Stufe2 = $this->getStufe2($pName);
                    $Stufe3 = $this->getStufe3($pName);
                    $Stufe4 = $this->getStufe4($pName);
                    $Stufe5 = $this->getStufe5($pName);
                    $Stufe6 = $this->getStufe6($pName);
                    $Stufe7 = $this->getStufe7($pName);
                    $Stufe8 = $this->getStufe8($pName);
                    $Stufe9 = $this->getStufe9($pName);
                    $Stufe10 = $this->getStufe10($pName);
                    if ($Stufe1 == 1 and $Stufe2 == 1 and $Stufe3 == 1 and $Stufe4 == 1 and $Stufe5 == 1 and $Stufe6 == 1 and $Stufe7 == 1 and $Stufe8 == 1) {
                        if ($Stufe9 !== 1) {
                            if ($Coins > 9) {
                                $player->sendMessage($this->prefix . " Erfolgreich freigeschaltet!");
                                self::$uConfig[$pName]->set("Stufe9", self::$uConfig[$pName]->get("Stufe9", 0) + 1);
                                self::$uConfig[$pName]->set("Coins", self::$uConfig[$pName]->get("Coins", 0) - 10);
                                self::$uConfig[$pName]->save();
                                $this->setItemsDiamondHose($player);
                                if (isset($this->diamondshoeslist[$player->getName()])) {
                                    unset($this->diamondshoeslist[$player->getName()]);
                                }
                                if (isset($this->diamondheadlist[$player->getName()])) {
                                    unset($this->diamondheadlist[$player->getName()]);
                                }
                                if (isset($this->diomandhoselist[$player->getName()])) {
                                    unset($this->diomandhoselist[$player->getName()]);
                                }
                                if (isset($this->diamondplatelist[$player->getName()])) {
                                    unset($this->diamondplatelist[$player->getName()]);
                                }
                                $this->diomandhoselist[$player->getName()] = $player->getName();
                            } else {
                                $player->sendMessage($this->prefix . " Nicht genug Coins!");
                            }
                        }
                        if ($Stufe9 == 1) {
                            $player->sendMessage($this->prefix . " Erfolgreich ausgewählt!");
                            $this->setItemsDiamondHose($player);
                            if (isset($this->diamondshoeslist[$player->getName()])) {
                                unset($this->diamondshoeslist[$player->getName()]);
                            }
                            if (isset($this->diamondheadlist[$player->getName()])) {
                                unset($this->diamondheadlist[$player->getName()]);
                            }
                            if (isset($this->diomandhoselist[$player->getName()])) {
                                unset($this->diomandhoselist[$player->getName()]);
                            }
                            if (isset($this->diamondplatelist[$player->getName()])) {
                                unset($this->diamondplatelist[$player->getName()]);
                            }
                            $this->diomandhoselist[$player->getName()] = $player->getName();
                        }
                    } else {
                        $player->sendMessage($this->prefix . " Schalte erst die Stufe davor frei!");
                    }
                    break;
                case 9; //diamond plate
                    $pName = $player->getName();
                    $Coins = $this->getCoins($pName);
                    $Stufe1 = $this->getStufe1($pName);
                    $Stufe2 = $this->getStufe2($pName);
                    $Stufe3 = $this->getStufe3($pName);
                    $Stufe4 = $this->getStufe4($pName);
                    $Stufe5 = $this->getStufe5($pName);
                    $Stufe6 = $this->getStufe6($pName);
                    $Stufe7 = $this->getStufe7($pName);
                    $Stufe8 = $this->getStufe8($pName);
                    $Stufe9 = $this->getStufe9($pName);
                    $Stufe10 = $this->getStufe10($pName);
                    if ($Stufe1 == 1 and $Stufe2 == 1 and $Stufe3 == 1 and $Stufe4 == 1 and $Stufe5 == 1 and $Stufe6 == 1 and $Stufe7 == 1 and $Stufe8 == 1 and $Stufe9 == 1) {
                        if ($Stufe10 !== 1) {
                            if ($Coins > 9) {
                                $player->sendMessage($this->prefix . " Erfolgreich freigeschaltet!");
                                self::$uConfig[$pName]->set("Stufe10", self::$uConfig[$pName]->get("Stufe10", 0) + 1);
                                self::$uConfig[$pName]->set("Coins", self::$uConfig[$pName]->get("Coins", 0) - 10);
                                self::$uConfig[$pName]->save();
                                $this->setIteamsDiamondPlate($player);
                                if (isset($this->diamondshoes[$player->getName()])) {
                                    unset($this->diamondshoeslist[$player->getName()]);
                                }
                                if (isset($this->diamondhead[$player->getName()])) {
                                    unset($this->diamondheadlist[$player->getName()]);
                                }
                                if (isset($this->diomandhose[$player->getName()])) {
                                    unset($this->diomandhoselist[$player->getName()]);
                                }
                                if (isset($this->diamondplate[$player->getName()])) {
                                    unset($this->diamondplatelist[$player->getName()]);
                                }
                                $this->diamondplatelist[$player->getName()] = $player->getName();
                            } else {
                                $player->sendMessage($this->prefix . " Nicht genug Coins!");
                            }
                        }
                        if ($Stufe10 == 1) {
                            $player->sendMessage($this->prefix . " Erfolgreich ausgewählt!");
                            $this->setIteamsDiamondPlate($player);
                            if (isset($this->diamondshoeslist[$player->getName()])) {
                                unset($this->diamondshoeslist[$player->getName()]);
                            }
                            if (isset($this->diamondheadlist[$player->getName()])) {
                                unset($this->diamondheadlist[$player->getName()]);
                            }
                            if (isset($this->diomandhoselist[$player->getName()])) {
                                unset($this->diomandhoselist[$player->getName()]);
                            }
                            if (isset($this->diamondplatelist[$player->getName()])) {
                                unset($this->diamondplatelist[$player->getName()]);
                            }
                            $this->diamondplatelist[$player->getName()] = $player->getName();
                        }
                    } else {
                        $player->sendMessage($this->prefix . " Schalte erst die Stufe davor frei!");
                    }
                    break;



            }
        });
        foreach($this->getServer()->getOnlinePlayers() as $players) {
            $players = $players->getPlayer();
        }
        $pName = $players->getName();
        $Stufe1 = $this->getStufe1($pName);
        $Stufe2 = $this->getStufe2($pName);
        $Stufe3 = $this->getStufe3($pName);
        $Stufe4 = $this->getStufe4($pName);
        $Stufe5 = $this->getStufe5($pName);
        $Stufe6 = $this->getStufe6($pName);
        $Stufe7 = $this->getStufe7($pName);
        $Stufe8 = $this->getStufe8($pName);
        $Stufe9 = $this->getStufe9($pName);
        $Stufe10 = $this->getStufe10($pName);
        $form->setTitle($this->prefix . "§a Battlepass");
        $form->setContent("Schalte alle Stufen mit Coins frei!\n§eCoins:§7[".TF::GREEN.self::$uConfig[$player->getName()]->get("Coins", 0). "§7]");
        if ($Stufe1 == 1){
            $form->addButton("Stufe §b1§7[§6Freigeschaltet§7]\n§aBooster§7[§dGeschwindigkeit§7]",0,"textures/gui/newgui/mob_effects/speed_effect");
        }else {
            $form->addButton("Stufe §b1§7[§c10 Coins§7]\n§aBooster§7[§dGeschwindigkeit§7]",0,"textures/gui/newgui/mob_effects/speed_effect");
        }
        if ($Stufe2 == 1) {
            $form->addButton("Stufe §b2§7[§6Freigeschaltet§7]\n§aOneTimeBooster§7[§d20 Coins§7]",0,"textures/items/apple_golden");
        }else{
            $form->addButton("Stufe §b2§7[§c10 Coins§7]\n§aOneTimeBooster§7[§d20 Coins§7]",0,"textures/items/apple_golden");
        }
        if ($Stufe3 == 1){
            $form->addButton("Stufe §b3§7[§6Freigeschaltet§7]\n§aDiamond Helm",0,"textures/items/diamond_helmet");
        }else {
            $form->addButton("Stufe §b3§7[§c10 Coins§7]\n§aDiamond Helm",0,"textures/items/diamond_helmet");
        }
        if ($Stufe4 == 1){
            $form->addButton("Stufe §b4§7[§6Freigeschaltet§7]\n§aOneTimeBooster§7[§d50 Mana§7]",0,"textures/items/apple");
        }else {
            $form->addButton("Stufe §b4§7[§c10 Coins§7]\n§aOneTimeBooster§7[§d50 Mana§7]",0,"textures/items/apple");
        }
        if ($Stufe5 == 1){
            $form->addButton("Stufe §b5§7[§6Freigeschaltet]\n§aEnderperle",0,"textures/items/ender_pearl");
        }else {
            $form->addButton("Stufe §b5§7[§c10 Coins§7]\n§aEnderperle", 0, "textures/items/ender_pearl");
        }
        if ($Stufe6 == 1){
            $form->addButton("Stufe §b6§7[§6 Freigeschaltet§7]\n§aBooster§7[§dJumpBoost§7]",0,"textures/gui/newgui/mob_effects/jump_boost_effect");
        }else {
            $form->addButton("Stufe §b6§7[§c10 Coins§7]\n§aBooster§7[§dJumpBoost§7]",0,"textures/gui/newgui/mob_effects/jump_boost_effect");
        }
        if ($Stufe7 == 1){
            $form->addButton("Stufe §b7§7[§6Freigeschaltet§7]\n§aDiamond Schuhe",0,"textures/items/diamond_boots");
        }else {
            $form->addButton("Stufe §b7§7[§c10 Coins§7]\n§aDiamond Schuhe",0,"textures/items/diamond_boots");
        }
        if ($Stufe8 == 1){
            $form->addButton("Stufe §b8§7[§6Freigeschaltet§7]\n§aOneTimeBooster§7[§d150 Mana§7]",0,"textures/items/apple");
        }else {
            $form->addButton("Stufe §b8§7[§c10 Coins§7]\n§aOneTimeBooster§7[§d150 Mana§7]",0,"textures/items/apple");
        }
        if ($Stufe9 == 1){
            $form->addButton("Stufe §b9§7[§6Freigeschaltet§7]\n§aDiamond Hose",0,"textures/items/diamond_leggings");
        }else {
            $form->addButton("Stufe §b9§7[§c10 Coins§7]\n§aDiamond Hose",0,"textures/items/diamond_leggings");
        }
        if ($Stufe10 == 1){
            $form->addButton("Stufe §b10§7[§6Freigeschaltet§7]\n§aDiamond Brustplatte",0,"textures/items/diamond_chestplate");
        }else {
            $form->addButton("Stufe §b10§7[§c10 Coins§7]\n§aDiamond Brustplatte",0,"textures/items/diamond_chestplate");
        }

        $form->sendToPlayer($player);
        return $form;

    }

    public function getCoins($pName){
        return self::$uConfig[$pName]->get("Coins", 0);
    }
    public function getStufe1($pName){
        return self::$uConfig[$pName]->get("Stufe1", 0);
    }
    public function getStufe2($pName){
        return self::$uConfig[$pName]->get("Stufe2",0);
    }
    public function getStufe3($pName){
        return self::$uConfig[$pName]->get("Stufe3",0);
    }
    public function getStufe4($pName){
        return self::$uConfig[$pName]->get("Stufe4",0);
    }
    public function getStufe5($pName){
        return self::$uConfig[$pName]->get("Stufe5",0);
    }
    public function getStufe6($pName){
        return self::$uConfig[$pName]->get("Stufe6",0);
    }
    public function getStufe7($pName){
        return self::$uConfig[$pName]->get("Stufe7",0);
    }
    public function getStufe8($pName){
        return self::$uConfig[$pName]->get("Stufe8",0);
    }
    public function getStufe9($pName){
        return self::$uConfig[$pName]->get("Stufe9",0);
    }
    public function getStufe10($pName){
        return self::$uConfig[$pName]->get("Stufe10",0);
    }

    public function setItems(Player $player)
    {
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->setHelmet(Item::get(306));
        $player->getArmorInventory()->setChestplate(Item::get(307));
        $player->getArmorInventory()->setLeggings(Item::get(308));
        $player->getArmorInventory()->setBoots(Item::get(309));
        $player->getInventory()->setItem(0, Item::get(267));
        $player->getInventory()->setItem(1, Item::get(282));
        $player->getInventory()->setItem(2, Item::get(282));
        $player->getInventory()->setItem(3, Item::get(282));
        $player->getInventory()->setItem(4, Item::get(282));
        $player->getInventory()->setItem(5, Item::get(282));
        $player->getInventory()->setItem(6, Item::get(282));
        $player->getInventory()->setItem(7, Item::get(282));
        $player->getInventory()->setItem(8, Item::get(282));
    }

    public function setItemsEnderpearl(Player $player)
    {
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->setHelmet(Item::get(306));
        $player->getArmorInventory()->setChestplate(Item::get(307));
        $player->getArmorInventory()->setLeggings(Item::get(308));
        $player->getArmorInventory()->setBoots(Item::get(309));
        $player->getInventory()->setItem(0, Item::get(267));
        $player->getInventory()->setItem(1, Item::get(282));
        $player->getInventory()->setItem(2, Item::get(282));
        $player->getInventory()->setItem(3, Item::get(282));
        $player->getInventory()->setItem(4, Item::get(282));
        $player->getInventory()->setItem(5, Item::get(282));
        $player->getInventory()->setItem(6, Item::get(282));
        $player->getInventory()->setItem(7, Item::get(282));
        $player->getInventory()->setItem(8, Item::get(368));
    }

    public function setItemSnowball(Player $player)
    {
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->setHelmet(Item::get(306));
        $player->getArmorInventory()->setChestplate(Item::get(307));
        $player->getArmorInventory()->setLeggings(Item::get(308));
        $player->getArmorInventory()->setBoots(Item::get(309));
        $player->getInventory()->setItem(0, Item::get(267));
        $player->getInventory()->setItem(1, Item::get(282));
        $player->getInventory()->setItem(2, Item::get(282));
        $player->getInventory()->setItem(3, Item::get(282));
        $player->getInventory()->setItem(4, Item::get(282));
        $player->getInventory()->setItem(5, Item::get(282));
        $player->getInventory()->setItem(6, Item::get(282));
        $player->getInventory()->setItem(7, Item::get(282));
        $player->getInventory()->setItem(8, Item::get(332));
    }

    public function setItemsDiamondHelm(Player $player)
    {
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->setHelmet(Item::get(310));
        $player->getArmorInventory()->setChestplate(Item::get(307));
        $player->getArmorInventory()->setLeggings(Item::get(308));
        $player->getArmorInventory()->setBoots(Item::get(309));
        $player->getInventory()->setItem(0, Item::get(267));
        $player->getInventory()->setItem(1, Item::get(282));
        $player->getInventory()->setItem(2, Item::get(282));
        $player->getInventory()->setItem(3, Item::get(282));
        $player->getInventory()->setItem(4, Item::get(282));
        $player->getInventory()->setItem(5, Item::get(282));
        $player->getInventory()->setItem(6, Item::get(282));
        $player->getInventory()->setItem(7, Item::get(282));
        $player->getInventory()->setItem(8, Item::get(282));
    }

    public function setItemsDiamondShoes(Player $player)
    {
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->setHelmet(Item::get(306));
        $player->getArmorInventory()->setChestplate(Item::get(307));
        $player->getArmorInventory()->setLeggings(Item::get(308));
        $player->getArmorInventory()->setBoots(Item::get(313));
        $player->getInventory()->setItem(0, Item::get(267));
        $player->getInventory()->setItem(1, Item::get(282));
        $player->getInventory()->setItem(2, Item::get(282));
        $player->getInventory()->setItem(3, Item::get(282));
        $player->getInventory()->setItem(4, Item::get(282));
        $player->getInventory()->setItem(5, Item::get(282));
        $player->getInventory()->setItem(6, Item::get(282));
        $player->getInventory()->setItem(7, Item::get(282));
        $player->getInventory()->setItem(8, Item::get(282));
    }

    public function setItemsDiamondHose(Player $player)
    {
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->setHelmet(Item::get(306));
        $player->getArmorInventory()->setChestplate(Item::get(307));
        $player->getArmorInventory()->setLeggings(Item::get(312));
        $player->getArmorInventory()->setBoots(Item::get(309));
        $player->getInventory()->setItem(0, Item::get(267));
        $player->getInventory()->setItem(1, Item::get(282));
        $player->getInventory()->setItem(2, Item::get(282));
        $player->getInventory()->setItem(3, Item::get(282));
        $player->getInventory()->setItem(4, Item::get(282));
        $player->getInventory()->setItem(5, Item::get(282));
        $player->getInventory()->setItem(6, Item::get(282));
        $player->getInventory()->setItem(7, Item::get(282));
        $player->getInventory()->setItem(8, Item::get(282));
    }

    public function setIteamsDiamondPlate(Player $player){
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->setHelmet(Item::get(306));
        $player->getArmorInventory()->setChestplate(Item::get(311));
        $player->getArmorInventory()->setLeggings(Item::get(308));
        $player->getArmorInventory()->setBoots(Item::get(309));
        $player->getInventory()->setItem(0, Item::get(267));
        $player->getInventory()->setItem(1, Item::get(282));
        $player->getInventory()->setItem(2, Item::get(282));
        $player->getInventory()->setItem(3, Item::get(282));
        $player->getInventory()->setItem(4, Item::get(282));
        $player->getInventory()->setItem(5, Item::get(282));
        $player->getInventory()->setItem(6, Item::get(282));
        $player->getInventory()->setItem(7, Item::get(282));
        $player->getInventory()->setItem(8, Item::get(282));
    }


    public function onDisable()
    {
        $this->getServer()->getLogger()->info(TF::GOLD . "SoupFFA entladen!");
    }

}
