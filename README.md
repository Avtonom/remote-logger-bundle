Sending and storage of log files on a remote server abstract
==================================================

Page bundle: https://github.com/Avtonom/remote-logger-bundle

#### To Install

Run the following in your project root, assuming you have composer set up for your project

```sh

composer.phar require avtonom/remote-logger-bundle ~1.1

```

Switching `~1.1` for the most recent tag.

Add the bundle to app/AppKernel.php

``` php

$bundles(
    ...
            new Avtonom\RemoteLoggerBundle\AvtonomRemoteLoggerBundle(),
    ...

```

Configuration configs (config_(dev|prod).yml):

``` yaml

monolog:
    handlers:
        avtonom_remote_logger:
            type:                service
            id:                  avtonom_remote_logger.monolog.handler
            channels: ~
            level: debug

```

Functional buffering in development. There unstable work on the server side:

``` yaml
monolog:
    handlers:
        avtonom_remote_logger_buffered:
            type: buffer
            handler: avtonom_remote_logger
            channels: ~
            level: debug
            buffer_size: 10 # strongly depends on the time "%avtonom_remote_logger.writing_timeout%"
            
```

Configuration options (parameters.yaml):

``` yaml

parameters:
    avtonom_remote_logger.token: ~ # 123
    avtonom_remote_logger.remote_host: ~ # server.com
    avtonom_remote_logger.use_ssl: ~ # [OPTIONAL] (def: true)
    avtonom_remote_logger.level: ~ # [OPTIONAL] (def: 100)
    avtonom_remote_logger.bubble: ~ # [OPTIONAL] (def: true)
    avtonom_remote_logger.service: ~ # [OPTIONAL] mobile, my_site.com (def: host)
    avtonom_remote_logger.appname: ~ # [OPTIONAL] cron | js | web | ... (def: web)
    avtonom_remote_logger.environment: ~ # [OPTIONAL] dev | prod | ...
    avtonom_remote_logger.writing_timeout: 10 # [OPTIONAL] (def: 10) Write timed-out, data sent for * seconds

```
