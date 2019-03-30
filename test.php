<?php
require_once __DIR__ . '/helper_load.php';

//$smsSend = new \SySms\DaYu\SmsSend();
//$smsSend->setSignName('');
//$sendRes = \SySms\SmsUtilDaYu::sendServiceRequest($smsSend);

$smsSend = new \SySms\Yun253\SmsSend();
$smsSend->setPhoneList([]);
$sendRes = \SySms\SmsUtilYun253::sendServiceRequest($smsSend);