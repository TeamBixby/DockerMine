<?php

declare(strict_types=1);

namespace alvin0319\DockerMine\command;

use alvin0319\DockerMine\DockerMine;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\plugin\Plugin;
use Throwable;
use function array_shift;
use function count;
use function explode;
use function mb_strpos;

final class DockerCommand extends Command implements PluginIdentifiableCommand{

	protected DockerMine $plugin;

	public function __construct(){
		parent::__construct("docker", "DockerMine command", "/docker <start|stop|execute>");
		$this->setPermission("dockermine.command");
		$this->plugin = DockerMine::getInstance();
	}

	/** @return DockerMine */
	public function getPlugin() : Plugin{
		return $this->plugin;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->testPermission($sender)){
			return false;
		}
		if(count($args) < 1){
			throw new InvalidCommandSyntaxException();
		}
		switch(array_shift($args)){
			/*
			case "start":
				if(count($args) < 2){
					$sender->sendMessage("Usage: /docker start <container name> <docker image> <port> <protocol> <volumes...>");
					return false;
				}
				$name = array_shift($args);
				$image = array_shift($args);
				$port = array_shift($args);
				$protocol = array_shift($args);
				$volumes = [];
				foreach($args as $volumeArgs){
					if(mb_strpos($volumeArgs, ":") === false){
						$sender->sendMessage("Volumes must be separated by \":\".");
						return false;
					}
					[$volumeName, $data] = explode(":", $volumeArgs);
					$volumes[] = [$volumeName, $data];
				}
				try{
					$container = DockerMine::getInstance()->createDocker($name, $image, $protocol, (int) $port, $volumes);
				}catch(Throwable $e){
					$sender->sendMessage("Failed to start docker: " . $e->getMessage());
					$sender->getServer()->getLogger()->logException($e);
					return false;
				}
				$sender->sendMessage("Container name: {$container->getName()}");
				break;
			*/
			case "start":
				if(count($args) < 3){
					$sender->sendMessage("Usage: /docker start <name> <type> <port>");
					return false;
				}
				$name = array_shift($args);
				$serverType = array_shift($args);
				$port = array_shift($args);
				try{
					switch($serverType){
						case "pmmp":
							$container = DockerMine::getInstance()->startPocketMine($name, (int) $port);
							$sender->sendMessage("Starting PocketMine docker: {$container->getName()}");
							break;
						case "bdsx":
							// todo
						default:
							$sender->sendMessage("Available types: pmmp");
					}
				}catch(Throwable $e){
					$sender->sendMessage("Failed to run docker: " . $e->getMessage());
				}
				break;
			case "stop":
				if(count($args) < 1){
					$sender->sendMessage("Usage: /docker stop <container name>");
					return false;
				}
				$name = array_shift($args);

				$container = DockerMine::getInstance()->getDockerByName($name);
				if($container === null){
					$sender->sendMessage("That docker doesn't exist or already stopped.");
					return false;
				}


				try{
					DockerMine::getInstance()->stopDocker($container);
				}catch(Throwable $e){
					$sender->getServer()->getLogger()->logException($e);
				}
				$sender->sendMessage("Docker stop success.");
				break;
			/*
		case "execute":
			if(count($args) < 2){
				$sender->sendMessage("Usage: /docker execute <container name> <command...>");
				return false;
			}
			$name = array_shift($args);
			$command = array_shift($args);

			$container = DockerMine::getInstance()->getDockerByName($name);
			if($container === null){
				$sender->sendMessage("That docker doesn't exist or already stopped.");
				return false;
			}

			$container->execute('');
			*/
			default:
				throw new InvalidCommandSyntaxException();
		}
		return true;
	}
}