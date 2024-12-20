<?php

namespace Snow;

use pocketmine\data\bedrock\BiomeIds;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

//This project was made with the help of https://github.com/PetteriM1/Snow/blob/master/src/main/java/suomicraftpe/events/christmas/Main.java
class SnowPlugin extends PluginBase implements Listener
{
    /**
     * @var string[]
     */
    private array $worlds;

    protected function onEnable(): void
    {
        $this->saveDefaultConfig();

        /** @var string[] $worlds */
        $worlds = (array) $this->getConfig()->get('worlds', []);
        $this->worlds = $worlds;

        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->getLogger()->info(TextFormat::GREEN . 'Snow plugin has been enabled!');
    }

    public function onChunkLoaded(ChunkLoadEvent $event): void
    {
        $world = $event->getWorld();
        $chunkX = $event->getChunkX();
        $chunkZ = $event->getChunkZ();

        for ($x = 0; $x < 16; ++$x)
        {
            for ($z = 0; $z < 16; ++$z)
            {
                $min = World::Y_MIN;
                $max = World::Y_MAX;

                for ($y = $min; $y < $max; ++$y)  {
                    $worldX = $chunkX * 16 + $x;
                    $worldZ = $chunkZ * 16 + $z;

                    if (!is_null($chunk = $world->getChunk($chunkX, $chunkZ))) {
                        $chunk->setBiomeId($worldX, $y, $worldZ, BiomeIds::ICE_PLAINS);
                    }
                }
            }
        }
    }

    public function onJoin(PlayerJoinEvent $event): void
    {
        $this->handleSetRaining($event->getPlayer());
    }

    public function onWorldChange(EntityTeleportEvent $event): void
    {
        $entity = $event->getEntity();
        if (!($entity instanceof Player)) {
            return;
        }

        $from = $event->getFrom();
        $to = $event->getTo();

        if ($from->getWorld()->getFolderName() === $to->getWorld()->getFolderName()) {
            return;
        }

        $this->handleSetRaining($entity);
    }

    private function handleSetRaining(Player $player): void
    {
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player): void {
            if (!$player->isOnline()) {
                return;
            }

            if (!in_array($player->getWorld()->getFolderName(), $this->worlds)) {
                $player->getNetworkSession()->sendDataPacket(LevelEventPacket::create(
                    LevelEvent::STOP_RAIN,
                    6000000,
                    $player->getPosition()
                ));

                return;
            }

            $player->getNetworkSession()->sendDataPacket(LevelEventPacket::create(
                LevelEvent::START_RAIN,
                6000000,
                $player->getPosition()
            ));
        }), 10);
    }

    protected function onDisable(): void
    {
        $this->getLogger()->info(TextFormat::RED . 'Snow plugin has been disabled!');
    }
}