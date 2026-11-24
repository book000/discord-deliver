<?php
// POST only
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit(json_encode([
      "status" => false,
      "message" => "Only POST requests are allowed"
    ]));
}

// Only application/json
if (strtolower(trim(explode(";", $_SERVER["CONTENT_TYPE"] ?? "", 2)[0])) !== "application/json") {
    http_response_code(415);
    exit(json_encode([
      "status" => false,
      "message" => "Only application/json content type is allowed"
    ]));
}

// Discord Token
$DISCORD_TOKEN = getenv("DISCORD_TOKEN");
if (!$DISCORD_TOKEN) {
    http_response_code(500);
    exit(json_encode([
      "status" => false,
      "message" => "Please set the DISCORD_TOKEN environment variable"
    ]));
}

// Discord Channel Id
$requestUrl = substr($_SERVER['REQUEST_URI'], 1);
$ENV_DISCORD_CHANNEL_ID = getenv("DISCORD_CHANNEL_ID");
$requestPath = explode('?', $requestUrl, 2)[0];
if ($requestPath !== '' && !preg_match('/\A[0-9]+\z/', $requestPath)) {
    http_response_code(404);
    exit(json_encode([
      "status" => false,
      "message" => "Invalid request"
    ]));
}
$isChannelIdIncludeUrl = $requestPath !== '' && strpos($requestUrl, '?') === false;
$DISCORD_CHANNEL_ID = $isChannelIdIncludeUrl ? $requestPath : $ENV_DISCORD_CHANNEL_ID;
if ($DISCORD_CHANNEL_ID === false || $DISCORD_CHANNEL_ID === '' || !preg_match('/\A[0-9]+\z/', $DISCORD_CHANNEL_ID)) {
    http_response_code(404);
    exit(json_encode([
      "status" => false,
      "message" => "Invalid request"
    ]));
}

// Get message
$input = file_get_contents("php://input");
if (empty($input)) {
    http_response_code(400);
    exit(json_encode([
      "status" => false,
      "message" => "No body provided"
    ]));
}
$json = json_decode($input);
if (json_last_error() !== JSON_ERROR_NONE || !is_object($json)) {
    http_response_code(400);
    exit(json_encode([
      "status" => false,
      "message" => "Request body must be a JSON object"
    ]));
}
if ((property_exists($json, 'content') && !is_string($json->content)) ||
    (property_exists($json, 'embed') && !is_object($json->embed))) {
    http_response_code(400);
    exit(json_encode([
      "status" => false,
      "message" => "Content must be a string and embed must be an object"
    ]));
}
$content = $json->content ?? null;
$embed = $json->embed ?? null;
if (($content === null || trim($content) === '') && ($embed === null || count((array) $embed) === 0)) {
    http_response_code(400);
    exit(json_encode([
      "status" => false,
      "message" => "A non-empty content or embed is required"
    ]));
}

$data = [
  "content" => $content,
  "embeds" => $embed ? [$embed] : []
];
$header = [
  "Content-Type: application/json",
  "Content-Length: " . strlen(json_encode($data)),
  "Authorization: Bot " . $DISCORD_TOKEN,
  "User-Agent: discord-deliver (https://github.com/book000/discord-deliver)"
];

// The timeout applies per socket wait (connect and each read), not to the whole exchange.
const UPSTREAM_TIMEOUT_SECONDS = 10;

$context = [
  "http" => [
    "method" => "POST",
    "header" => implode("\r\n", $header),
    "content" => json_encode($data),
    "ignore_errors" => true,
    "timeout" => UPSTREAM_TIMEOUT_SECONDS
  ]
];
$context = stream_context_create($context);
$startedAt = microtime(true);
$stream = @fopen("https://discord.com/api/channels/" . $DISCORD_CHANNEL_ID . "/messages", "r", false, $context);
if ($stream === false) {
    $timedOut = microtime(true) - $startedAt >= UPSTREAM_TIMEOUT_SECONDS;
    http_response_code($timedOut ? 504 : 502);
    exit(json_encode([
      "status" => false,
      "message" => $timedOut ? "Discord API request timed out" : "Failed to send message",
      "response" => null
    ]));
}
$headers = stream_get_meta_data($stream)["wrapper_data"];
$contents = stream_get_contents($stream);
$bodyTimedOut = stream_get_meta_data($stream)["timed_out"];
fclose($stream);
if ($bodyTimedOut) {
    http_response_code(504);
    exit(json_encode([
      "status" => false,
      "message" => "Discord API request timed out",
      "response" => null
    ]));
}
preg_match('/HTTP\/\S+ ([0-9]{3})/', $headers[0], $matches);
$status_code = (int) ($matches[1] ?? 502);
if ($status_code != 200) {
    http_response_code($status_code);
    exit(json_encode([
      "status" => false,
      "message" => "Failed to send message",
      "response" => json_decode($contents, true)
    ]));
}
exit(json_encode([
  "status" => true,
  "response" => json_decode($contents, true)
]));
