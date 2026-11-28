<?php
$requests = tempnam(sys_get_temp_dir(), 'discord-requests-');
$socket = stream_socket_server('tcp://127.0.0.1:0');
$address = stream_socket_get_name($socket, false);
fclose($socket);
$command = [PHP_BINARY, '-d', 'auto_prepend_file=' . __DIR__ . '/transport.php', '-S', $address, '-t', __DIR__ . '/../src', __DIR__ . '/../src/main.php'];
$cases = [
    ['success', 'POST', '/123', 'application/json; charset=utf-8', '{"content":"hello","embed":{"title":"test"}}', 'test-token', '200', 200, ['content' => 'hello', 'embeds' => [['title' => 'test']]]],
    ['embed only', 'POST', '/', 'application/json', '{"embed":{"title":"test"}}', 'test-token', '200', 200, ['content' => null, 'embeds' => [['title' => 'test']]]],
    ['upstream error', 'POST', '/', 'application/json', '{"content":"hello"}', 'test-token', '429', 429, ['content' => 'hello', 'embeds' => []]],
    ['transport failure', 'POST', '/', 'application/json', '{"content":"hello"}', 'test-token', 'failure', 502, ['content' => 'hello', 'embeds' => []]],
    ['method', 'GET', '/', 'application/json', '{"content":"hello"}', 'test-token', '200', 405, null],
    ['content type', 'POST', '/', 'text/plain', '{"content":"hello"}', 'test-token', '200', 415, null],
    ['missing token', 'POST', '/', 'application/json', '{"content":"hello"}', '', '200', 500, null],
    ['empty body', 'POST', '/', 'application/json', '', 'test-token', '200', 400, null],
    ['invalid json', 'POST', '/', 'application/json', '{', 'test-token', '200', 400, null],
    ['array body', 'POST', '/', 'application/json', '[]', 'test-token', '200', 400, null],
    ['invalid content', 'POST', '/', 'application/json', '{"content":12}', 'test-token', '200', 400, null],
    ['invalid embed', 'POST', '/', 'application/json', '{"embed":[]}', 'test-token', '200', 400, null],
    ['empty message', 'POST', '/', 'application/json', '{"content":" ","embed":{}}', 'test-token', '200', 400, null],
];
$failed = false;
try {
    foreach ($cases as [$name, $method, $path, $type, $body, $token, $upstream, $expectedStatus, $expectedPayload]) {
        file_put_contents($requests, '');
        $env = array_merge(getenv(), ['DISCORD_TOKEN' => $token, 'DISCORD_CHANNEL_ID' => '42', 'TEST_REQUESTS' => $requests, 'TEST_UPSTREAM' => $upstream]);
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'a'], 2 => ['file', '/dev/null', 'a']], $pipes, null, $env);
        if (!is_resource($process)) throw new RuntimeException('Could not start test server');
        fclose($pipes[0]);
        $client = false;
        for ($attempt = 0; $attempt < 100 && !$client; $attempt++) {
            $client = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
            if (!$client) usleep(10000);
        }
        if (!$client) {
            proc_terminate($process);
            proc_close($process);
            throw new RuntimeException('Test server did not start');
        }
        fwrite($client, "$method $path HTTP/1.1\r\nHost: localhost\r\nContent-Type: $type\r\nContent-Length: " . strlen($body) . "\r\nConnection: close\r\n\r\n$body");
        $response = stream_get_contents($client);
        fclose($client);
        proc_terminate($process);
        proc_close($process);
        preg_match('/^HTTP\/\S+ (\d{3})/', $response, $matches);
        $status = (int) ($matches[1] ?? 0);
        $responseBody = json_decode(explode("\r\n\r\n", $response, 2)[1] ?? '', true);
        $request = json_decode(file_get_contents($requests), true);
        $expectedChannel = $path === '/123' ? '123' : '42';
        $valid = $status === $expectedStatus && is_array($responseBody) && ($responseBody['status'] ?? null) === ($expectedStatus === 200);
        if ($expectedPayload === null) {
            $valid = $valid && $request === null;
        } else {
            $http = $request['options']['http'] ?? [];
            $valid = $valid && ($request['url'] ?? '') === 'https://discord.com/api/channels/' . $expectedChannel . '/messages'
                && ($http['method'] ?? '') === 'POST' && json_decode($http['content'] ?? '', true) === $expectedPayload
                && str_contains($http['header'] ?? '', 'Authorization: Bot test-token');
            if ($upstream !== 'failure') $valid = $valid && ($responseBody['response'] ?? null) === ['id' => 'test-message'];
        }
        if (!$valid) {
            fwrite(STDERR, "Failed: $name (HTTP $status, expected $expectedStatus)\n");
            $failed = true;
        }
    }
} finally {
    unlink($requests);
}
if ($failed) exit(1);
echo "Request behavior cases passed\n";
