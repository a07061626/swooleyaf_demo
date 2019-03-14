<?php
$server = new \swoole_websocket_server('172.18.134.124', 9501);
$server->on('message', function (\swoole_websocket_server $server, $frame) {
    $server->push($frame->fd, "this is server");
});
$server->on('request', function ($request, $response) {
    $response->end("<h1>Hello Websocket Swoole. #" . random_int(1000, 9999) . "</h1>");
});
$server->on('close', function ($ser, $fd) {
    echo "client {$fd} closed\n";
});
$server->start();