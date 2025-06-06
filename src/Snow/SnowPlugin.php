<?php

namespace Snow;

use pocketmine\data\bedrock\BiomeIds;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\event\world\ChunkPopulateEvent;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;

//This project was made with the help of https://github.com/PetteriM1/Snow/blob/master/src/main/java/suomicraftpe/events/christmas/Main.java
class SnowPlugin extends PluginBase implements Listener
{
    /**
     * @var string[]
     */
    private array $worlds;

    private int $particlesAmount;

    protected function onEnable(): void
    {
        $this->saveDefaultConfig();

        /** @var string[] $worlds */
        $worlds = (array) $this->getConfig()->get('worlds', []);
        $this->worlds = $worlds;

        /** @var int $particlesAmount */
        $particlesAmount = $this->getConfig()->get('particlesAmount', 2500);
        $this->particlesAmount = $particlesAmount;

        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        $this->getLogger()->info(TextFormat::GREEN . 'Snow plugin has been enabled!');
    }

    public function onChunkLoaded(ChunkLoadEvent $event): void
    {
        if (!in_array($event->getWorld()->getFolderName(), $this->worlds)) {
            return;
        }

        $chunkX = $event->getChunkX();
        $chunkZ = $event->getChunkZ();

        for ($x = 0; $x < Chunk::EDGE_LENGTH; ++$x) {
            for ($z = 0; $z < Chunk::EDGE_LENGTH; ++$z) {
                for ($y = World::Y_MIN; $y < World::Y_MAX; ++$y)  {
                    if (!is_null($chunk = $event->getWorld()->getChunk($chunkX, $chunkZ))) {
                        $chunk->setBiomeId($x, $y, $z, BiomeIds::ICE_PLAINS);
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
                    0,
                    $player->getPosition()
                ));

                return;
            }

            $player->getNetworkSession()->sendDataPacket(LevelEventPacket::create(
                LevelEvent::START_RAIN,
                $this->particlesAmount,
                $player->getPosition()
            ));
        }), 10);
    }

    protected function onDisable(): void
    {
        $this->getLogger()->info(TextFormat::RED . 'Snow plugin has been disabled!');
    }
}