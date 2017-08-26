<?php

namespace liesauer\Gincs;

class CryptUtil
{
    public static $default_key = 'c91ec25e5fdab4d8572029aa36a65f0d';

    public static function encode($string, $key = '', $expiry = 3600)
    {
        $ckey_length   = 4;
        $key           = md5($key ? $key : self::$default_key); //解密密匙
        $keya          = md5(substr($key, 0, 16)); //做数据完整性验证
        $keyb          = md5(substr($key, 16, 16)); //用于变化生成的密文 (初始化向量IV)
        $keyc          = substr(md5(microtime()), -$ckey_length);
        $cryptkey      = $keya . md5($keya . $keyc);
        $key_length    = strlen($cryptkey);
        $string        = sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);

        $rndkey = array();
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        $box = range(0, 255);
        // 打乱密匙簿，增加随机性
        for ($j = $i = 0; $i < 256; $i++) {
            $j       = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp     = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        // 加解密，从密匙簿得出密匙进行异或，再转成字符
        $result = '';
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a       = ($a + 1) % 256;
            $j       = ($j + $box[$a]) % 256;
            $tmp     = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        $result = $keyc . str_replace('=', '', base64_encode($result));
        $result = str_replace(array('+', '/', '='), array('-', '_', '.'), $result);
        return $result;
    }

    public static function decode($string, $key = '')
    {
        $string      = str_replace(array('-', '_', '.'), array('+', '/', '='), $string);
        $ckey_length = 4;
        $key         = md5($key ? $key : self::$default_key); //解密密匙
        $keya        = md5(substr($key, 0, 16)); //做数据完整性验证
        $keyb        = md5(substr($key, 16, 16)); //用于变化生成的密文 (初始化向量IV)
        $keyc        = substr($string, 0, $ckey_length);

        $cryptkey      = $keya . md5($keya . $keyc);
        $key_length    = strlen($cryptkey);
        $string        = base64_decode(substr($string, $ckey_length));
        $string_length = strlen($string);

        $result = '';
        $box    = range(0, 255);
        $rndkey = array();
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
        // 打乱密匙簿，增加随机性
        for ($j = $i = 0; $i < 256; $i++) {
            $j       = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp     = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        // 加解密，从密匙簿得出密匙进行异或，再转成字符
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a       = ($a + 1) % 256;
            $j       = ($j + $box[$a]) % 256;
            $tmp     = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0)
            && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)
        ) {
            return substr($result, 26);
        } else {
            return '';
        }
    }

}
