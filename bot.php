<?php

$BOT_TOKEN = getenv("8570211299:AAGOX6MhtDOGvTheQkI-T62NLxr7vos8rno");
$ADMIN_ID  = getenv("7884538719");

$update = file_get_contents("php://input");
if(!$update){
    http_response_code(200);
    exit;
}

file_put_contents("log.txt", $update.PHP_EOL, FILE_APPEND);

$data = json_decode($update, true);

if(!isset($data["message"])) exit;

$chat_id = $data["message"]["chat"]["id"];
$text    = trim($data["message"]["text"] ?? "");

$dataFile = "data.json";
if(!file_exists($dataFile)){
    file_put_contents($dataFile, json_encode(new stdClass()));
}
$db = json_decode(file_get_contents($dataFile), true);

/* SEND MESSAGE */
function sendMessage($chat_id, $text){
    global $BOT_TOKEN;
    file_get_contents(
        "https://api.telegram.org/bot{$BOT_TOKEN}/sendMessage?chat_id={$chat_id}&text=".urlencode($text)
    );
}

/* START */
if($text === "/start"){
    sendMessage($chat_id, "👋 Welcome!\nSend /request to get VIP access.");
}

/* REQUEST */
elseif($text === "/request"){
    if(isset($db[$chat_id])){
        sendMessage($chat_id, "✅ You already approved\nPassword: ".$db[$chat_id]);
    } else {
        sendMessage($chat_id, "⏳ Request sent to admin");
        sendMessage($ADMIN_ID, "🔔 New VIP Request\nUser ID: $chat_id\nApprove: /approve $chat_id");
    }
}

/* APPROVE */
elseif(strpos($text, "/approve") === 0 && $chat_id == $ADMIN_ID){
    $parts = explode(" ", $text);
    $uid = $parts[1] ?? null;

    if($uid){
        $pass = substr(bin2hex(random_bytes(4)),0,8);
        $db[$uid] = $pass;
        file_put_contents($dataFile, json_encode($db, JSON_PRETTY_PRINT));

        sendMessage($uid, "✅ Approved!\nPassword: $pass");
        sendMessage($ADMIN_ID, "✔ User Approved");
    }
}

/* REVOKE */
elseif(strpos($text, "/revoke") === 0 && $chat_id == $ADMIN_ID){
    $parts = explode(" ", $text);
    $uid = $parts[1] ?? null;

    if(isset($db[$uid])){
        unset($db[$uid]);
        file_put_contents($dataFile, json_encode($db, JSON_PRETTY_PRINT));
        sendMessage($ADMIN_ID, "❌ Access revoked");
        sendMessage($uid, "❌ Your VIP access revoked");
    }
}

http_response_code(200);
echo "OK";
