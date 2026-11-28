<?php
class TestTransport implements ArrayAccess {
    public $context;
    private $position = 0;
    private $body;

    public function stream_open($path, $mode, $options, &$openedPath) {
        file_put_contents(getenv('TEST_REQUESTS'), json_encode(['url' => $path, 'options' => stream_context_get_options($this->context)]));
        $this->body = '{"id":"test-message"}';
        return getenv('TEST_UPSTREAM') !== 'failure';
    }
    public function stream_read($count) {
        $part = substr($this->body, $this->position, $count);
        $this->position += strlen($part);
        return $part;
    }
    public function stream_eof() { return $this->position >= strlen($this->body); }
    public function stream_stat() { return []; }
    public function offsetExists($offset): bool { return $offset === 0; }
    public function offsetGet($offset): mixed { return 'HTTP/1.1 ' . getenv('TEST_UPSTREAM') . ' Test'; }
    public function offsetSet($offset, $value): void {}
    public function offsetUnset($offset): void {}
}
stream_wrapper_unregister('https');
stream_wrapper_register('https', TestTransport::class);
