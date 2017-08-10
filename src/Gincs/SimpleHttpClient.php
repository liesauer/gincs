<?php

namespace liesauer\Gincs;

class SimpleHttpClient {
  public static function quickGet($url,$header=NULL,$cookie='',$data='',$options=NULL) {
    return self::quickRequest($url.(empty($data)?'':'?'.$data),'GET',$header,$cookie,'',$options);
  }
  public static function quickPost($url,$header=NULL,$cookie='',$data='',$options=NULL) {
    return self::quickRequest($url,'POST',$header,$cookie,$data,$options);
  }
  private static final function quickRequest($url,$method,$header=NULL,$cookie='',$data='',$options=NULL) {
    $ch=curl_init($url);
    switch (strtoupper($method)) {
      case 'GET':
        //curl_setopt($ch,CURLOPT_HTTPGET,TRUE);
        break;
      case 'POST':
        curl_setopt($ch,CURLOPT_POST,TRUE);
        if (!empty($data))
          curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        break;
      default:
        return FALSE;
        break;
    }
    if (count($header)>=1)
      curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
    if (!empty($cookie))
      curl_setopt($ch,CURLOPT_COOKIE,$cookie);
    curl_setopt($ch,CURLOPT_HEADER,TRUE);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
    curl_setopt($ch,CURLOPT_TIMEOUT,30);
    if (strtolower(substr($url,0,5))==='https') {
      curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
      curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
    }
    if (count($options)>=1)
      curl_setopt_array($ch,$options);
    $re=array(
      'http_code'=>0,
      'header'=>'',
      'data'=>''
      );
    $re['data']=curl_exec($ch);
    $re['http_code']=curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $header_size=curl_getinfo($ch,CURLINFO_HEADER_SIZE);
    $re['header']=substr($re['data'],0,$header_size);
    $re['data']=substr($re['data'],$header_size);
    curl_close($ch);
    return $re;
  }
}
