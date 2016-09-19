<?php
    define('DOC_ROOT', realpath(dirname(__FILE__) . '/../../..'));

    $username = 'osatparticipants';
    $password = 'login123';
    $url = 'http://osat.dev/survey/index.php/admin/authentication/sa/login'; // 'http://assessment.eu-fundraising.eu/index.php/admin/authentication/sa/login';
    // $proxy = "127.0.0.1:8080";

    //set the directory for the cookie using defined document root var
    $cookiejar = dirname(__FILE__) . '/cookie.' . md5(time() . rand(0,1000)) . '.txt';

    //login form action url
    $postinfo = [
        'user' => $username,
        'password' => $password,
        'YII_CSRF_TOKEN' => null,
        'PHPSESSID' => null
    ];


    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3 );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");

    if(!empty($proxy))
    {
        curl_setopt($ch, CURLOPT_PROXY, $proxy[0]);
        curl_setopt($ch, CURLOPT_PROXYPORT, $proxy[1]);
    }

    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiejar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiejar);
    //set the cookie the site has for certain features, this is optional
    # curl_setopt($ch, CURLOPT_COOKIE, "cookiename=0");
    curl_setopt($ch, CURLOPT_REFERER, $_SERVER['REQUEST_URI']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);

    $response = curl_exec($ch);
    if (curl_errno($ch)) die(curl_error($ch));

    // grab from HEADER
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    foreach($postinfo as $k => $v)
    {
        if(empty($v))
        {
            preg_match('/'.$k.'=([^;]+)/i', $response, $matches);
            if(!empty($matches[1]))
            {
                $postinfo[$k] = $matches[1];
            }
        }
    }
    unset($header_size, $header, $k, $v, $matches);
    echo "CSRF : " . $postinfo['YII_CSRF_TOKEN'] . "\n";

    curl_close($ch);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3 );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6");

    if(!empty($proxy))
    {
        curl_setopt($ch, CURLOPT_PROXY, $proxy[0]);
        curl_setopt($ch, CURLOPT_PROXYPORT, $proxy[1]);
    }

    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiejar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiejar);
    //set the cookie the site has for certain features, this is optional
    # curl_setopt($ch, CURLOPT_COOKIE, "cookiename=0");
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);

    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postinfo));
    $response = curl_exec($ch);
    if (curl_errno($ch)) die(curl_error($ch));

    print_r($response); die();

    //page with the content I want to grab
    curl_setopt($ch, CURLOPT_URL, $url . '/?osatparticipantsaction=cron');
    //do stuff with the info with DomDocument() etc
    $html = curl_exec($ch);
    curl_close($ch);

    unlink($cookie_file_path);

    print_r($html);
    exit();


    /*
    session_start();

    $string = "user=".$username."&password=".$password;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3 );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    # curl_setopt($ch, CURLOPT_COOKIE, "adminhtml=$sessionId" );
    curl_setopt($ch, CURLOPT_COOKIE, session_name().'='.session_id());
    curl_setopt($ch, CURLOPT_COOKIESESSION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);

    if(!empty($proxy))
    {
        curl_setopt($ch, CURLOPT_PROXY, $proxy[0]);
        curl_setopt($ch, CURLOPT_PROXYPORT, $proxy[1]);
    }

    $buffer = curl_exec($ch);


    preg_match('/YII_CSRF_TOKEN=([0-9a-z]{40})/i', $buffer, $CSRF);
    if(!empty($CSRF[1]))
    {
        $CSRF = $CSRF[1];

        $string.='&YII_CSRF_TOKEN=' . $CSRF;

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $string);

        $buffer = curl_exec($ch);
    }
    curl_close($ch);

    session_write_close(); // so we can reuse the session id and formkey...
    # unset($session,$string,$ch,$buffer);

    print_r($buffer);
    die("!");





     */
