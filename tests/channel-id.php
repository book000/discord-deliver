<?php
$mock = tempnam(sys_get_temp_dir(), 'discord-mock-');
$requests = tempnam(sys_get_temp_dir(), 'discord-requests-');
file_put_contents($mock, <<<'MOCK'
<?php
class DiscordMock {
    public $context;
    public function stream_open($path, $mode, $options, &$openedPath) {
        file_put_contents(getenv('TEST_REQUESTS'), $path);
        return false;
    }
}
stream_wrapper_unregister('https');
stream_wrapper_register('https', DiscordMock::class);
MOCK);
$socket = stream_socket_server('tcp://127.0.0.1:0');
$address = stream_socket_get_name($socket, false);
fclose($socket);
$command = [PHP_BINARY, '-d', 'auto_prepend_file=' . $mock, '-S', $address, '-t', __DIR__ . '/../src', __DIR__ . '/../src/main.php'];
$cases = [
    ['/', '42', 502, '42'],
    ['/123', '42', 502, '123'],
    ['/0', '42', 502, '0'],
    ['/123?x=1', '42', 502, '42'],
    ['/123?x=1', '', 404, null],
    ['/1e3', '42', 404, null],
    ['/1e3', '', 404, null],
    ['/%2012', '42', 404, null],
    ['/abc', '42', 404, null],
    ['/123/456', '42', 404, null],
    ['/', '', 404, null],
    ['/', '1e3', 404, null],
];
$failed = false;
try {
    foreach ($cases as [$path, $default, $expectedStatus, $expectedChannel]) {
        file_put_contents($requests, '');
        $env = array_merge(getenv(), ['DISCORD_TOKEN' => 'test-token', 'DISCORD_CHANNEL_ID' => $default, 'TEST_REQUESTS' => $requests]);
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'a'], 2 => ['file', '/dev/null', 'a']], $pipes, null, $env);
        if (!is_resource($process)) {
            throw new RuntimeException('Could not start test server');
        }
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
        fwrite($client, "POST $path HTTP/1.1\r\nHost: localhost\r\nContent-Type: application/json\r\nContent-Length: 16\r\nConnection: close\r\n\r\n{\"content\":\"hi\"}");
        $response = stream_get_contents($client);
        fclose($client);
        proc_terminate($process);
        proc_close($process);
        preg_match('/^HTTP\/\S+ (\d{3})/', $response, $matches);
        $actualStatus = (int) ($matches[1] ?? 0);
        $actualRequest = file_get_contents($requests);
        $expectedRequest = $expectedChannel === null ? '' : 'https://discord.com/api/channels/' . $expectedChannel . '/messages';
        if ($actualStatus !== $expectedStatus || $actualRequest !== $expectedRequest) {
            fwrite(STDERR, "Failed: path=$path default=$default status=$actualStatus expected=$expectedStatus request=$actualRequest\n");
            $failed = true;
        }
    }
} finally {
    unlink($mock);
    unlink($requests);
}
if ($failed) exit(1);
echo "Channel ID regression cases passed\n";
