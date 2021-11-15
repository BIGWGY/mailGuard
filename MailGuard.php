<?php
require_once __DIR__ . "/vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

$data_file_path = __DIR__ . "/yifan.data";

$debug = true;

$content = json_decode(@file_get_contents($data_file_path), true);

if (empty($content['last_check_time'])) {
    $last_check_time = time();
} else {
    $last_check_time = $content['last_check_time'];
}

$db = new \PDO(
    'mysql:host=localhost;dbname=yifan',
    'root',
    '123456'
);

$statement = $db->query("select id, uid from yifan_order where pay_status = 2 and create_time >= {$last_check_time}");
$statement->setFetchMode(PDO::FETCH_ASSOC);
$statement->execute();
$row_of_order_ids = $statement->fetchAll();

$statement = $db->query("select id, uid from yifan_send where ck_status = 2 and create_time >= {$last_check_time}");
$statement->setFetchMode(PDO::FETCH_ASSOC);
$statement->execute();
$row_of_send_order_ids = $statement->fetchAll();

$content['last_check_time'] = time();

file_put_contents($data_file_path, json_encode($content));

$mail = new PHPMailer(true);
$mail->SMTPDebug = SMTP::DEBUG_CLIENT;                      // Enable verbose debug output
$mail->isSMTP();                                            // Send using SMTP
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
$mail->SMTPAuth = true;                                   // Enable SMTP authentication
$mail->Host = 'smtp.exmail.qq.com';                         // Set the SMTP server to send through
$mail->Port = 465;                                   // TCP port to connect to  ， 465
$mail->CharSet = "UTF-8";
$mail->FromName = "lvpeilin";
$mail->Username = 'lvpeilin@dyspace.net';                     // SMTP username
$mail->Password = '****************';                     // SMTP password, 授权码
$mail->setFrom('lvpeilin@dyspace.net', '深圳多游空间');
$mail->isHTML(true);                                  // Set email format to HTML

if ($debug) {
    $statement = $db->query("select id, uid from yifan_send where ck_status = 2");
    $statement->setFetchMode(PDO::FETCH_ASSOC);
    $statement->execute();
    $row_of_send_order_ids = $statement->fetchAll();

    $mail->isHTML(true);
    $mail->addAddress('lvpeilin@dyspace.net', 'lvpeilin');     // Add a recipient
    $mail->addAddress('422615924@qq.com', 'lvpeilin');     // Add a recipient
    $mail->Subject = '一番赏';
    $mail->Body = "一番赏测试邮件，收到请忽略!<br> 玩家uid: " . json_encode(array_column($row_of_send_order_ids, 'uid'));;
    try {
        return $mail->send();
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return;
    }
} else {  
     $mail->addAddress('lvpeilin@dyspace.net', 'lvpeilin');     // Add a recipient
}

if (empty($row_of_order_ids) && empty($row_of_send_order_ids)) {
    return;
}

try {
    if (!empty($row_of_order_ids)) {
        $mail->Subject = '一番赏';
        $mail->Body = "一番赏有新的已支付订单 <br> 玩家uid : " . json_encode(array_column($row_of_order_ids, 'uid'));
        $mail->send();
    }

    if (!empty($row_of_send_order_ids)) {
        $mail->Subject = '一番赏';
        $mail->Body = '一番赏有新的申请发货订单 <br> 玩家uid: ' . json_encode(array_column($row_of_send_order_ids, 'uid'));
        $mail->send();
    }
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
