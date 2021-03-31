# DockerMine
A PocketMine-MP Plugin that manages Docker on PocketMine server.

# How it works?
It uses `proc_open()` to dispatch `docker` command.

for now, it runs on main thread so it can freeze server when the starting docker, stopping docker etc.

# Commands
The default command of this plugin is `/docker`.

|args|description|
|---|---|
|start|Starts new docker container|
|stop|Stops docker container, container must created by this plugin|