<?php

/**
 * ECSHOP ǰ̨�����ļ�
 * ============================================================================
 * * ��Ȩ���� 2005-2012 �Ϻ���������Ƽ����޹�˾������������Ȩ����
 * ��վ��ַ: http://www.ecshop.com��
 * ----------------------------------------------------------------------------
 * �ⲻ��һ��������������ֻ���ڲ�������ҵĿ�ĵ�ǰ���¶Գ����������޸ĺ�
 * ʹ�ã��������Գ���������κ���ʽ�κ�Ŀ�ĵ��ٷ�����
 * ============================================================================
 * $Author: liubo $
 * $Id: init.php 17217 2011-01-19 06:29:08Z liubo $
 */

if (!defined('IN_ECTOUCH'))
{
    die('Hacking attempt');
}

error_reporting(E_ALL);

if (__FILE__ == '') {
    die('Fatal error code: 0');
}
ob_start();
require(dirname(__FILE__) . '/../data/config.php');

if (!file_exists(ROOT_PATH . 'data/install.lock') && !file_exists(ROOT_PATH . 'include/install.lock') && !defined('NO_CHECK_INSTALL')) {
    header("Location: ./install/index.php\n");
    exit;
}

/* ��ʼ������ */
@ini_set('memory_limit', '640M');
@ini_set('session.cache_expire', 180);
@ini_set('session.use_trans_sid', 0);
@ini_set('session.use_cookies', 1);
@ini_set('session.auto_start', 0);
@ini_set('display_errors', 0);

if (DIRECTORY_SEPARATOR == '\\') {
    @ini_set('include_path', '.;' . ROOT_PATH);
} else {
    @ini_set('include_path', '.:' . ROOT_PATH);
}

if (defined('DEBUG_MODE') == false) {
    define('DEBUG_MODE', 2);
}

if (PHP_VERSION >= '5.1' && !empty($timezone)) {
    date_default_timezone_set($timezone);
}

$php_self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
if ('/' == substr($php_self, -1)) {
    $php_self .= 'index.php';
}
define('PHP_SELF', $php_self);

require(ROOT_PATH . 'include/inc_constant.php');
require(ROOT_PATH . 'include/cls_ecshop.php');
require(ROOT_PATH . 'include/cls_error.php');
require(ROOT_PATH . 'include/lib_time.php');
require(ROOT_PATH . 'include/lib_base.php');
require(ROOT_PATH . 'include/lib_common.php');
require(ROOT_PATH . 'include/lib_main.php');
require(ROOT_PATH . 'include/lib_insert.php');
require(ROOT_PATH . 'include/lib_goods.php');
require(ROOT_PATH . 'include/lib_article.php');
require(ROOT_PATH . 'include/cls_wechat.php');
/* ���û�����ı�������ת������� */
if (!get_magic_quotes_gpc()) {
    if (!empty($_GET)) {
        $_GET = addslashes_deep($_GET);
    }
    if (!empty($_POST)) {
        $_POST = addslashes_deep($_POST);
    }

    $_COOKIE = addslashes_deep($_COOKIE);
    $_REQUEST = addslashes_deep($_REQUEST);
}

/* ���� ECSHOP ���� */
$ecs = new ECS($db_name, $prefix);
define('DATA_DIR', $ecs->data_dir());
define('IMAGE_DIR', $ecs->image_dir());

/* ��ʼ�����ݿ��� */
require(ROOT_PATH . 'include/cls_mysql.php');
$db = new cls_mysql($db_host, $db_user, $db_pass, $db_name);
$db->set_disable_cache_tables(array($ecs->table('sessions'), $ecs->table('sessions_data'), $ecs->table('cart')));
$db_host = $db_user = $db_pass = $db_name = NULL;

/* �������������� */
$err = new ecs_error('message.dwt');

/* ����ϵͳ���� */
$_CFG = load_config();
$_CFG['URL_HTTP_HOST'] = $config['site_url'];

/* ���������ļ� */
require(ROOT_PATH . 'lang/' . $_CFG['lang'] . '/common.php');

if ($_CFG['shop_closed'] == 1) {
    /* �̵�ر��ˣ�����رյ���Ϣ */
    header('Content-type: text/html; charset=' . EC_CHARSET);

    die('<div style="margin: 150px; text-align: center; font-size: 14px"><p>' . $_LANG['shop_closed'] . '</p><p>' . $_CFG['close_comment'] . '</p></div>');
}

if (is_spider()) {
    /* �����֩��ķ��ʣ���ôĬ��Ϊ�ÿͷ�ʽ�����Ҳ���¼����־�� */
    if (!defined('INIT_NO_USERS')) {
        define('INIT_NO_USERS', true);
        /* ����UC�������֩����ʣ���ʼ��UC��Ҫ�ĳ��� */
        if ($_CFG['integrate_code'] == 'ucenter') {
            $user = & init_users();
        }
    }
    $_SESSION = array();
    $_SESSION['user_id'] = 0;
    $_SESSION['user_name'] = '';
    $_SESSION['email'] = '';
    $_SESSION['user_rank'] = 0;
    $_SESSION['discount'] = 1.00;
}

if (!defined('INIT_NO_USERS')) {
    /* ��ʼ��session */
    include(ROOT_PATH . 'include/cls_session.php');

    $sess = new cls_session($db, $ecs->table('sessions'), $ecs->table('sessions_data'));

    define('SESS_ID', $sess->get_session_id());
}
if (isset($_SERVER['PHP_SELF'])) {
    $_SERVER['PHP_SELF'] = htmlspecialchars($_SERVER['PHP_SELF']);
}
if (!defined('INIT_NO_SMARTY')) {
    header('Cache-control: private');
    header('Content-type: text/html; charset=' . EC_CHARSET);

    /* ���� Smarty ���� */
    require(ROOT_PATH . 'include/cls_template.php');
    $smarty = new cls_template;

    $smarty->cache_lifetime = $_CFG['cache_time'];
    $smarty->template_dir = ROOT_PATH . 'themes/' . $_CFG['template'];
    $smarty->cache_dir = ROOT_PATH . 'data/caches';
    $smarty->compile_dir = ROOT_PATH . 'data/compiled';

    if ((DEBUG_MODE & 2) == 2) {
        $smarty->direct_output = true;
        $smarty->force_compile = true;
    } else {
        $smarty->direct_output = false;
        $smarty->force_compile = false;
    }
    $smarty->direct_output = false;
    $smarty->force_compile = false;

    $smarty->assign('lang', $_LANG);
    $smarty->assign('ecs_charset', EC_CHARSET);
    if (!empty($_CFG['stylename'])) {
        $smarty->assign('ectouch_css', 'themes/' . $_CFG['template'] . '/style_' . $_CFG['stylename'] . '.css');
    } else {
        $smarty->assign('ectouch_css', 'themes/' . $_CFG['template'] . '/style.css');
    }
    $smarty->assign('ectouch_themes', 'themes/' . $_CFG['template']);
    $smarty->assign('site_url', $config['site_url']); //����/��β
}

if (!defined('INIT_NO_USERS')) {
    /* ��Ա��Ϣ */
    $user = & init_users();

    if (!isset($_SESSION['user_id'])) {
        /* ��ȡͶ��վ������� */
        $site_name = isset($_GET['from']) ? htmlspecialchars($_GET['from']) : addslashes($_LANG['self_site']);
        $from_ad = !empty($_GET['ad_id']) ? intval($_GET['ad_id']) : 0;

        $_SESSION['from_ad'] = $from_ad; // �û�����Ĺ��ID
        $_SESSION['referer'] = stripslashes($site_name); // �û���Դ

        unset($site_name);

        if (!defined('INGORE_VISIT_STATS')) {
            visit_stats();
        }
    }

    if (empty($_SESSION['user_id'])) {
        if ($user->get_cookie()) {
            /* �����Ա�Ѿ���¼���һ�û�л�û�Ա���ʻ��������Լ��Ż�ȯ */
            if ($_SESSION['user_id'] > 0) {
                update_user_info();
            }
        } else {
            $_SESSION['user_id'] = 0;
            $_SESSION['user_name'] = '';
            $_SESSION['email'] = '';
            $_SESSION['user_rank'] = 0;
            $_SESSION['discount'] = 1.00;
            if (!isset($_SESSION['login_fail'])) {
                $_SESSION['login_fail'] = 0;
            }
        }
    }

    /* �����Ƽ���Ա */
    if (isset($_GET['u'])) {
	
        set_affiliate();
    }
    /* �����Ƽ���Ա */
    if (isset($_GET['wxid'])) {
	
        set_affiliate();
    }

    /* session �����ڣ����cookie */
    if (!empty($_COOKIE['ECS']['user_id']) && !empty($_COOKIE['ECS']['password'])) {
        // �ҵ���cookie, ��֤cookie��Ϣ
        $sql = 'SELECT user_id, user_name, password ' .
                ' FROM ' . $ecs->table('users') .
                " WHERE user_id = '" . intval($_COOKIE['ECS']['user_id']) . "' AND password = '" . $_COOKIE['ECS']['password'] . "'";

        $row = $db->GetRow($sql);

        if (!$row) {
            // û���ҵ������¼
            $time = time() - 3600;
            setcookie("ECS[user_id]", '', $time, '/');
            setcookie("ECS[password]", '', $time, '/');
        } else {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['user_name'] = $row['user_name'];
            update_user_info();
        }
    }

    if (isset($smarty)) {
        $smarty->assign('ecs_session', $_SESSION);
    }
}

if ((DEBUG_MODE & 1) == 1) {
    error_reporting(E_ALL);
} else {
    error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));
}
if ((DEBUG_MODE & 4) == 4) {
    include(ROOT_PATH . 'include/lib.debug.php');
}

/* �ж��Ƿ�֧�� Gzip ģʽ */
if (!defined('INIT_NO_SMARTY') && gzip_enabled()) {
    ob_start('ob_gzhandler');
} else {
    ob_start();
}

//*20141208����100���ҿ�������*/
	if (isset($_GET['u']))
    {
		$u=$_GET['u'];
   
    }else{
		$u="";
		
	}
	
	$share_info=array();
	//��½�����
	if(!empty($_SESSION['user_id'])){	
		$user_id=$_SESSION['user_id'];
		$sql = "SELECT parent_id FROM ". $ecs->table('users') .  "where user_id ='$user_id'";
		$parent_id=$GLOBALS['db']->getOne($sql);
		//��½��Աû�ϼ�
		if(empty($parent_id)){
			if(isset($_GET['u'])){
				if($u== $user_id){
					$share_info=array();
				}else{
					$sql = "SELECT * FROM ".$GLOBALS['ecs']->table('users')." where user_id ='$u'";
					$user_info=$GLOBALS['db']->getRow($sql);
					$share_userid=$user_info['wxid'];
					$sql = "SELECT * FROM wxch_user where wxid ='$share_userid'";
					$share_info=$GLOBALS['db']->getRow($sql);
				}
			}
		}else{
			//��½��Ա���ϼ�
			$sql = "SELECT wxid FROM ". $ecs->table('users') .  "where user_id ='$parent_id'";
			$share_userid=$GLOBALS['db']->getOne($sql);	
			$sql = "SELECT * FROM wxch_user where wxid ='$share_userid'";
			$share_info=$GLOBALS['db']->getRow($sql);
		}
	
	}else{
		//û��½�����
		if(isset($_GET['u'])){
				$sql = "SELECT * FROM ".$GLOBALS['ecs']->table('users')." where user_id ='$u'";
				$user_info=$GLOBALS['db']->getRow($sql);
				$share_userid=$user_info['wxid'];
				$sql = "SELECT * FROM wxch_user where wxid ='$share_userid'";
				$share_info=$GLOBALS['db']->getRow($sql);
		}
	}
$wechat = new Wechat();
/*20141208����100���ҿ�������*/
/* ����Ƿ���΢����������� */
function is_wechat_browser(){
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    if (strpos($user_agent, 'MicroMessenger') === false){
      //echo '��΢���������ֹ���';
      return false;
    } else {
      //echo '΢�����������������';
      //preg_match('/.*?(MicroMessenger\/([0-9.]+))\s*/', $user_agent, $matches);
      //echo '<br>���΢�Ű汾��Ϊ:'.$matches[2];
      return true;
    }
}
?>