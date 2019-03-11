<?php
$server = new \swoole_websocket_server('172.18.134.124', 7100);
$server->on('request', function (\swoole_http_request $request,\swoole_http_response $response) {
    $response->end("<h1>Hello Swoole Websocket Server. #" . random_int(1000, 9999) . "</h1>");
});
$server->on('message', function (\swoole_websocket_server $server,\swoole_websocket_frame $frame) {
    $server->push($frame->fd, "this is websocket server");
});
$server->on('close', function (\swoole_server $server,int $fd,int $reactorId) {
});

$server->start();