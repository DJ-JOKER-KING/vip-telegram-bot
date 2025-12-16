<?php
$BOT_TOKEN = getenv("BOT_TOKEN");
$ADMIN_ID  = getenv("ADMIN_ID");

$update = json_decode(file_get_contents("php://input"), true);
if(!$update) exit;

$chat_id = $update["message"]["chat"]["id"] ?? null;
$text    = trim($update["message"]["text"] ?? "");

$dataFile = __DIR__ . "/data.json";
if(!file_exists($dataFile)){
    file_put_contents($dataFile, json_encode(new stdClass()));
}
$data = json_decode(file_get_contents($dataFile), true);

/* SEND MESSAGE FUNCTION */
function sendMessage($chat_id, $text){
    global $BOT_TOKEN;
    file_get_contents(
        "https://api.telegram.org/bot{$BOT_TOKEN}/sendMessage?chat_id={$chat_id}&text=" . urlencode($text)
    );
}

if(!$chat_id) exit;

/* START */
if($text === "/start"){
    sendMessage($chat_id, "👋 Welcome!\nSend /request to get VIP access.");
}

/* REQUEST */
elseif($text === "/request"){
    if(isset($data[$chat_id])){
        sendMessage($chat_id, "✅ You already have access.\nPassword: ".$data[$chat_id]);
    } else {
        sendMessage($chat_id, "⏳ Request sent to admin.");
        sendMessage($ADMIN_ID, "🔔 New Request\nUser ID: $chat_id\nApprove: /approve $chat_id");
    }
}

/* APPROVE */
elseif(strpos($text, "/approve") === 0 && $chat_id == $ADMIN_ID){
    $parts = explode(" ", $text);
    $user_id = $parts[1] ?? "";

    if($user_id){
        $password = substr(md5(uniqid()), 0, 8);
        $data[$user_id] = $password;
        file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));

        sendMessage($user_id, "✅ Approved!\nYour Password: $password");
        sendMessage($ADMIN_ID, "✔ User Approved.");
    }
}

/* REVOKE */
elseif(strpos($text, "/revoke") === 0 && $chat_id == $ADMIN_ID){
    $parts = explode(" ", $text);
    $user_id = $parts[1] ?? "";

    if(isset($data[$user_id])){
        unset($data[$user_id]);
        file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
        sendMessage($ADMIN_ID, "❌ Access revoked.");
        sendMessage($user_id, "❌ Your access revoked by admin.");
    }
}
