<?php
// USDT-TRC20 支付页面

if(!defined('IN_PLUGIN'))exit();

$to = isset($trc20_to) ? $trc20_to : '';
$amount = isset($trc20_amount) ? $trc20_amount : '';
$uri = 'tron:' . $to . '?amount=' . $amount;
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Content-Language" content="zh-cn">
    <meta name="renderer" content="webkit">
    <title>USDT-TRC20 支付</title>
    <link href="/assets/css/wechat_pay.css?v=2" rel="stylesheet" media="screen">
</head>
<body>
<div class="body">
    <h1 class="mod-title">
        <span class="ico-wechat"></span><span class="text">USDT-TRC20 支付</span>
    </h1>
    <div class="mod-ct">
        <div class="amount">USDT <?php echo htmlspecialchars($amount) ?></div>
        <div style="text-align:center;margin:10px 0;color:#666;">
            请使用支持 TRC20 的钱包转账到以下地址
        </div>
        <div style="text-align:center;font-size:14px;word-break:break-all;padding:8px 12px;border:1px solid #eee;border-radius:6px;margin:10px 20px;background:#fafafa;">
            <b><?php echo htmlspecialchars($to) ?></b>
        </div>
        <div class="qr-image" id="qrcode"></div>

        <div class="mobile-btn" style="margin-top: 15px;">
            <a class="btn-copy-link" id="copy-addr" data-clipboard-text="<?php echo htmlspecialchars($to) ?>">复制收款地址</a>
            <a class="btn-copy-link" id="copy-amt" data-clipboard-text="<?php echo htmlspecialchars($amount) ?>" style="margin-top:10px;">复制USDT金额</a>
            <a class="btn-copy-link" href="<?php echo htmlspecialchars($uri) ?>" rel="noreferrer" style="margin-top:10px;">打开钱包(如支持)</a>
        </div>

        <div class="tip" style="margin-top:20px;">
            <span class="dec dec-left"></span>
            <span class="dec dec-right"></span>
            <div class="ico-scan"></div>
            <div class="tip-text">
                <p>转账完成后将自动检测到账</p>
                <p>请确保金额精确到 6 位小数</p>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.min.js"></script>
<script src="<?php echo $cdnpublic?>jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
<script src="<?php echo $cdnpublic?>clipboard.js/1.7.1/clipboard.min.js"></script>
<script>
    var pay_uri = <?php echo json_encode($uri); ?>;
    $('#qrcode').qrcode({
        text: pay_uri,
        width: 230,
        height: 230,
        foreground: "#000000",
        background: "#ffffff",
        typeNumber: -1
    });

    var clipboardAddr = new Clipboard('#copy-addr');
    clipboardAddr.on('success', function() { layer.msg('地址复制成功'); });
    clipboardAddr.on('error', function() { layer.msg('复制失败'); });

    var clipboardAmt = new Clipboard('#copy-amt');
    clipboardAmt.on('success', function() { layer.msg('金额复制成功'); });
    clipboardAmt.on('error', function() { layer.msg('复制失败'); });

    function poll() {
        $.ajax({
            type: "GET",
            dataType: "text",
            url: "/pay/notify/<?php echo $order['trade_no']?>/",
            complete: function () {
                setTimeout(poll, 5000);
            }
        });
        $.ajax({
            type: "GET",
            dataType: "json",
            url: "/getshop.php",
            data: {trade_no: "<?php echo $order['trade_no']?>"},
            success: function (data) {
                if (data.code == 1) {
                    layer.msg('支付成功，正在跳转...', {icon: 16,shade: 0.1,time: 15000});
                    window.location.href=data.backurl;
                }
            }
        });
    }
    window.onload = function(){
        setTimeout(poll, 2000);
    }
</script>
</body>
</html>
