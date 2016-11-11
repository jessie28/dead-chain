<?php
header("Content-type: text/html; charset=utf-8");
$link = "http://www.chinagoabroad.com/";
date_default_timezone_set('Asia/Shanghai');
set_time_limit(0);
$tip = 0;
$once = 1;
$second = 1;

//TODO，清空临时表，是否需要？done
//phpinfo();
connect();
//scanSecond();
//checkOneMore();
scanContent();
function connect(){
    global $once;
    global $mysqli;
    $servername = "localhost";
    $username = "wyx";
    $password = "YoNrYdHLw9HlyYO0";
    $dbname = "cga_union_bak";
    $mysqli = new mysqli($servername,$username,$password,$dbname);
    $mysqli->set_charset("utf8");
    if(!$mysqli){
        die("wrong");
    }
    //创建临时表
    if($once == 1){
        $name = 'temp_list';
        createTable($name);
    }
    $once++;

}


function getLink(){
    global $link;
    return $link;
}
function getList($sql){
    global $mysqli;
    $result = $mysqli->query($sql);
    $list = array();
    if($result -> num_rows >0){
        while ($row = $result->fetch_assoc()) {
            $list[] = $row;
        }
    }
    var_dump(count($list));
    return $list;
}

/**
 *
 */
function createTable($name){
    var_dump('create : ' .$name);
    global $mysqli;
    $sql = "drop table `".$name."`";
    $result1 = $mysqli->query($sql);
    $sql = "CREATE TABLE IF NOT EXISTS `".$name."` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `content_id` int(11) NOT NULL,
          `a_href` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
          `a_text` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
          `img_src` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
          `status` int(11) NOT NULL,
          `lang` int(11) NOT NULL,
          `add_time` int(11) NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=438 ;
        ";
    $result = $mysqli->query($sql);
    if($result === false){
        die("creat false");
    }
}

/**
 *  扫描content_url
 */
function scanContentUrl($p = 1){
    set_time_limit(0);
    $info = getContentUrlByPage($p);
    if(!$info){
        saveCSVfromTempTable();
    }
    foreach ($info as $itme){
        $url_en =  getLink()."en/".trim($itme['url'],'/');
        $url_zh =  getLink()."zh/".trim($itme['url'],'/');
        $status_en = getHeaderInfo($url_en);
        $status_zh = getHeaderInfo($url_zh);
        if($status_en !== false || $status_zh !== false){
            $list[] = $itme['id']."||".iconv('utf-8','gb2312',$url_en) ."||||||||".$status_en."||0";
            $list[] = $itme['id']."||".iconv('utf-8','gb2312',$url_zh) ."||||||||".$status_zh."||0";
        }
    }
    $p++;
    if($list){
        addListtoSql($list,'scanContentUrl',$p);
    }else{
        scanContentUrl($p);
    }

}

/**
 * 扫描content
 */
 function scanContent($p = 1){
    set_time_limit(0);
    $info = getContentByPage($p);
    if(!$info){
        scanContentMember();
    }
    $content_list = filterContent($info);
    $list = _filterListInfo($content_list);
    $p++;
    if($list){
        addListtoSql($list,'scanContent',$p);
    }else{
        scanContent($p);
    }
}

/**
 * 匹配content和content_member这两个表的信息
 *
 */
function _filterListInfo($content_list){
    $list = array();
    foreach ($content_list as $key=>$value){
        $a_href = @$value['a_href'] ? $value['a_href'] : array();
        $content_id = @$value['content_id'] ? $value['content_id'] : 0;
        $img_url = @$value['img_url'] ? $value['img_url'] : array();
        $lang = @$value['lang'] ? $value['lang'] : 0;
        foreach ($a_href as $k=>$v){
            $a_url = $v;
            $status = getHeaderInfo($a_url);
            if($status !== false){
                $a_text = @$value['a_text'][$k] ? $value['a_text'][$k] : ' ';
                $list[] = $content_id."||".$v."||".$a_text."||||".$status."||".$lang;
            }
        }
        foreach ($img_url as $ik=>$iv){
            $i_url = $iv;
            $img_status = getHeaderInfo($i_url);
            if($img_status !== false){
                $list[] = $content_id."||||||".$iv."||".$img_status."||".$lang;
            }
        }
    }
    return $list;
}

/**
 * 扫描content_member
 */
function scanContentMember($p = 1)
{
    set_time_limit(0);
    $info = getContentMemberByPage($p);
    if (!$info) {
        //从临时表里读取数据存入csv
//        scanContentUrl();
//        saveCSVfromTempTable();
        scanSecond();
    }
    $content_list = filterContentMember($info);
    $list = _filterListInfo($content_list);
    $p++;
    if ($list) {
        addListtoSql($list, 'scanContentMember', $p);
    } else {
        scanContentMember($p);
    }
}
/**
 * 扫描第二遍
 */
function scanSecond($time = 1){
    //todo 获取第一个表的名字，因为第一个表名是写死的，所以没做

    global $second;
    var_dump("second_time:".time()."\ntime = ".$time."&second=".$second);
    if($second > 2){
        die('wrong,but i dont know y');
    }
    $old_name = 'temp_list';
    if($second == 1 && $time == 1){
        $old_name = 'temp_list';
        $name = 'temp_list_2';
        createTable($name);
    }elseif ($second == 2 && $time == 2){
        $old_name = 'temp_list_2';
        $name = 'temp_list_3';
        createTable($name);
    }
    $second++;
    $sql = 'select content_id,a_href,a_text,img_src,status,lang from '.$old_name.' order by status,content_id';
    $content_list = getList($sql);
    $list = array();
    if($time == 1){
//        $list = getSecondList($content_list);
        $list = getThirdList($content_list);
    }elseif ($time == 2){
        $list = getForthList($content_list);
    }
    if($list){
        $res = addsql($list,$name);
        if($res){
//            saveCSVfromTempTable($name);
            if($time == 1){
                scanSecond(2);
            }elseif ($time == 2){
                saveCSVfromTempTable($name);
            }

        }
    }
}


function getSecondList($content_list){
    $list = array();
    foreach ($content_list as $item){
        if (($status = getHeaderInfo(@$item['a_href'])) !== false){
            $list[] = $item['content_id']."||".$item['a_href']."||".$item['a_text']."||||".$status."||".$item['lang'];
        }elseif (($status = getHeaderInfo(@$item['img_src'])) !== false){
            $list[] = $item['content_id']."||||||".$item['img_src']."||".$status."||".$item['lang'];
        }else{
            continue;
        }
    }
    return $list;
}

function getThirdList($content_list){
    $list = array();
    foreach ($content_list as $item){

        if(@$item['a_href']){
            $status = getThirdStatus(@$item['status'],@$item['a_href']);
            if($status !== false){
                $list[] = $item['content_id']."||".$item['a_href']."||".$item['a_text']."||||".$status."||".$item['lang'];
            }
        }
        if(@$item['img_src']){
            $status = getThirdStatus(@$item['status'],@$item['img_src']);
            if($status !== false){
                $list[] = $item['content_id']."||||||".$item['img_src']."||".$status."||".$item['lang'];
            }
        }
    }
    return $list;
}

function getForthList($content_list){
    $list = array();
    foreach ($content_list as $item){
        if(@$item['a_href']){
            $status = getForthStatus(@$item['status'],@$item['a_href']);
            if($status !== false){
                $list[] = $item['content_id']."||".$item['a_href']."||".$item['a_text']."||||".$status."||".$item['lang'];
            }
        }
        if(@$item['img_src']){
            $status = getForthStatus(@$item['status'],@$item['img_src']);
            if($status !== false){
                $list[] = $item['content_id']."||||||".$item['img_src']."||".$status."||".$item['lang'];
            }
        }
    }
    return $list;
}

function getThirdStatus($status , $url){
    if($status == 408){
        return false;
    }else{
        $httpCode = checkOneMore($url , $status);
        if($httpCode != 0){
            $code = substr($httpCode,0,1);
            if($code == 4 || $code == 5){
                if($httpCode != $status){
                    return $status;
                }else{
                    return $httpCode;
                }
            }else{
                return false;
            }
        }else{
            return $httpCode;
        }
    }
}

function getForthStatus($status , $url){
    if($status == 408){
        return false;
    }else{
        $httpCode = getUrlStatusByfile($url , $status);
        if($httpCode != 0){
            $code = substr($httpCode,0,1);
            if($code == 4 || $code == 5){
                if($httpCode != $status){
                    return $status;
                }else{
                    return $httpCode;
                }
            }else{
                return false;
            }
        }else{
            return $httpCode;
        }
    }
}

/**
 * 从临时表里读取数据并存入CSV
 */
function saveCSVfromTempTable($name){
    $sql = 'select content_id,a_href,a_text,img_src,status,lang from '.$name.' order by status,content_id';
    $list = getList($sql);

    $filename = 'content_list_'.date('Ymd').'.csv'; //设置文件名
    $file = "/data/csv/".$filename;
    if(!file_exists("/data/")){
        mkdir("/data/");
    }
    if(!file_exists("/data/csv/")){
        mkdir("/data/csv/");
    }
    /*$csv = fopen($file,"a+");
    $title = array(
        'content_id','url','a_href','a_text','img_src','status'
    );
    fputcsv($csv,$title);
    foreach ($list as $item){
        fputcsv($csv,$item);
    }*/
     $str = "content_id,a_href,a_text,img_src,status,lang"."\n";
    foreach ($list as &$v){
        $v['a_href'] = dealContent(@$v['a_href']);
        $v['a_text'] = dealContent(@$v['a_text']);
        $v['img_src'] = dealContent(@$v['img_src']);
        $v['lang'] = $v['lang'] == 1 ? 'zh':'en';
        $str .= implode(",",$v)."\n";
    }
    unset($v);
    $info = file_put_contents($file,$str,FILE_APPEND);
    //todo 结束后关闭数据库? done
//    fclose($csv);
    global $mysqli;
    mysqli_close($mysqli);
    die('done');
}

/**
 * 处理数据库中的字符串
 *
 */
function dealContent($content){
    $str = "";
    if($content){
        $content = html_decode($content);
        $content = str_replace(",","，",$content);
        $str = iconv('utf-8','GBK//IGNORE',$content);
//        $str = str_replace(",","|",$str);
        $str = str_replace("\n","",$str);
        $str = str_replace("\t","",$str);
        $str = str_replace("\r","",$str);
        $str = trim($str," ");
    }
    return $str;
}


/**
 * 匹配content信息
 * @param array $info
 * @return mixed
 */
function filterContent($info){
    $list = array();
    foreach ($info as $k=>$v){
        $content_zh = htmlspecialchars_decode($v['content_zh']);
        if($a = _filterInfo($content_zh,$v['id'] , 1)){
            $list[] = $a;
        }
        $content_en = htmlspecialchars_decode($v['content_en']);
        if($b = _filterInfo($content_en,$v['id'] , 2)){
            $list[] = $b;
        }
    }
    return $list;
}

/**
 * 匹配content_member信息
 * @param array $info
 * @return mixed
 */
function filterContentMember($info){
    $list = array();
    foreach ($info as $k=>$v){
        $sidebar_en = htmlspecialchars_decode($v['sidebar_en']);
        if($a = _filterInfo($sidebar_en,$v['content_id'] , 2)){
            $list[] = $a;
        }
        $sidebar_zh = htmlspecialchars_decode($v['sidebar_zh']);
        if($a1 = _filterInfo($sidebar_zh,$v['content_id'] , 1)){
            $list[] = $a1;
        }
        $member_publications_en = htmlspecialchars_decode($v['member_publications_en']);
        if($a2 = _filterInfo($member_publications_en,$v['content_id'] , 2)){
            $list[] = $a2;
        }
        $member_publications_zh = htmlspecialchars_decode($v['member_publications_zh']);
        if($a3 = _filterInfo($member_publications_zh,$v['content_id'] , 1)){
            $list[] = $a3;
        }
        $member_outteam_en = htmlspecialchars_decode($v['member_outteam_en']);
        if($a4 = _filterInfo($member_outteam_en,$v['content_id'] , 2)){
            $list[] = $a4;
        }
        $member_outteam_zh = htmlspecialchars_decode($v['member_outteam_zh']);
        if($a5 = _filterInfo($member_outteam_zh,$v['content_id'] , 1)){
            $list[] = $a5;
        }
    }
    return $list;
}

/**
 * 处理数据表中的字段内容
 * @param $content
 * @param $content_id
 * @return mixed
 */

function _filterInfo($content,$content_id = 0,$lang = 0){
    $a = _filterUrl($content);
    $list = array();
    $en_a_href = array();
    if($a){
        foreach ($a[1] as $k1=>$v1){
            if(_filterEmail($v1) !== null){
                $en_a_href[$k1] = _filterEmail($v1);
                $text = strip_tags($a[2][$k1]);
//                $en_a_text[$k1] = checkImgUrl($text);
                $en_a_text[$k1] = $text;
            }
        }
        if($en_a_href){
            $list['a_href'] = $en_a_href;
            $list['a_text'] = $en_a_text;
            $list['content_id'] = $content_id;
            $list['lang'] = $lang ;
        }

    }
    $img_src = _filterSrc($content);
    if($img_src){
        $list['img_src'] = $img_src;
        $list['content_id'] = $content_id;
        $list['lang'] = $lang ;
    }
    return $list;
}

/**
 * 判断字符串里是否包含html标签
 * @param $str
 * @return bool
 */
function judgeHtml($str){
    if($str != strip_tags($str)){
        return true;
    }else{
        return false;
    }
}

//能打开但是每次都判断错误的网站
function get_wrong_judge_url(){
    $url_arr = array(
        'http://www.ey.com/Publication/vwLUAssets/Business_Pulse_-_top_10_risks_and_opportunities/$FILE/Business pulse 2013.pdf',
        'http://www.ey.com/Publication/vwLUAssets/UK_as_a_holding_company_jurisdiction_for_Chinese_investments/$FILE/UK China Tax Brochure_EN.pdf',
        'http://www.ey.com/Publication/vwLUAssets/Why_Invest_in_Paris_2013_(Chinese)/$FILE/Why Invest in Paris (2013)_vCN.pdf',
        'http://www.ey.com/Publication/vwLUAssets/Renewable_Energy_Country_Attractiveness_Index_43/$FILE/RECAI 43_March 2015.pdf',
        'http://greekreporter.com/',
        'http://www.justice.gov/criminal/pr/speeches/2013/crm-speech-130617.html',
        'http://mbandf.com',
        'http://www.mwcshanghai.com/',
        'http://www.lifescienceaustria.at',
        'http://geff2015.com/',
        'https://portal.cbbc.org/node/96',
        'http://artcentralhongkong.com/zh-hant/',
        'http://artcentralhongkong.com',
        'http://mooc.pku.edu.cn/enroll/03.html',
        'http://www.mobileworldlive.com/mwc-shanghai-16-video/mwcs16-keynote/',
        'http://mooc.pku.edu.cn/',
        'http://bpiaj.com/',
        'http://m.cnzouchuqu.com/nd.jsp?id=107',
        'http://europa.eu/'
    );
    return $url_arr;
}

/**
 * 判断是否为死链
 * @param url : 链接
 */
function getHeaderInfo( $url ){
    global $tip;
    $tip++;
    $start_time = time();
    $url_arr = get_wrong_judge_url();
    if(!$url || judgeHtml($url)){
        return false;
    }else{
        $url = html_decode($url);
        if(in_array($url,$url_arr)){
            return false;
        }
//        header("Content-type: text/html; charset=utf-8");
        $ch = curl_init ();
        $options = array(
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_ENCODING       => "",
            CURLOPT_AUTOREFERER    => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_MAXREDIRS      => 10,
        );
        curl_setopt_array( $ch, $options );
        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
//        curl_close($ch);
        $diff_time = time()-$start_time;
        if( $httpCode == 0 && ($diff_time < 10) ){
            //未超时且没有返回结果的再获取一次结果
            $httpCode = checkOneMore($url);
        }elseif( $httpCode == 0 && ($diff_time >= 10) ){
            $httpCode = 408;
        }
        $code = substr($httpCode,0,1);
        //有些国外网站，可以打开，但是返回的httpcode为0；
        var_dump("total_num:".$tip."&url=".$url."&status=".$httpCode."&time=".$diff_time);
        if(($code == 4 || $code == 5 || $httpCode == 0 ) && ($httpCode != 401) ) {
            return $httpCode;
        }
    }
    return false;
}
/**
 * 为0的结果在检查一次
 */
function checkOneMore( $url  , $status = 0 ){
    $url = html_decode($url);
    @header("Content-type: text/html; charset=utf-8");
    $res = get_headers($url);
    if($res){
        $msg = explode(" ",$res[0]);
        $code = $msg[1];
    }else{
        $code = $status;
    }
    var_dump("third:url=".$url."&res=".$res[0]." &code=".$code);
    return $code;
}
function getUrlStatusByfile($url , $status = 0){
    $url = html_decode($url);
    @header("Content-type: text/html; charset=utf-8");
    @file_get_contents($url);
    if($http_response_header){
        $code = $http_response_header[0];
        var_dump("forth:url=".$url."&res=".$code);
        $msg = explode(" ",$code);
        $code = $msg[1];
    }else{
        $code = $status;
    }
    return $code;
}

/**
 * 分页获取content_url信息
 * @param 当前页数|int $p 当前页数
 * @param null $param
 * @return
 */
function getContentUrlByPage($p = 1 , $param = null){
    $limit = 1000;
    if( $p === 1 ){
        $page = 0;
    }else{
        $page = $limit * ($p - 1);
    }
    $where = "url not like '%/node/%'";
    $order = 'id';
    $where = $param['where'] ? "1=1" : $where;
    $order = $param['order'] ? $param['order'] : $order;
    $sql = "select id,url from content_url where ".$where." order by ".$order." limit ".$page.",".$limit;
    $list = getList($sql);
    return $list;
}

/**
 * 分页获取content信息
 * @param int $p 当前页数
 * @return mixed
 */
function getContentByPage($p = 1){
    $limit = 1000;
    if( $p === 1 ){
        $page = 0;
    }else{
        $page = $limit * ($p - 1);
    }
    $order = "id";
    $sql = "select id,content_en,content_zh,status_en,status_zh from content where status_en = 1 or status_zh = 1  order by ".$order." limit ".$page.",".$limit;
    $list = getList($sql);
    foreach ($list as $key=>&$value){
        if($value['status_en'] != 1){
            $value['content_en'] = '';
        }
        if($value['status_zh'] != 1){
            $value['content_zh'] = '';
        }
    }
    unset($value);
    return $list;
}

/**
 * html解码
 */
function html_decode($url){
    //判断是否包含这些字符
    $yh_pos = strpos($url , "&quot;");
    $dyh_pos = strpos($url , "&#39;");
    $xy_pos = strpos($url , "&lt;");
    $dy_pos = strpos($url , "&gt;");
    $and_pos = strpos($url , "&amp;");
    if(
        ($yh_pos !== false) ||
        ($dyh_pos !== false) ||
        ($xy_pos !== false) ||
        ($dy_pos !== false) ||
        ($and_pos !== false)
    ){
        $url = htmlspecialchars_decode($url);
        return html_decode($url);
    }else{
//        var_dump("url:".$url);
        $url = str_replace("&#10;","\n",$url);
        $url = str_replace("&#13;","\r",$url);
        $url = str_replace("&#09;","\t",$url);
        $url = str_replace("&#32;"," ",$url);
        $url = str_replace("\n","",$url);
        $url = str_replace("\t","",$url);
        $url = str_replace("\r","",$url);
        $url = trim($url);
        return $url;
    }
}



function text_decode($url){
    //判断是否包含这些字符

    $url = htmlspecialchars_decode($url);
    $url = str_replace(";"," ",$url);
    $url = str_replace("\n"," ",$url);
    $url = str_replace("\t"," ",$url);
    $url = str_replace("\r"," ",$url);
    $url = str_replace(";"," ",$url);
    $url = str_replace("\"\""," ",$url);
    $url = trim($url);
    return $url;
}

/**
 * 获取content_member数据
 * @param int $p
 * @return mixed
 */

function getContentMemberByPage($p = 1){
    $limit = 1000;
    if( $p === 1 ){
        $page = 0;
    }else{
        $page = $limit * ($p - 1);
    }
    $order = "id";
    $sql = "select * from content_member order by ".$order." limit ".$page.",".$limit;
    $list = getList($sql);
    foreach ($list as $key=>&$value){
        $value['sidebar_en'] = html_decode($value['sidebar_en']);
        $value['sidebar_zh'] = html_decode($value['sidebar_zh']);
        $value['member_publications_en'] = html_decode($value['member_publications_en']);
        $value['member_publications_zh'] = html_decode($value['member_publications_zh']);
        $value['member_outteam_en'] = html_decode($value['member_outteam_en']);
        $value['member_outteam_zh'] = html_decode($value['member_outteam_zh']);
    }
    unset($value);
    return $list;
}


/**
 * 获取页面中的a标签href和text
 * @param $web_content htmlcode
 * @return mixed
 */
function _filterUrl($web_content){
    $reg_tag_a = '/<[a|A].*?href=[\'\"]{0,1}([^>\'\"]*).*?>(.*?)<\/a>/';
    $result = preg_match_all($reg_tag_a,$web_content,$match_result);
    if($result){
        //$match_result[1]是href内容
        //$match_result[2]是text
        return $match_result;
    }
}


/**
 * 获取页面中的img标签src
 * @param $web_content
 * @return mixed
 */
function _filterSrc($web_content){
    $reg_tag_s = '/<img.*?src=[\'\"]{0,1}([^>\'\"\ ]*).*?>/';
    $result = preg_match_all($reg_tag_s,$web_content,$match_result);
    if($result){
        foreach ($match_result[1] as $k=>$v){
            $url[$k] = completeUrl($v,true);
        }
        return $url;
    }
}

/**
 * 判断是不是邮件地址
 * @param (String)$url 链接
 * @return bool|string
 */
function _filterEmail( $url ){
    $pos = strpos( $url , "mailto:");
    $at_pos = strpos( $url , "@" );
    if($pos === 0 && ($at_pos !== false)){
//        $reg_tag_e = '/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i';
//        $reg_tag_e = '/(\w+)@(\w)/';
//        if(preg_match($reg_tag_e, ltrim($url,"mailto:"))){
//            //是邮箱
//            $url = null;
//        }
        $url = null;
    }else{
        $url = completeUrl($url);
    }
    return $url;
}

/**
 * 补全链接
 * @param string $url
 * @param bool $img
 * @return string
 */
function completeUrl($url,$img=false){
    $http_pos = strpos($url, "http://");
    $https_pos = strpos($url,"https://");
    $javascript = strpos($url,"javascript:");
    $jing = strpos($url,"#");
    if($http_pos === false && $https_pos === false && $javascript === false){
        if($img){
            $url = (getLink())."Public/".trim($url,"/");
        }else{
            $url = (getLink()).trim($url,"/");
        }
    }elseif($javascript === 0 || $jing === 0){
        $url = null;
    }
    return $url;
}

/**
 * 判断a标签的text是否为img标签
 * @param string $text
 * @return mixed
 */
function checkImgUrl($text){
    $reg_tag_img = '/<\s*img\s+[^>]*\/>(.*)/';
    $result = preg_match_all($reg_tag_img,$text,$match_result);
    if($result){
        return $match_result[1][0];
    }else{
        return $text;
    }
}

/**
 * 添加内容到临时表
 * @param $list
 * @param string $name
 * @param $p
 */
function addListtoSql($list,$name,$p){
    if($list){
        addsql($list,'temp_list');
    }
    var_dump($name."//well done//".time());
    $name($p);
}

/**
 * 清空临时表并存储数据
 */
function addsql($list,$name){
    $len = 100;
    global $mysqli;
    $res = false;
    $wrong_list = '';
    $biglen = (int)(ceil(count($list)/$len));
    for($i= 0;$i<=$biglen;$i++){
        $index = $i*$len;
        $slice_list = array_slice($list,$index,$len);
        $mysql = 'INSERT INTO '.$name.'(`content_id`,`a_href`,`a_text`,`img_src`,`status`,`lang`,`add_time`) VALUES';
        if($slice_list){
            var_dump('slice_list_count '.count($slice_list));
            foreach ($slice_list as $k=>$v){
                if($v){
                    $value = mb_split("\|\|",htmlspecialchars($v));
                    if($value && ($value[4] !== '')){
                        $value[2] = text_decode($value[2]);
                        $mysql .= "($value[0],'$value[1]','$value[2]','$value[3]',$value[4],$value[5],".time()."),";
                    }
                }
            }
            $mysql = trim($mysql,",");
            $res = $mysqli->query($mysql);
            if($res === false){
                $wrong_list .= implode("\n\t",$slice_list);
                var_dump($mysql);
                var_dump("insert wrong");
                $wrong_list .= ($mysql ."\n\t ");
                putWrongSql($wrong_list);
//                die();
            }
        }
    }
    return $res;
}


function putWrongSql($list){
    $name = 'wrong-sql-'.date('Y-m-d');
    $filename = $name.'.txt'; //设置文件名
    $file = "/data/csv/".$filename;
    if(!file_exists("/data/")){
        mkdir("/data/");
    }
    if(!file_exists("/data/csv/")){
        mkdir("/data/csv/");
    }
    file_put_contents($file,$list,FILE_APPEND);

}


/*------------------无穷无尽分割线-------------------------*/

function getCsvTest($list,$name = "",$p,$over=false){
    $filename = $name.'_'.date('Ymd').'.csv'; //设置文件名
    $file = "/data/csv/".$filename;
    if(!file_exists("/data/")){
        mkdir("/data/");
    }
    if(!file_exists("/data/csv/")){
        mkdir("/data/csv/");
    }
    $str = "";
    foreach ($list as $k=>$v){
        $value = mb_split("\|\|",$v);
        $value = implode($value,",");
        $str .= $value."\n";
    }
    file_put_contents($file,$str,FILE_APPEND);
    if($p){
        if($name == "content"){
            scanContent($p);
        }elseif($name == "content_member"){
            scanContentMember($p);
        }elseif ($name == "content_url"){
            scanContentUrl($p);
        }
    }else{
        echo "\n".time();
        die("完成");
    }

}




/**
 * 读取excel获取数据
 *
 */
function getCountentByExcel($name){
    header("Content-Type:text/html;charset=utf-8");
    $filename = $name.'_'.date('Ymd').'.csv'; //设置文件名
    $file = "/data/csv/".$filename;
    $info = file_get_contents($file);
    if(!$info){
        $arr = array();
    }else{
        $arr = explode("\n",mb_convert_encoding($info, "UTF-8", "gbk"));
        array_shift($arr);
        array_walk($arr,"myfunction");
//    var_dump($arr);
    }
    return $arr;
}
function myfunction(&$value,$key){
    $value = explode(",",$value);
}

/**
 * 扫描content
 */
function scanContentByexcel($p = 1){
    set_time_limit(0);
//    $info = getContentByPage($p);
    $list = array();
    $content_list = array();
//    $content_list = getCountentByExcel("content");
    $content_lists = getCountentByExcel("content_member");
    foreach ($content_lists as $k=>$v){
        $content_list[] = $v;
    }
    if($content_list){
        gocsv($content_list,'content');
    }else{
        die("nothing");
    }

}

function gocsv($content_list,$name){
    $list = array(
        0=>'content_id||a_href||a_text||img_src||status'
    );
    foreach ($content_list as $key=>$value){
        $a_href = @$value[1] ? $value[1] : '';
        $content_id = @$value[0] ? $value[0] : 0;
        $img_url = @$value[3] ? $value[3] : '';
        $a_text = @$value[2] ? $value[2] : ' ';
        $status = getHeaderInfo($a_href);
        if($status !== false){
            $list[] = $content_id."||".$a_href."||".$a_text."||||".$status;
        }
        $img_status = getHeaderInfo($img_url);
        if($img_status !== false){
            $list[] = $content_id."||||||".$img_url."||".$img_status;
        }
    }
    if($list){
        getCsvTest($list,'content_csv2',0);
    }
}




?>
