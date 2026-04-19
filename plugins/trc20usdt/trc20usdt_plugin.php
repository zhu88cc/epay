<?php

use Exception;

class trc20usdt_plugin
{
	static public $info = [
		'name'        => 'trc20usdt',
		'showname'    => 'USDT-TRC20(波场)',
		'author'      => 'custom',
		'link'        => '',
		'types'       => ['usdt'],
		'inputs'      => [
			'appkey' => [
				'name' => '收款TRON地址',
				'type' => 'input',
				'note' => 'TRC20收款地址(以T开头)',
			],
			'appurl' => [
				'name' => 'CNY/USDT汇率(可选)',
				'type' => 'input',
				'note' => '例如7.25；留空则自动拉取汇率',
			],
			'appid' => [
				'name' => 'TronGrid API Key(可选)',
				'type' => 'input',
				'note' => '没有也能用，但可能被限流',
			],
			'appsecret' => [
				'name' => 'USDT合约地址(可选)',
				'type' => 'input',
				'note' => '默认TRC20-USDT合约',
			],
		],
		'select' => null,
		'note' => '链上收款模式：用户按页面显示金额转入指定地址，页面会自动轮询链上到账。建议开启HTTPS并配置TronGrid API Key，避免限流。',
		'bindwxmp' => false,
		'bindwxa' => false,
	];

	private static function getConfig()
	{
		global $channel;
		$contract = trim((string)($channel['appsecret'] ?? ''));
		if($contract === ''){
			$contract = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';
		}
		return [
			'address' => trim((string)($channel['appkey'] ?? '')),
			'rate' => trim((string)($channel['appurl'] ?? '')),
			'api_key' => trim((string)($channel['appid'] ?? '')),
			'contract' => $contract,
			'decimals' => 6,
			'tolerance_seconds' => 6,
		];
	}

	private static function isValidTronAddress($address)
	{
		return is_string($address) && preg_match('/^T[1-9A-HJ-NP-Za-km-z]{33}$/', $address);
	}

	private static function usdtToInt($amount, $decimals = 6)
	{
		$amount = trim((string)$amount);
		if($amount === '' || !preg_match('/^\d+(?:\.\d+)?$/', $amount)) return false;
		$parts = explode('.', $amount, 2);
		$intPart = $parts[0];
		$fracPart = $parts[1] ?? '';
		$fracPart = substr(str_pad($fracPart, $decimals, '0'), 0, $decimals);
		return ltrim($intPart . $fracPart, '0') === '' ? '0' : ltrim($intPart . $fracPart, '0');
	}

	private static function calcUsdtAmount($cnyAmount, $rate)
	{
		if($rate !== '' && is_numeric($rate) && floatval($rate) > 0){
			$base = round(floatval($cnyAmount) / floatval($rate), 6);
		} else {
			$usd = currency_convert('CNY', 'USD', $cnyAmount);
			$base = round(floatval($usd), 6);
		}
		if($base <= 0){
			throw new Exception('USDT金额计算失败');
		}

		$offset = rand(1, 9999) / 1000000;
		$amount = number_format($base + $offset, 6, '.', '');
		return $amount;
	}

	private static function getTrc20Transfers($toAddress, $minTimestampMs, $contract, $apiKey = '')
	{
		$url = 'https://api.trongrid.io/v1/accounts/' . urlencode($toAddress) . '/transactions/trc20';
		$query = [
			'only_confirmed' => 'true',
			'limit' => 50,
			'min_timestamp' => (string)intval($minTimestampMs),
			'contract_address' => $contract,
		];
		$url .= '?' . http_build_query($query);
		$headers = [];
		if($apiKey !== ''){
			$headers[] = 'TRON-PRO-API-KEY: ' . $apiKey;
		}
		$data = get_curl($url, 0, 0, 0, 0, 0, 0, $headers);
		$arr = json_decode($data, true);
		if(!is_array($arr)){
			return [];
		}
		return is_array($arr['data'] ?? null) ? $arr['data'] : [];
	}

	private static function getTxValueInt($tx, $decimals = 6)
	{
		$value = $tx['value'] ?? ($tx['quant'] ?? null);
		if($value === null && isset($tx['token_info']['decimals']) && isset($tx['amount'])){
			$value = $tx['amount'];
		}
		if($value === null) return false;
		if(is_numeric($value) && strpos((string)$value, '.') === false && strlen((string)$value) > 6){
			return (string)$value;
		}
		return self::usdtToInt($value, $decimals);
	}

	private static function getTxTo($tx)
	{
		return $tx['to'] ?? ($tx['to_address'] ?? ($tx['toAddress'] ?? null));
	}

	private static function getTxFrom($tx)
	{
		return $tx['from'] ?? ($tx['from_address'] ?? ($tx['fromAddress'] ?? null));
	}

	private static function getTxId($tx)
	{
		return $tx['transaction_id'] ?? ($tx['transactionId'] ?? ($tx['txID'] ?? ($tx['hash'] ?? '')));
	}

	static public function submit()
	{
		global $order;
		$config = self::getConfig();
		if(!self::isValidTronAddress($config['address'])){
			return ['type' => 'error', 'msg' => 'TRON收款地址配置错误'];
		}
		// 仅支持“订单金额=实付金额”模式；如果你启用了随机增减金额(pay_payadd*)，请先关闭
		// 或者改为在此处基于 $order['realmoney'] 计算链上USDT金额并回写订单。
		if(isset($order['money']) && isset($order['realmoney']) && round(floatval($order['money']), 2) != round(floatval($order['realmoney']), 2)){
			return ['type' => 'error', 'msg' => 'USDT-TRC20暂不支持加价/随机增减金额模式，请关闭后再试'];
		}

		try{
			$payData = \lib\Payment::lockPayData(TRADE_NO, function() use ($config, $order) {
				$amount = self::calcUsdtAmount($order['realmoney'], $config['rate']);
				$amountInt = self::usdtToInt($amount, $config['decimals']);
				if($amountInt === false) throw new Exception('USDT金额格式错误');
				return [
					'chain' => 'TRON',
					'token' => 'USDT',
					'contract' => $config['contract'],
					'to' => $config['address'],
					'amount' => $amount,
					'amount_int' => $amountInt,
					'decimals' => $config['decimals'],
					'created_at' => time(),
				];
			});
		}catch(Exception $e){
			return ['type' => 'error', 'msg' => $e->getMessage()];
		}

		return [
			'type' => 'page',
			'page' => 'trc20usdt',
			'data' => [
				'trc20_to' => $payData['to'],
				'trc20_amount' => $payData['amount'],
				'trc20_contract' => $payData['contract'],
			],
		];
	}

	static public function notify()
	{
		global $order, $DB;
		$config = self::getConfig();
		if(!self::isValidTronAddress($config['address'])){
			return ['type' => 'html', 'data' => 'fail'];
		}
		if(!$order){
			return ['type' => 'html', 'data' => 'fail'];
		}
		if($order['status'] >= 1){
			return ['type' => 'html', 'data' => 'success'];
		}

		$ext = $DB->findColumn('order', 'ext', ['trade_no' => $order['trade_no']]);
		$payData = $ext ? @unserialize($ext) : null;
		if(!is_array($payData) || empty($payData['amount_int']) || empty($payData['to'])){
			return ['type' => 'html', 'data' => 'fail'];
		}

		$minTimestampMs = (strtotime($order['addtime']) - 60) * 1000;
		try{
			$txs = self::getTrc20Transfers($payData['to'], $minTimestampMs, $payData['contract'], $config['api_key']);
			foreach($txs as $tx){
				$to = self::getTxTo($tx);
				if($to !== $payData['to']) continue;
				$timestampMs = $tx['block_timestamp'] ?? ($tx['timestamp'] ?? null);
				if($timestampMs !== null){
					$timestampSec = intval($timestampMs) > 2000000000000 ? intval($timestampMs / 1000) : intval($timestampMs);
					if($timestampSec + intval($config['tolerance_seconds']) < strtotime($order['addtime'])) continue;
				}
				$valueInt = self::getTxValueInt($tx, intval($payData['decimals'] ?? 6));
				if($valueInt === false) continue;
				if($valueInt !== $payData['amount_int']) continue;

				$txid = self::getTxId($tx);
				$from = self::getTxFrom($tx);
				processNotify($order, $txid, $from);
				return ['type' => 'html', 'data' => 'success'];
			}
		}catch(Exception $e){
			return ['type' => 'html', 'data' => 'fail'];
		}

		return ['type' => 'html', 'data' => 'fail'];
	}
}
