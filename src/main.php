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
if (strtolower($_SERVER["CONTENT_TYPE"]) !== "application/json") {
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
$isChannelIdIncludeUrl = !empty($requestUrl) && strpos($requestUrl, '/') === false && is_numeric($requestUrl);
if (!$isChannelIdIncludeUrl && !$ENV_DISCORD_CHANNEL_ID) {
    http_response_code(404);
    exit(json_encode([
      "status" => false,
      "message" => "Invalid request"
    ]));
}
$DISCORD_CHANNEL_ID = $isChannelIdIncludeUrl ? $requestUrl : $ENV_DISCORD_CHANNEL_ID;

// Get message
$input = file_get_contents("php://input");
if (empty($input)) {
    http_response_code(400);
    exit(json_encode([
      "status" => false,
      "message" => "No body provided"
    ]));
}
$json = json_decode($input, true);
$content = isset($json['content']) ? $json['content'] : null;
$embed = isset($json['embed']) ? $json['embed'] : null;

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

$context = [
  "http" => [
    "method" => "POST",
    "header" => implode("\r\n", $header),
    "content" => json_encode($data),
    "ignore_errors" => true
  ]
];
$context = stream_context_create($context);
$contents = file_get_contents("https://discord.com/api/channels/" . $DISCORD_CHANNEL_ID . "/messages", false, $context);
preg_match('/HTTP\/1\.[0|1|x] ([0-9]{3})/', $http_response_header[0], $matches);
$status_code = $matches[1];
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
