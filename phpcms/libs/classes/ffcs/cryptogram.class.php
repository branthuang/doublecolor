<?php

/**
 * UDB加解密辅助类
 * 2011.5.31
 */
class Cryptogram {

	private static $pIV = '12345678';

	/*	 * *
	 * 使用Key加密
	 */

	public static function encryptByKey($originalStr, $key) {
		$_key = pack('H48', $key);
		$iv = self::ord2str(self::$pIV);

		return base64_encode(self::encrypt($_key, $iv, $originalStr));
	}

	public static function encrypt($key, $iv, $originalStr) {
		$td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');
		mcrypt_generic_init($td, $key, $iv);
		$input = self::paddingPKCS7($originalStr);
		$encrypted_data = mcrypt_generic($td, $input);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return $encrypted_data;
	}

	/*	 * *
	 * 使用Key解密
	 */

	public static function decryptByKey($originalStr, $key) {
		$_key = pack('H48', $key);
		$iv = self::ord2str(self::$pIV);

		return self::decrypt($_key, $iv, base64_decode($originalStr));
	}

	public static function decrypt($key, $iv, $originalStr) {
		$td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_CBC, '');

		//使用MCRYPT_3DES算法,cbc模式
		mcrypt_generic_init($td, $key, $iv);
		//初始处理
		$decrypted = mdecrypt_generic($td, $originalStr);
		//解密
		mcrypt_generic_deinit($td);
		//结束
		mcrypt_module_close($td);

		return self::pkcs5_unpad($decrypted);
	}

	public static function computeHashString($s) {
		return base64_encode(self::ComputeHash($s));
	}

	/*	 * *
	 * 生成验证码
	 * base64 (3DES(SHA1()))
	 */

	public static function generateAuthenticator($key, $source) {
		$_key = pack('H48', $key);
		$iv = self::ord2str(self::$pIV);
		$tmp = self::computeHash($source);
		return base64_encode(self::encrypt($_key, $iv, $tmp));
	}

	private static function computeHash($str) {
		return sha1($str, 1);
	}

	private static function ord2str($hexstr) {
		for ($i = 0; $i < strlen($hexstr); ++$i)
			$iv.=chr(substr($hexstr, $i, 1));
		return $iv;
	}

	private static function paddingPKCS7($data) {
		$block_size = mcrypt_get_block_size('tripledes', 'cbc');
		$padding_char = $block_size - (strlen($data) % $block_size);
		$data .= str_repeat(chr($padding_char), $padding_char);
		return $data;
	}

	private static function pkcs5_unpad($text) {
		$pad = ord($text{strlen($text) - 1});
		if ($pad > strlen($text)) {
			return false;
		}
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
			return false;
		}
		return substr($text, 0, -1 * $pad);
	}

}

?>
