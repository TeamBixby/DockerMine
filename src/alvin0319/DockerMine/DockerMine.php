<?php

declare(strict_types=1);

namespace alvin0319\DockerMine;

use alvin0319\DockerMine\command\DockerCommand;
use Closure;
use InvalidArgumentException;
use libDocker\ComposerDecoy;
use libDocker\containers\DockerContainer;
use libDocker\containers\DockerContainerInstance;
use libDocker\containers\Exceptions\CouldNotStartDockerContainer;
use pocketmine\level\Level;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Utils;
use function base64_encode;
use function class_exists;
use function mkdir;
use function random_bytes;
use function substr;

final class DockerMine extends PluginBase{
	use SingletonTrait;

	/** @var DockerContainerInstance[] */
	protected $instances = [];

	public function onLoad() : void{ self::setInstance($this); }

	public function onEnable() : void{
		if(!class_exists(ComposerDecoy::class)){
			throw new AssumptionFailedError("Failed to find virion libDockerHandler, download it from \"https://github.com/alvin0319/libDockerHandler\".");
		}

		$this->getServer()->getCommandMap()->register("dockermine", new DockerCommand());
	}

	public function createDocker(string $dockerName, string $dockerImage, string $protocol, int $port, array $dataVolumes = [], ?Closure $addEnvironmentFunc = null) : DockerContainerInstance{
		if(isset($this->instances[$dockerName])){
			throw new InvalidArgumentException("Docker name with {$dockerName} is already running");
		}
		$container = DockerContainer::create($dockerImage);

		$container->daemonize(true)
			->name($dockerName)
			->mapPort($port, $port, $protocol)
			->stopOnDestruct(true);

		if($addEnvironmentFunc !== null){
			Utils::validateCallableSignature(function(DockerContainer $container) : void{ }, $addEnvironmentFunc);
			($addEnvironmentFunc)($container);
		}

		foreach($dataVolumes as $volume){
			$container->setVolume($volume[0], $volume[1]);
		}

		try{
			$instance = $container->start();
		}catch(CouldNotStartDockerContainer $e){
			throw new AssumptionFailedError("Docker start must not failed", 0, $e);
		}

		return $this->instances[$dockerName] = $instance;
	}

	public function startPocketMine(string $name, int $port) : DockerContainerInstance{
		$dataFolder = $this->getDataFolder() . "data/{$name}/{$port}";
		$pluginsFolder = $this->getDataFolder() . "data/{$name}/{$port}/plugins";
		@mkdir($dataFolder, 0777, true);
		@mkdir($pluginsFolder, 0777, true);
		new Config($dataFolder . "/server.properties", Config::PROPERTIES, [
			"motd" => \pocketmine\NAME . " Server",
			"server-port" => $port,
			"white-list" => false,
			"announce-player-achievements" => true,
			"spawn-protection" => 16,
			"max-players" => 20,
			"gamemode" => 0,
			"force-gamemode" => false,
			"hardcore" => false,
			"pvp" => true,
			"difficulty" => Level::DIFFICULTY_NORMAL,
			"generator-settings" => "",
			"level-name" => "world",
			"level-seed" => "",
			"level-type" => "DEFAULT",
			"enable-query" => true,
			"enable-rcon" => false,
			"rcon.password" => substr(base64_encode(random_bytes(20)), 3, 10),
			"auto-save" => true,
			"view-distance" => 8,
			"xbox-auth" => true,
			"language" => "eng"
		]);
		return $this->createDocker($name, "pmmp/pocketmine-mp", "udp", $port, [
			[$dataFolder, "/data"],
			[$pluginsFolder, "/plugins"]
		]);
	}

	public function startBDSX(string $name, int $port, bool $allowCheats = false, string $levelName = "Bedrock level", int $maxPlayers = 20) : DockerContainerInstance{
		$dataFolder = $this->getDataFolder() . "data/{$name}/{$port}";
		@mkdir($dataFolder, 0777, true);
		return $this->createDocker($name, "jasonwynn10/bdsx-container", "udp", $port, [
			[$dataFolder, "/data"]
		], function(DockerContainer $container) use ($name, $port, $maxPlayers, $levelName, $allowCheats) : void{
			foreach([
				["EULA", "TRUE"],
				["SERVER_NAME", $name],
				["SERVER_PORT", (string) $port],
				["MAX_PLAYERS", (string) $maxPlayers],
				["LEVEL_NAME", (string) $levelName],
				["ALLOW_CHEATS", $allowCheats ? "TRUE" : "FALSE"]
			] as $setting){
				$container->setEnvironmentVariable($setting[0], $setting[1]);
			}
		});
	}

	public function stopDocker(DockerContainerInstance $instance) : void{
		$instance->stop();
		unset($this->instances[$instance->getName()]);
	}

	public function getDockerByName(string $name) : ?DockerContainerInstance{
		return $this->instances[$name] ?? null;
	}

	public function onDisable() : void{
		foreach($this->instances as $name => $instance){
			$this->stopDocker($instance);
		}
	}
}