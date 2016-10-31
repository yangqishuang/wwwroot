<?php

/**
 * ECSHOP 会员中心
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: user.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
require(ROOT_PATH . 'includes/lib_weixintong.php');
/* 载入语言文件 */
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/user.php');
$user_id = $_SESSION['user_id'];
$action  = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'default';
$affiliate = unserialize($GLOBALS['_CFG']['affiliate']);
$smarty->assign('affiliate', $affiliate);
$back_act='';

// 不需要登录的操作或自己验证是否登录（如ajax处理）的act
$not_login_arr =
array('login','act_login','getAreaList','register','act_register','act_edit_password','get_password','send_pwd_email','send_pwd_sms','password', 'signin', 'add_tag', 
    'collect', 'return_to_cart', 'logout', 'email_list', 'validate_email', 'send_hash_mail', 'order_query', 'is_registered', 'check_email','clear_history','qpassword_name', 
    'get_passwd_question', 'check_answer', 'oath', 'oath_login','send_pwd_phonecode','register_step2','act_register_step2_post','upload_picture','get_wx_info');

/* 显示页面的action列表 */
$ui_arr = array('register', 'login', 'profile', 'order_list', 'order_detail', 'order_tracking', 'address_list', 'act_edit_address', 'collection_list',
'message_list', 'tag_list', 'get_password', 'reset_password', 'booking_list', 'add_booking', 'account_raply','agent_goods','delivery_order',
'account_deposit', 'account_log', 'account_detail', 'act_account', 'pay', 'default', 'bonus', 'group_buy', 'group_buy_detail', 'affiliate', 
    'comment_list','validate_email','track_packages', 'transform_points','qpassword_name', 'get_passwd_question', 'check_answer','follow_shop','cancel_order');

/*必须登录但无需审核可以访问的act**/

/* 未登录处理 */
if (empty($_SESSION['user_id']))
{
    if (!in_array($action, $not_login_arr))
    {
        if (in_array($action, $ui_arr))
        {
            /* 如果需要登录,并是显示页面的操作，记录当前操作，用于登录后跳转到相应操作
            if ($action == 'login')
            {
                if (isset($_REQUEST['back_act']))
                {
                    $back_act = trim($_REQUEST['back_act']);
                }
            }
            else
            {}*/
            if (!empty($_SERVER['QUERY_STRING']))
            {
                $back_act = 'user.php?' . strip_tags($_SERVER['QUERY_STRING']);
            }
            $action = 'login';
        }
        else
        {
            //未登录提交数据。非正常途径提交数据！
            die($_LANG['require_login']);
        }
    }
}

/* 如果是显示页面，对页面进行相应赋值 */
if (in_array($action, $ui_arr))
{
    assign_template();
    $position = assign_ur_here(0, $_LANG['user_center']);
    $smarty->assign('page_title', $position['title']); // 页面标题
    $smarty->assign('ur_here',    $position['ur_here']);
    $sql = "SELECT value FROM " . $ecs->table('touch_shop_config') . " WHERE id = 419";
    $row = $db->getRow($sql);
    $car_off = $row['value'];
    $smarty->assign('car_off',       $car_off);
    /* 是否显示积分兑换 */
    if (!empty($_CFG['points_rule']) && unserialize($_CFG['points_rule']))
    {
        $smarty->assign('show_transform_points',     1);
    }
    $smarty->assign('helps',      get_shop_help());        // 网店帮助
    $smarty->assign('data_dir',   DATA_DIR);   // 数据目录
    $smarty->assign('action',     $action);
    $smarty->assign('lang',       $_LANG);
}
$not_login_arr[] = 'send_mobile_code';
$not_login_arr[] = 'send_email_code';
$not_login_arr[] = 'check_mobile';
//手机验证码
function action_send_mobile_code ()
{

	// 获取全局变量
	$user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$user_id = $GLOBALS['user_id'];

	require_once (ROOT_PATH . 'includes/lib_validate_record.php');

	$mobile_phone = trim($_REQUEST['mobile_phone']);

	if(empty($mobile_phone))
	{
		exit("手机号不能为空");
		return;
	}
	else if(! is_mobile_phone($mobile_phone))
	{
		exit("手机号格式不正确");
		return;
	}
	else if(check_validate_record_exist($mobile_phone))
	{
		// 获取数据库中的验证记录
		$record = get_validate_record($mobile_phone);

		/**
		 * 检查是过了限制发送短信的时间
		*/
		$last_send_time = $record['last_send_time'];
		$expired_time = $record['expired_time'];
		$create_time = $record['create_time'];
		$count = $record['count'];

		// 每天每个手机号最多发送的验证码数量
		$max_sms_count = 10;
		// 发送最多验证码数量的限制时间，默认为24小时
		$max_sms_count_time = 60 * 60 * 24;

		if((time() - $last_send_time) < 60)
		{
			echo ("每60秒内只能发送一次短信验证码，请稍候重试");
			return;
		}
		else if(time() - $create_time < $max_sms_count_time && $record['count'] > $max_sms_count)
		{
			echo ("您发送验证码太过于频繁，请稍后重试！");
			return;
		}
		else
		{
			$count ++;
		}
	}

	require_once (ROOT_PATH . 'includes/lib_passport.php');

	// 设置为空
	$_SESSION['mobile_register'] = array();

	require_once (ROOT_PATH . 'sms/sms.php');

	// 生成6位短信验证码
	$mobile_code = rand_number(6);
	// 短信内容
	$content = sprintf($_LANG['mobile_code_template'], $GLOBALS['_CFG']['shop_name'], $mobile_code, $GLOBALS['_CFG']['shop_name']);

	// file_put_contents("D:/mobile_code.txt", $content."\n");

	/* 发送激活验证邮件 */
	// $result = true;
	$result = sendSMS($mobile_phone,$content);
	if($result)
	{

		if(! isset($count))
		{
			$ext_info = array(
					"count" => 1
			);
		}
		else
		{
			$ext_info = array(
					"count" => $count
			);
		}

		// 保存手机号码到SESSION中
		$_SESSION[VT_MOBILE_REGISTER] = $mobile_phone;
		// 保存验证信息
		save_validate_record($mobile_phone, $mobile_code, VT_MOBILE_REGISTER, time(), time() + 30 * 60, $ext_info);
		echo 'ok';
	}
	else
	{
		echo '短信验证码发送失败';
	}
}
//用户中心欢迎页
if ($action == 'default')
{
    include_once(ROOT_PATH .'includes/lib_clips.php');
    if ($rank = get_rank_info())
    {
        $smarty->assign('rank_name', sprintf($_LANG['your_level'], $rank['rank_name']));
        if (!empty($rank['next_rank_name']))
        {
            $smarty->assign('next_rank_name', sprintf($_LANG['next_level'], $rank['next_rank'] ,$rank['next_rank_name']));
        }
    }
	$info = get_user_default($user_id);
	
	$sql = "SELECT wxid FROM " .$GLOBALS['ecs']->table('users'). " WHERE user_id = '$user_id'";
    $wxid = $GLOBALS['db']->getOne($sql);
	if(!empty($wxid)){
		$weixinInfo = $GLOBALS['db']->getRow("SELECT nickname, headimgurl FROM wxch_user WHERE wxid = '$wxid'");
		$info['avatar'] = empty($weixinInfo['headimgurl']) ? '':$weixinInfo['headimgurl'];
		$info['username'] = empty($weixinInfo['nickname']) ? $info['username']:$weixinInfo['nickname'];
	}
    $smarty->assign('info',        $info);
    $smarty->assign('user_notice', $_CFG['user_notice']);
    $smarty->assign('prompt',      get_user_prompt($user_id));
    $smarty->assign('pagername','my');
    $smarty->display('user_clips.dwt');
}


//  第三方登录接口
elseif($action == 'oath')
{
	$type = empty($_REQUEST['type']) ?  '' : $_REQUEST['type'];
	
	include_once(ROOT_PATH . 'includes/website/jntoo.php');

	$c = &website($type);
	if($c)
	{
		if (empty($_REQUEST['callblock']))
		{
			if (empty($_REQUEST['callblock']) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
			{
				$back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? 'index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
			}
			else
			{
                           
				$back_act = 'user.php';
			}
		}
		else
		{
			$back_act = trim($_REQUEST['callblock']);
		}

		if($back_act[4] != ':') $back_act = $ecs->url().$back_act;
		$open = empty($_REQUEST['open']) ? 0 : intval($_REQUEST['open']);
		
		$url = $c->login($ecs->url().'user.php?act=oath_login&type='.$type.'&callblock='.urlencode($back_act).'&open='.$open);

		if(!$url)
		{
			show_message( $c->get_error() , '首页', $ecs->url() , 'error');
		}
		header('Location: '.$url);
	}
	else
	{
		show_message('服务器尚未注册该插件！' , '首页',$ecs->url() , 'error');
	}
}

//  处理第三方登录接口
elseif ($action == 'oath_login') {
    $type = empty($_REQUEST['type']) ? '' : $_REQUEST['type'];

    include_once(ROOT_PATH . 'includes/website/jntoo.php');
    $c = &website($type);

    if ($c) {
        $access = $c->getAccessToken();

        if (!$access) {
            show_message($c->get_error(), '首页', $ecs->url(), 'error');
        }

        $c->setAccessToken($access);
        $info = $c->getMessage();
        if($type =='renn' ){
            
             $info =  $info['response'];
             $info['user_id'] = $info['id'];
            
        }
        
        if (!$info) {
            show_message($c->get_error(), '首页', $ecs->url(), 'error', false);
        }
        if (!$info['user_id'] || !$info['user_id']) {

            show_message($c->get_error(), '首页', $ecs->url(), 'error', false);
        }
        $info_user_id = $type . '_' . $info['user_id']; //  加个标识！！！防止 其他的标识 一样  // 以后的ID 标识 将以这种形式 辨认
        $info['name'] = str_replace("'", "", $info['name']); // 过滤掉 逗号 不然出错  很难处理   不想去  搞什么编码的了
        if (!$info['user_id'])
            show_message($c->get_error(), '首页', $ecs->url(), 'error', false);


        $sql = 'SELECT user_name,password,aite_id FROM ' . $ecs->table('users') . ' WHERE aite_id = \'' . $info_user_id . '\'';

        $count = $db->getRow($sql);


        if (!$count) {   // 没有当前数据
            if ($user->check_user($info['name'])) {  // 重名处理
                $info['name'] = $info['name'] . '_' . $type . (rand(10000, 99999));
            }
            $user_pass = $user->compile_password(array('password' => $info['user_id']));
            $sql = 'INSERT INTO ' . $ecs->table('users') . '(user_name , password, aite_id , sex , reg_time , user_rank , is_validated) VALUES ' .
                    "('$info[name]' , '$user_pass' , '$info_user_id' , '$info[sex]' , '" . gmtime() . "' , '$info[rank_id]' , '1')";

            $db->query($sql);
        } else {
            $sql = '';
            if ($count['aite_id'] == $info['user_id']) {
                $sql = 'UPDATE ' . $ecs->table('users') . " SET aite_id = '$info_user_id' WHERE aite_id = '$count[aite_id]'";
                $db->query($sql);
            }
            if ($info['name'] != $count['user_name']) {   // 这段可删除
                if ($user->check_user($info['name'])) {  // 重名处理
                    $info['name'] = $info['name'] . '_' . $type . (rand() * 1000);
                }
                $sql = 'UPDATE ' . $ecs->table('users') . " SET user_name = '$info[name]' WHERE aite_id = '$info_user_id'";
                $db->query($sql);
            }
        }
        $user->set_session($info['name']);
        $user->set_cookie($info['name']);
        update_user_info();
        recalculate_price();

        if (!empty($_REQUEST['open'])) {
            die('<script>window.opener.window.location.reload(); window.close();</script>');
        } else {
            ecs_header('Location: ' . $_REQUEST['callblock']);
        }
    }
}

/* 显示会员注册界面 */

if ($action == 'register')

{

    if ((!isset($back_act)||empty($back_act)) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))

    {

        $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];

    }



    /* 取出注册扩展字段 */

    $sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 ORDER BY dis_order, id';

    $extend_info_list = $db->getAll($sql);

    $smarty->assign('extend_info_list', $extend_info_list);



    /* 验证码相关设置 */

    if ((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0)

    {

        $smarty->assign('enabled_captcha', 1);

        $smarty->assign('rand',            mt_rand());

    }

    

    /* 短信发送设置 by carson */

    if(intval($_CFG['sms_signin']) > 0){

        $smarty->assign('enabled_sms_signin', 1);

    }



    /* 密码提示问题 */

    $smarty->assign('passwd_questions', $_LANG['passwd_questions']);



    /* 增加是否关闭注册 */

    $smarty->assign('shop_reg_closed', $_CFG['shop_reg_closed']);

//    $smarty->assign('back_act', $back_act);

    $smarty->display('user_passport.dwt');

}



/* 注册会员的处理 */

elseif ($action == 'act_register')

{
    /* 增加是否关闭注册 */

    if ($_CFG['shop_reg_closed'])

    {

        $smarty->assign('action',     'register');

        $smarty->assign('shop_reg_closed', $_CFG['shop_reg_closed']);

        $smarty->display('user_passport.dwt');

    }

    else

    {

        include_once(ROOT_PATH . 'includes/lib_passport.php');



        //注册类型 by carson start

        $enabled_sms = intval($_POST['enabled_sms']);

        if($enabled_sms){

            $username = $other['mobile_phone'] = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';

            $password = isset($_POST['password']) ? trim($_POST['password']) : '';

            //$email    = $username .'@qq.com';

        }else{

            $username = isset($_POST['username']) ? trim($_POST['username']) : '';

            $password = isset($_POST['password']) ? trim($_POST['password']) : '';

            $email = isset($_POST['email']) ? trim($_POST['email']) : '';

        }

        //注册类型 by carson end



        $back_act = isset($_POST['back_act']) ? trim($_POST['back_act']) : '';



        if(empty($_POST['agreement']))

        {

            show_message($_LANG['passport_js']['agreement']);

        }

        if (strlen($username) < 3)

        {

            show_message($_LANG['passport_js']['username_shorter']);

        }



        if (strlen($password) < 6)

        {

            show_message($_LANG['passport_js']['password_shorter']);

        }



        if (strpos($password, ' ') > 0)

        {

            show_message($_LANG['passwd_balnk']);

        }



        // 验证码检查

        if ((intval($_CFG['captcha']) & CAPTCHA_REGISTER) && gd_version() > 0 && $enabled_sms <= 0)

        {

            if (empty($_POST['captcha']))

            {

                show_message($_LANG['invalid_captcha'], $_LANG['sign_up'], 'user.php?act=register', 'error');

            }



            // 检查验证码

            include_once('includes/cls_captcha.php');



            $validator = new captcha();

            if (!$validator->check_word($_POST['captcha']))

            {

                show_message($_LANG['invalid_captcha'], $_LANG['sign_up'], 'user.php?act=register', 'error');

            }

        }

        //录入注册信息
        check_register($username, $username, strval($_POST['mobile_code']), $password);
        $salt = substr(uniqid(rand()), -6);
        if(empty($salt)){
            $password = md5($password);
        }else{
            $password = md5(md5($password).$salt);
        }
        $time = time();
        $ip = real_ip();
        $sql = "insert into {$ecs->table('users')} (user_name,mobile_phone,password,reg_time,last_time,last_ip,ec_salt,froms)values('$username','$username','$password',$time,$time,'$ip','$salt','mobile')";
        $db->query($sql);
        $user_id = $db->insert_id();
        $_SESSION['user_id'] = $user_id;
        register_success($username);

            /*把新注册用户的扩展信息插入数据库*/

            $sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';   //读出所有自定义扩展字段的id

            $fields_arr = $db->getAll($sql);



            $extend_field_str = '';    //生成扩展字段的内容字符串

            foreach ($fields_arr AS $val)

            {

                $extend_field_index = 'extend_field' . $val['id'];

                if(!empty($_POST[$extend_field_index]))

                {

                    $temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];

                    $extend_field_str .= " ('" . $_SESSION['user_id'] . "', '" . $val['id'] . "', '" . compile_str($temp_field_content) . "'),";

                }

            }

            $extend_field_str = substr($extend_field_str, 0, -1);



            if ($extend_field_str)      //插入注册扩展数据

            {

                $sql = 'INSERT INTO '. $ecs->table('reg_extend_info') . ' (`user_id`, `reg_field_id`, `content`) VALUES' . $extend_field_str;

                $db->query($sql);

            }



            /* 写入密码提示问题和答案 */

            if (!empty($passwd_answer) && !empty($sel_question))

            {

                $sql = 'UPDATE ' . $ecs->table('users') . " SET `passwd_question`='$sel_question', `passwd_answer`='$passwd_answer'  WHERE `user_id`='" . $_SESSION['user_id'] . "'";

                $db->query($sql);

            }

            /* 判断是否需要自动发送注册邮件 */
            if ($GLOBALS['_CFG']['member_email_validate'] && $GLOBALS['_CFG']['send_verify_email'])
            {
                send_regiter_hash($_SESSION['user_id']);
            }
            $ucdata = empty($user->ucdata)? "" : $user->ucdata;
            /* add by www.53moban.com 新注册会员登录审核插件 begin */
            $user->logout();
            show_message("您已经注册成功,请完善信息","点击跳转","user.php?act=register_step2&user_id=$user_id");
            //show_message("您的会员已经注册成功但需要人工审核！","","index.php");
            /* add by www.53moban.com 新注册会员登录审核插件 end */
            //show_message(sprintf($_LANG['register_success'], $username . $ucdata), array($_LANG['back_up_page'], $_LANG['profile_lnk']), array($back_act, 'user.php'), 'info');

    }

}

elseif($action == 'register_step2'){
    assign_template();
    $user_id = empty(intval($_GET['user_id']))?empty($_SESSION['user_id'])?0:$_SESSION['user_id']:intval($_GET['user_id']);
    $smarty->assign('user_id',$user_id);
    if(empty($user_id)){
        show_message("会员编号不得为空", '前往注册页面' ,'/user.php?act=register' , 'error');
    }
    $sql = "select user_name,mobile_phone,real_name,dp_name from {$ecs->table('users')} where user_id=$user_id";
    $user_info = $db->getRow($sql);
    if(empty($user_info)){
        show_message("你还尚未注册成为会员，请注册", '前往注册页面' ,'user.php?act=register' , 'error');
    }
    if(!empty($user_info['real_name'])){
        show_message("你的注册信息已经完善，请登录个人中心进行查看", '前往登录页面' ,'user.php' , 'error');
    }
    $smarty->assign('dp_name',$user_info['dp_name']);
    /*$sql = "select add_time from {$ecs->table('users_vcode')} where mobile=$mobile";
    $add_time = $db->getOne($sql);
    if(empty($add_time)){
        show_message("你输入的手机号尚未通过第一步注册，正在前往用户注册页面", '前往注册页面' ,'/user.php?act=register' , 'error');
    }
    if(time()-$add_time>60*60){
        $sql = "delete from {$ecs->table('users_vcode')} where mobile=$mobile";
        $db->query($sql);
        show_message("你的注册信息已过期，请重新注册", '前往注册页面' ,'/user.php?act=register' , 'error');
    }*/
    $province_list = getAreaList(1);
    $gd_region_list = getAreaList(6);
    $area_list = getAreaList(77);
    $smarty->assign('gd_id',6);
    $smarty->assign('province',$province_list);
    $smarty->assign('city',$gd_region_list);
    $sql = "select region_id from {$ecs->table('region')} where region_name='深圳'";
    $smarty->assign('sz_id',77);
    $smarty->assign('area',$area_list);
    $smarty->assign('page_title','用户中心_一点到批发商城|酒类批发|酒水批发-www.zgjlpf.com');
    $smarty->assign('user_info',$user_info);
    $smarty->display('user_detail.dwt');
}

//上传营业执照
elseif($action == 'upload_picture'){
    $picture = $_FILES['picture'];
    if(empty($picture['name'])){
        exit(json_encode(array('error'=>'营业执照图片不能为空')));
    }
    $allow_file_types = array('image/jpg','image/jpeg','image/png','image/gif');
    $allow_file_size = 35*1024*1024;
    if(!in_array($picture['type'], $allow_file_types)){
        exit(json_encode(array('error'=>'上传的图片格式不合法')));
    }
    if($picture['size']>$allow_file_size){
        exit(json_encode(array('error'=>'上传的图片太大')));
    }
    include  './includes/cls_image.php';
    $Image = new cls_image();
    $time = time();
    switch ($picture['type']){
        case 'image/jpg':
        case 'image/jpeg':
            $upload_file = $time.'.jpg';
            break;
        case 'image/gif':
            $upload_file = $time.'.gif';
            break;
        default :
            $upload_file = $time.'.png';
    }
    if(($upload_file=$Image->upload_image($picture, 'user_pic', $upload_file))===false){
        exit(json_encode(array('error'=>$Image->error_msg)));
    }
    exit(json_encode(array('file'=>$upload_file)));
}


//提交完善信息
elseif($action == 'act_register_step2_post'){
    $dp_name = trim(htmlspecialchars(strval($_POST['dp_name'])));
    $real_name = trim(htmlspecialchars(strval($_POST['real_name'])));
    $country_id = intval($_POST['country_id']);
    $province_id = intval($_POST['province_id']);
    $city_id = intval($_POST['city_id']);
    $area_id = intval($_POST['area_id']);
    $address = trim(htmlspecialchars(strval($_POST['address'])));
    $time = time();
    $ip = real_ip();
    $user_id = intval($_POST['user_id']);
    $upload_file = trim(htmlspecialchars(strval($_POST['upload_pic'])));
    if(empty($user_id)){
        show_message("会员编号不得为空", '前往注册页面' ,'/user.php?act=register' , 'error');
    }
    $sql = "select user_name,real_name from {$ecs->table('users')} where user_id=$user_id";
    $user_info = $db->getRow($sql);
    if(empty($user_info)){
        show_message("你还尚未注册成为会员，请注册", '前往注册页面' ,'/user.php?act=register' , 'error');
    }
    if(!empty($user_info['real_name'])){
        show_message("你的注册信息已经完善，请登录个人中心进行查看", '前往登录页面' ,'/user.php' , 'error');
    }
    check_register2($dp_name,$real_name, $country_id, $province_id, $city_id, $area_id, $address,$upload_file);
    if(!empty($upload_file)){
        /*include  './includes/cls_image.php';
        $Image = new cls_image();*
        $upload_file = ROOT_PATH.$upload_file;
        /*if(($new_upload_file=$Image->make_thumb($upload_file, 300, 300))===false){
            exit(json_encode(array('error'=>$Image->error_msg)));
        }*/
        $upload_file = ROOT_PATH.$upload_file;
        if(is_file($upload_file)&&file_exists($upload_file)){
            $new_upload_dir = dirname(__DIR__).'/images/'.  date('Ym/',$time);
            if(!is_dir($new_upload_dir)){
                mkdir($new_upload_dir, 0777, true);
            }
            $upload_file_type = getimagesize($upload_file);
            switch ($upload_file_type[2]){
                case 1:
                    $new_upload_file = $new_upload_dir.$time.'.gif';
                    break;
                case 2:
                    $new_upload_file = $new_upload_dir.$time.'.jpg';
                    break;
                case 3:
                    $new_upload_file = $new_upload_dir.$time.'.png';
                    break;
            }
            copy($upload_file, $new_upload_file);
            $new_upload_file = str_replace(dirname(__DIR__).'/', '', $new_upload_file);
            unlink($upload_file);
        }else{
            $new_upload_file = '';
        }
    }else{
        $new_upload_file = '';
    }
    $sql = "update {$ecs->table('users')} set real_name='$real_name',country=$country_id,province=$province_id,"
    ."city=$city_id,district=$area_id,address='$address',picture='$new_upload_file' where user_id=$user_id";
    $db->query($sql);
    exit(json_encode(array('succ'=>1)));
}



/* 验证用户注册邮件 */
elseif ($action == 'validate_email')
{
    $hash = empty($_GET['hash']) ? '' : trim($_GET['hash']);
    if ($hash)
    {
        include_once(ROOT_PATH . 'includess/lib_passport.php');
        $id = register_hash('decode', $hash);
        if ($id > 0)
        {
            $sql = "UPDATE " . $ecs->table('users') . " SET is_validated = 1 WHERE user_id='$id'";
            $db->query($sql);
            $sql = 'SELECT user_name, email FROM ' . $ecs->table('users') . " WHERE user_id = '$id'";
            $row = $db->getRow($sql);
            show_message(sprintf($_LANG['validate_ok'], $row['user_name'], $row['email']),$_LANG['profile_lnk'], 'user.php');
        }
    }
    show_message($_LANG['validate_fail']);
}

//获取地区列表
elseif($_REQUEST['act'] == 'getAreaList'){
    $parent_id = intval($_GET['parent_id']);
    $area_list = getAreaList($parent_id);
    exit(json_encode($area_list));
}



/* 验证用户注册用户名是否可以注册 */

elseif ($action == 'is_registered')

{

    include_once(ROOT_PATH . 'includes/lib_passport.php');



    $username = trim($_GET['username']);

    $username = json_str_iconv($username);



    if ($user->check_user($username) || admin_registered($username))

    {

        echo 'false';

    }

    else

    {

        echo 'true';

    }

}



/* 验证用户邮箱地址是否被注册 */

elseif($action == 'check_email')

{

    $email = trim($_GET['email']);

    if ($user->check_email($email))

    {

        echo 'false';

    }

    else

    {

        echo 'ok';

    }

}
/* 用户登录界面 */
elseif ($action == 'login')
{
    if (empty($back_act)) {
        if (empty($back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
            $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
        } else {
            $back_act = 'user.php';
        }
    }

    /* 短信发送设置 by carson */
    if(intval($_CFG['sms_signin']) > 0){
        $smarty->assign('enabled_sms_signin', 1);
    }

    $captcha = intval($_CFG['captcha']);
    if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0 || (intval($_CFG['captcha']) & CAPTCHA_REGISTER))
    {
        $GLOBALS['smarty']->assign('enabled_captcha', 1);
        $GLOBALS['smarty']->assign('rand', mt_rand());
    }
    $smarty->assign('back_act', $back_act);
    $smarty->display('user_passport.dwt');
}

/* 处理会员的登录 */
elseif ($action == 'act_login')
{
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $back_act = isset($_POST['back_act']) ? trim($_POST['back_act']) : '';

    /* 关闭验证码 by wang
    $captcha = intval($_CFG['captcha']);
    if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
    {
        if (empty($_POST['captcha']))
        {
            show_message($_LANG['invalid_captcha'], $_LANG['relogin_lnk'], 'user.php', 'error');
        }

        // 检查验证码
        include_once('includes/cls_captcha.php');

        $validator = new captcha();
        $validator->session_word = 'captcha_login';
        if (!$validator->check_word($_POST['captcha']))
        {
            show_message($_LANG['invalid_captcha'], $_LANG['relogin_lnk'], 'user.php', 'error');
        }
    }
    */

    //用户名是邮箱格式 by wang
    if(is_email($username))
    {
        $sql ="select user_name from ".$ecs->table('users')." where email='".$username."'";
        $username_try = $db->getOne($sql);
        $username = $username_try ? $username_try:$username;
    }

    //用户名是手机格式 by wang
    if(is_mobile_phone($username))
    {
        $sql ="select user_name from ".$ecs->table('users')." where mobile_phone='".$username."'";
        $username_try = $db->getOne($sql);
        $username = $username_try ? $username_try:$username;
    }
    /* add by www.53moban.com 新注册会员登录审核插件 begin */
    $sql = "SELECT user_id,user_name,state,real_name FROM ".$ecs->table('users')." WHERE user_name = '".$username."'";
    $count = $db->getRow($sql);
    if($count)
    {
        if(!$count['real_name']){
            show_message("你的账号还未填写审核资料","点击前往填写资料",'user.php?act=register_step2&user_id='.$count['user_id'],'error');
        }
        if(!$count['state'])
        {
            show_message("你的账号还未通过审核，暂无法登录网站,");
        }
    }else{
        //找不到？？？去个人中心获取
        $user_info = getUserFromCenter(trim($_POST['username']));
        if(empty($user_info)){
            show_message('您登录的手机号还未注册，请注册', array('点击前往注册页面'), array('register.php?back_act=http://b.yidiandao.com/mobile_b2b/index.php'), 'info');
        }else{
            $info = array(
                'user_name'=>$user_info['mobile'],
                'password'=>$user_info['password'],
                'reg_time'=>empty(intval($user_info['reg_time']))?time():intval($user_info['reg_time']),
                'ec_salt'=>empty(strval($user_info['salt']))?'':strval($user_info['salt']),
                'mobile_phone'=>$user_info['mobile'],
                'address'=>$user_info['address'],
                'state'=>1,
                'froms'=>'mobile',
                'country'=>1,
                'province'=>0,
                'city'=>0,
                'district'=>0,
                'real_name'=>$user_info['real_name'],
                'picture'=>'',
                'dp_name'=>''
            );
            
            /***
             * 获取远程图片
             * **/
            if(!empty($user_info['picture'])){
                $pathinfo = pathinfo($user_info['picture']);
                $file_dir = ROOT_PATH_WAP.'images/'.date('Ym').'/';
                if(!is_dir($file_dir)){
                    mkdir($fields_arr, 0777, true);
                }
                $file_dir = $file_dir.time().'.'.$pathinfo['extension'];
                $ch = curl_init();
                if(DEV_SERVER){
                    $url = 'http://open.yidiandao.com/Data/Uploads/'.$user_info['picture'];
                }else{
                    $url = 'http://open.yidiandao.com/Data/Uploads/'.$user_info['picture'];
                }
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                $img = curl_exec($ch);
                $errno = curl_errno($ch);
                curl_close($ch);
                if(!$errno){
                    $fp = @fopen($file_dir, 'w+');
                    $fp = fwrite($fp, $img);
                    fclose($fp);
                    $info['picture'] = str_replace(ROOT_PATH_WAP, '', $file_dir);
                }
            }
            
            /***
             * 获取省，市，区的ID编码
             * **/
            if(!empty($user_info['area_info'])){
                $area_info = explode(' ', $user_info['area_info']);
                $area_info[0] = str_replace('市', '', $area_info[0]);
                $area_info[0] = str_replace('省', '', $area_info[0]);
                $area_info[1] = str_replace('市', '', $area_info[1]);
                $sql = "select region_id,region_name,region_type from {$ecs->table('region')} where region_name in('{$area_info[0]}','{$area_info[1]}') and region_type in(1,2)";
                $region_list1 = $db->getAll($sql);
                foreach($region_list1 as $value){
                    if($value['region_type']==1){
                        $info['province'] = $value['region_id'];
                    }else{
                        $info['city'] = $value['region_id'];
                    }
                }
                if(!empty($info['city'])){
                    $sql = "select region_id,region_name from {$ecs->table('region')} where parent_id={$info['city']} and region_type=3";
                    $region_list2 = $db->getAll($sql);
                    foreach($region_list2 as $value){
                        if(strpos($area_info[2], $value['region_name'])!==false){
                            $info['district'] = $value['region_id'];
                            $district_name = $value['region_name'];
                        }
                    }
                }
            }
            
            //判断店名是否重复，重复就加上XXXXX分店
            $sql = "select count(*) from {$ecs->table('users')} where dp_name='{$user_info['dp_name']}'";
            if(empty($db->getOne($sql))){
                $info['dp_name'] = $user_info['dp_name'];
            }else{
                $info['dp_name'] = $user_info['dp_name'].'('.$district_name.')';
            }
            
            //录入数据库
            $sql = "insert into {$ecs->table('users')} (user_name,password,ec_salt,reg_time,mobile_phone,"
            ."address,state,froms,country,province,city,district,picture,dp_name,real_name)values('{$info['user_name']}',"
            ."'{$info['password']}','{$info['ec_salt']}',{$info['reg_time']},'{$info['mobile_phone']}','{$info['address']}',"
            ."{$info['state']},'{$info['froms']}',{$info['country']},{$info['province']},{$info['city']},{$info['district']},"
            ."'{$info['picture']}','{$info['dp_name']}','{$info['real_name']}')";
            $db->query($sql);
            $user_id = $db->insert_id();
            $sql = "insert into {$ecs->table('user_address')} (user_id,consignee,country,province,city,district,address,tel,"
            ."mobile)values($user_id,'{$info['real_name']}',{$info['country']},{$info['province']},{$info['city']},{$info['district']},"
            ."'{$info['address']}','{$info['mobile_phone']}','{$info['mobile_phone']}')";
            $db->query($sql);
            $address_id = $db->insert_id();
            $sql = "update {$ecs->table('users')} set address_id=$address_id where user_id=$user_id";
            $db->query($sql);
            /* 注册送积分 */
            if(! empty($GLOBALS['_CFG']['register_points']))
            {
                    log_account_change($user_id, 0, 0, $GLOBALS['_CFG']['register_points'], $GLOBALS['_CFG']['register_points'], $GLOBALS['_LANG']['register_points']);
            }
            $now = gmtime();
            if($_CFG['bonus_reg_rand'])
            {
                    $sql_bonus_ext = " order by rand() limit 0,1";
            }
            $sql_b = "SELECT type_id FROM " . $ecs->table("bonus_type") . " WHERE send_type='" . SEND_BY_REGISTER . "'  AND send_start_date<=" . $now . " AND send_end_date>=" . $now . $sql_bonus_ext;
            $res_bonus = $db->query($sql_b);
            while($row_bonus = $db->fetchRow($res_bonus))
            {
                $sql = "INSERT INTO " . $ecs->table('user_bonus') . "(bonus_type_id, bonus_sn, user_id, used_time, order_id, emailed)" . " VALUES('" . $row_bonus['type_id'] . "', 0, '" . $_SESSION['user_id'] . "', 0, 0, 0)";
                $db->query($sql);
            }
        }
        //show_message('您登录的手机号还未注册，请注册', array('点击前往注册页面'), array('register.php?back_act=http://b.yidiandao.com/mobile/index.php'), 'info');
    }
    /* add by www.53moban.com 新注册会员登录审核插件 end */
    if ($user->login($username, $password,isset($_POST['remember'])))
    {
        update_user_info();
        recalculate_price();

        $ucdata = isset($user->ucdata)? $user->ucdata : '';
        show_message($_LANG['login_success'] . $ucdata , array($_LANG['back_up_page'], $_LANG['profile_lnk']), array($back_act,'user.php'), 'info');
    }
    else
    {
        $_SESSION['login_fail'] ++ ;
        show_message($_LANG['login_failure'], $_LANG['relogin_lnk'], 'user.php', 'error');
    }
}

/* 处理 ajax 的登录请求 */
elseif ($action == 'signin')
{
    include_once('includes/cls_json.php');
    $json = new JSON;

    $username = !empty($_POST['username']) ? json_str_iconv(trim($_POST['username'])) : '';
    $password = !empty($_POST['password']) ? trim($_POST['password']) : '';
    $captcha = !empty($_POST['captcha']) ? json_str_iconv(trim($_POST['captcha'])) : '';
    $result   = array('error' => 0, 'content' => '');

    $captcha = intval($_CFG['captcha']);
    if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
    {
        if (empty($captcha))
        {
            $result['error']   = 1;
            $result['content'] = $_LANG['invalid_captcha'];
            die($json->encode($result));
        }

        /* 检查验证码 */
        include_once('includes/cls_captcha.php');

        $validator = new captcha();
        $validator->session_word = 'captcha_login';
        if (!$validator->check_word($_POST['captcha']))
        {

            $result['error']   = 1;
            $result['content'] = $_LANG['invalid_captcha'];
            die($json->encode($result));
        }
    }

    if ($user->login($username, $password))
    {
        update_user_info();  //更新用户信息
        recalculate_price(); // 重新计算购物车中的商品价格
        $smarty->assign('user_info', get_user_info());
        $ucdata = empty($user->ucdata)? "" : $user->ucdata;
        $result['ucdata'] = $ucdata;
        $result['content'] = $smarty->fetch('library/member_info.lbi');
    }
    else
    {
        $_SESSION['login_fail']++;
        if ($_SESSION['login_fail'] > 2)
        {
            $smarty->assign('enabled_captcha', 1);
            $result['html'] = $smarty->fetch('library/member_info.lbi');
        }
        $result['error']   = 1;
        $result['content'] = $_LANG['login_failure'];
    }
    die($json->encode($result));
}

/* 退出会员中心 */
elseif ($action == 'logout')
{
    if ((!isset($back_act)|| empty($back_act)) && isset($GLOBALS['_SERVER']['HTTP_REFERER']))
    {
        $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'user.php') ? './index.php' : $GLOBALS['_SERVER']['HTTP_REFERER'];
    }

    $user->logout();
    $ucdata = empty($user->ucdata)? "" : $user->ucdata;
    show_message($_LANG['logout'] . $ucdata, array($_LANG['back_up_page'], $_LANG['back_home_lnk']), array($back_act, 'index.php'), 'info');
}

/* 个人资料页面 */
elseif ($action == 'profile')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');

    $user_info = get_profile($user_id);

    /* 取出注册扩展字段 */
    $sql = 'SELECT * FROM ' . $ecs->table('reg_fields') . ' WHERE type < 2 AND display = 1 ORDER BY dis_order, id';
    $extend_info_list = $db->getAll($sql);

    $sql = 'SELECT reg_field_id, content ' .
           'FROM ' . $ecs->table('reg_extend_info') .
           " WHERE user_id = $user_id";
    $extend_info_arr = $db->getAll($sql);

    $temp_arr = array();
    foreach ($extend_info_arr AS $val)
    {
        $temp_arr[$val['reg_field_id']] = $val['content'];
    }

    foreach ($extend_info_list AS $key => $val)
    {
        switch ($val['id'])
        {
            case 1:     $extend_info_list[$key]['content'] = $user_info['msn']; break;
            case 2:     $extend_info_list[$key]['content'] = $user_info['qq']; break;
            case 3:     $extend_info_list[$key]['content'] = $user_info['office_phone']; break;
            case 4:     $extend_info_list[$key]['content'] = $user_info['home_phone']; break;
            case 5:     $extend_info_list[$key]['content'] = $user_info['mobile_phone']; break;
            default:    $extend_info_list[$key]['content'] = empty($temp_arr[$val['id']]) ? '' : $temp_arr[$val['id']] ;
        }
    }

    $smarty->assign('extend_info_list', $extend_info_list);

    /* 密码提示问题 */
    $smarty->assign('passwd_questions', $_LANG['passwd_questions']);

    $smarty->assign('profile', $user_info);
    $smarty->display('user_transaction.dwt');
}

/* 修改个人资料的处理 */
elseif ($action == 'act_edit_profile')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');

    $birthday = trim($_POST['birthdayYear']) .'-'. trim($_POST['birthdayMonth']) .'-'.
    trim($_POST['birthdayDay']);
   // $email = trim($_POST['email']);
    $other['msn'] = $msn = isset($_POST['extend_field1']) ? trim($_POST['extend_field1']) : '';
    $other['qq'] = $qq = isset($_POST['extend_field2']) ? trim($_POST['extend_field2']) : '';
    $other['office_phone'] = $office_phone = isset($_POST['extend_field3']) ? trim($_POST['extend_field3']) : '';
    $other['home_phone'] = $home_phone = isset($_POST['extend_field4']) ? trim($_POST['extend_field4']) : '';
    $other['mobile_phone'] = $mobile_phone = isset($_POST['extend_field5']) ? trim($_POST['extend_field5']) : '';
    $sel_question = empty($_POST['sel_question']) ? '' : compile_str($_POST['sel_question']);
    $passwd_answer = isset($_POST['passwd_answer']) ? compile_str(trim($_POST['passwd_answer'])) : '';

    /* 更新用户扩展字段的数据 */
    $sql = 'SELECT id FROM ' . $ecs->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';   //读出所有扩展字段的id
    $fields_arr = $db->getAll($sql);

    foreach ($fields_arr AS $val)       //循环更新扩展用户信息
    {
        $extend_field_index = 'extend_field' . $val['id'];
        if(isset($_POST[$extend_field_index]))
        {
            $temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr(htmlspecialchars($_POST[$extend_field_index]), 0, 99) : htmlspecialchars($_POST[$extend_field_index]);
            $sql = 'SELECT * FROM ' . $ecs->table('reg_extend_info') . "  WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
            if ($db->getOne($sql))      //如果之前没有记录，则插入
            {
                $sql = 'UPDATE ' . $ecs->table('reg_extend_info') . " SET content = '$temp_field_content' WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
            }
            else
            {
                $sql = 'INSERT INTO '. $ecs->table('reg_extend_info') . " (`user_id`, `reg_field_id`, `content`) VALUES ('$user_id', '$val[id]', '$temp_field_content')";
            }
            $db->query($sql);
        }
    }

    /* 写入密码提示问题和答案 */
    if (!empty($passwd_answer) && !empty($sel_question))
    {
        $sql = 'UPDATE ' . $ecs->table('users') . " SET `passwd_question`='$sel_question', `passwd_answer`='$passwd_answer'  WHERE `user_id`='" . $_SESSION['user_id'] . "'";
        $db->query($sql);
    }

    if (!empty($office_phone) && !preg_match( '/^[\d|\_|\-|\s]+$/', $office_phone ) )
    {
        show_message($_LANG['passport_js']['office_phone_invalid']);
    }
    if (!empty($home_phone) && !preg_match( '/^[\d|\_|\-|\s]+$/', $home_phone) )
    {
         show_message($_LANG['passport_js']['home_phone_invalid']);
    }
   /* if (!is_email($email))
    {
        show_message($_LANG['msg_email_format']);
    }
    if (!empty($msn) && !is_email($msn))
    {
         show_message($_LANG['passport_js']['msn_invalid']);
    }
    if (!empty($qq) && !preg_match('/^\d+$/', $qq))
    {
         show_message($_LANG['passport_js']['qq_invalid']);
    }*/
    if (!empty($mobile_phone) && !preg_match('/^[\d-\s]+$/', $mobile_phone))
    {
        show_message($_LANG['passport_js']['mobile_phone_invalid']);
    }


    $profile  = array(
        'user_id'  => $user_id,
        'email'    => isset($_POST['email']) ? trim($_POST['email']) : '',
        'sex'      => isset($_POST['sex'])   ? intval($_POST['sex']) : 0,
        'birthday' => $birthday,
        'other'    => isset($other) ? $other : array()
        );


    if (edit_profile($profile))
    {
        show_message($_LANG['edit_profile_success'], $_LANG['profile_lnk'], 'user.php?act=profile', 'info');
    }
    else
    {
        if ($user->error == ERR_EMAIL_EXISTS)
        {
            $msg = sprintf($_LANG['email_exist'], $profile['email']);
        }
        else
        {
            $msg = $_LANG['edit_profile_failed'];
        }
        show_message($msg, '', '', 'info');
    }
}

/* 密码找回-->修改密码界面 */
elseif ($action == 'get_password')
{
    include_once(ROOT_PATH . 'includes/lib_passport.php');

    if (isset($_GET['code']) && isset($_GET['uid'])) //从邮件处获得的act
    {
        $code = trim($_GET['code']);
        $uid  = intval($_GET['uid']);

        /* 判断链接的合法性 */
        $user_info = $user->get_profile_by_id($uid);
        if (empty($user_info) || ($user_info && md5($user_info['user_id'] . $_CFG['hash_code'] . $user_info['reg_time']) != $code))
        {
            show_message($_LANG['parm_error'], $_LANG['back_home_lnk'], './', 'info');
        }

        $smarty->assign('uid',    $uid);
        $smarty->assign('code',   $code);
        $smarty->assign('action', 'reset_password');
        $smarty->display('user_passport.dwt');
    }
    else
    {
        /* 短信发送设置 by carson */
       // if(intval($_CFG['sms_signin']) > 0){
            $smarty->assign('enabled_sms_signin', 1);
       // }
        //显示用户名和email表单
        $smarty->display('user_passport.dwt');
    }
}

/* 密码找回-->输入用户名界面 */
elseif ($action == 'qpassword_name')
{
    //显示输入要找回密码的账号表单
    $smarty->display('user_passport.dwt');
}

/* 密码找回-->根据注册用户名取得密码提示问题界面 */
elseif ($action == 'get_passwd_question')
{
    if (empty($_POST['user_name']))
    {
        show_message($_LANG['no_passwd_question'], $_LANG['back_home_lnk'], './', 'info');
    }
    else
    {
        $user_name = trim($_POST['user_name']);
    }

    //取出会员密码问题和答案
    $sql = 'SELECT user_id, user_name, passwd_question, passwd_answer FROM ' . $ecs->table('users') . " WHERE user_name = '" . $user_name . "'";
    $user_question_arr = $db->getRow($sql);

    //如果没有设置密码问题，给出错误提示
    if (empty($user_question_arr['passwd_answer']))
    {
        show_message($_LANG['no_passwd_question'], $_LANG['back_home_lnk'], './', 'info');
    }

    $_SESSION['temp_user'] = $user_question_arr['user_id'];  //设置临时用户，不具有有效身份
    $_SESSION['temp_user_name'] = $user_question_arr['user_name'];  //设置临时用户，不具有有效身份
    $_SESSION['passwd_answer'] = $user_question_arr['passwd_answer'];   //存储密码问题答案，减少一次数据库访问

    $captcha = intval($_CFG['captcha']);
    if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
    {
        $GLOBALS['smarty']->assign('enabled_captcha', 1);
        $GLOBALS['smarty']->assign('rand', mt_rand());
    }

    $smarty->assign('passwd_question', $_LANG['passwd_questions'][$user_question_arr['passwd_question']]);
    $smarty->display('user_passport.dwt');
}

/* 密码找回-->根据提交的密码答案进行相应处理 */
elseif ($action == 'check_answer')
{
    $captcha = intval($_CFG['captcha']);
    if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
    {
        if (empty($_POST['captcha']))
        {
            show_message($_LANG['invalid_captcha'], $_LANG['back_retry_answer'], 'user.php?act=qpassword_name', 'error');
        }

        /* 检查验证码 */
        include_once('includes/cls_captcha.php');

        $validator = new captcha();
        $validator->session_word = 'captcha_login';
        if (!$validator->check_word($_POST['captcha']))
        {
            show_message($_LANG['invalid_captcha'], $_LANG['back_retry_answer'], 'user.php?act=qpassword_name', 'error');
        }
    }

    if (empty($_POST['passwd_answer']) || $_POST['passwd_answer'] != $_SESSION['passwd_answer'])
    {
        show_message($_LANG['wrong_passwd_answer'], $_LANG['back_retry_answer'], 'user.php?act=qpassword_name', 'info');
    }
    else
    {
        $_SESSION['user_id'] = $_SESSION['temp_user'];
        $_SESSION['user_name'] = $_SESSION['temp_user_name'];
        unset($_SESSION['temp_user']);
        unset($_SESSION['temp_user_name']);
        $smarty->assign('uid',    $_SESSION['user_id']);
        $smarty->assign('action', 'reset_password');
        $smarty->display('user_passport.dwt');
    }
}

/* 发送密码修改确认邮件 */
elseif ($action == 'send_pwd_email')
{
    include_once(ROOT_PATH . 'includes/lib_passport.php');

    /* 初始化会员用户名和邮件地址 */
    $user_name = !empty($_POST['user_name']) ? trim($_POST['user_name']) : '';
    $email     = !empty($_POST['email'])     ? trim($_POST['email'])     : '';

    //用户名和邮件地址是否匹配
    $user_info = $user->get_user_info($user_name);

    if ($user_info && $user_info['email'] == $email)
    {
        //生成code
         //$code = md5($user_info[0] . $user_info[1]);

        $code = md5($user_info['user_id'] . $_CFG['hash_code'] . $user_info['reg_time']);
        //发送邮件的函数
        if (send_pwd_email($user_info['user_id'], $user_name, $email, $code))
        {
            show_message($_LANG['send_success'] . $email, $_LANG['back_home_lnk'], './', 'info');
        }
        else
        {
            //发送邮件出错
            show_message($_LANG['fail_send_password'], $_LANG['back_page_up'], './', 'info');
        }
    }
    else
    {
        //用户名与邮件地址不匹配
        show_message($_LANG['username_no_email'], $_LANG['back_page_up'], '', 'info');
    }
}

elseif ($action == 'send_pwd_phonecode')
{
    include_once(ROOT_PATH . 'includess/lib_passport.php');

    /* 初始化会员用户名和邮件地址 */
    $mobile_code = !empty($_POST['mobile_code']) ? trim($_POST['mobile_code']) : '';
    $mobile_phone = !empty($_POST['mobile']) ? trim($_POST['mobile']) : exit();
	$u = $db->getRow("select user_id  FROM " . $ecs->table('users') . " WHERE mobile_phone =".$mobile_phone);

    $uid = $u['user_id'];
    $user_info = $user->get_profile_by_id($uid);
      if ($_POST)
    {
        //生成code
         //$code = md5($user_info[0] . $user_info[1]);

        $code = md5($user_info['user_id'] . $_CFG['hash_code'] . $user_info['reg_time']);
		 
		    $url = $GLOBALS['ecs']->url() . 'user.php?act=get_password&uid=' . $uid . '&code=' . $code;

        	 ecs_header("Location: $url\n");

    }
    else
    {
        
        show_message('验证码不正确', $_LANG['back_page_up'], '', 'info');
    }
}
/* 发送短信找回密码 */
elseif ($action == 'send_pwd_sms')
{
    include_once(ROOT_PATH . 'includes/lib_passport.php');

    /* 初始化会员手机 */
    $mobile = !empty($_POST['mobile']) ? trim($_POST['mobile']) : '';
    
    $sql = "SELECT user_id FROM " . $ecs->table('users') . " WHERE mobile_phone='$mobile'";
    $user_id = $db->getOne($sql);
    if ($user_id > 0)
    {
        //生成新密码
        $newPwd = random(6, 1);
        $message = "您的新密码是：" . $newPwd . "，请不要把密码泄露给其他人，如非本人操作，可不用理会！";
        includes(ROOT_PATH . 'includes/cls_sms.php');
        $sms = new sms();
        $sms_error = array();
        if ($sms->send($mobile, $message, $sms_error)) {
            $sql="UPDATE ".$ecs->table('users'). "SET `ec_salt`='0',password='". md5($newPwd) ."' WHERE mobile_phone= '".$mobile."'";
            $db->query($sql);
            show_message($_LANG['send_success_sms'] . $mobile, $_LANG['relogin_lnk'], './user.php', 'info');
        } else {
            //var_dump($sms_error);
            //发送邮件出错
            show_message($sms_error, $_LANG['back_page_up'], './', 'info');
        }
    }
    else
    {
        //不存在
        show_message($_LANG['username_no_mobile'], $_LANG['back_page_up'], '', 'info');
    }
}

/* 重置新密码 */
elseif ($action == 'reset_password')
{
    //显示重置密码的表单
    $smarty->display('user_passport.dwt');
}

/* 修改会员密码 */
elseif ($action == 'act_edit_password')
{
    include_once(ROOT_PATH . 'includes/lib_passport.php');

  
    $old_password = isset($_POST['old_password']) ? trim($_POST['old_password']) : null;
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $user_id      = isset($_POST['uid'])  ? intval($_POST['uid']) : $user_id;
    $code         = isset($_POST['code']) ? trim($_POST['code'])  : '';

    if (strlen($new_password) < 6)
    {
        show_message($_LANG['passport_js']['password_shorter']);
    }

    $user_info = $user->get_profile_by_id($user_id); //论坛记录

    if (($user_info && (!empty($code) && md5($user_info['user_id'] . $_CFG['hash_code'] . $user_info['reg_time']) == $code)) || ($_SESSION['user_id']>0 && $_SESSION['user_id'] == $user_id && $user->check_user($_SESSION['user_name'], $old_password)))
    {
		
        if ($user->edit_user(array('username'=> (empty($code) ? $_SESSION['user_name'] : $user_info['user_name']), 'old_password'=>$old_password, 'password'=>$new_password), empty($code) ? 0 : 1))
        {
			$sql="UPDATE ".$ecs->table('users'). "SET `ec_salt`='0',`mobile_phone`='".$user_info['user_name']."' WHERE user_id= '".$user_id."'";
			$db->query($sql);
                        $sql = "update {$ecs->table('users_token')} set token='',logout_time=".gmtime()." where user_id=$user_id";
            $db->query($sql);
            $sql = "select password,salt from {$GLOBALS['ecs']->table('agent')} where agent_id=$user_id";
            $agent_info = $GLOBALS['db']->getRow($sql);
            if(!empty($agent_info)){
                if(empty($agent_info['salt'])){
                    $password = md5(trim(htmlspecialchars($new_password)));
                }else{
                    $password = md5(md5(trim(htmlspecialchars($new_password))).strval($agent_info['salt']));
                }
                $sql = "update {$GLOBALS['ecs']->table('agent')} set password='$password' where agent_id=$user_id";
                $GLOBALS['db']->query($sql);
            }
            $user->logout();
            include '../includes/cls_sms.php';
            $Sms = new sms();
            $sql = "select mobile_phone from {$ecs->table('users')} where user_id=$user_id";
            $Sms->send($db->getOne($sql), '您的一点到商家密码已经修改，请牢记您的新密码。如有疑问请致电：400-930-1919，一点到竭诚为您服务！【一点到商家】');
            show_message($_LANG['edit_password_success'], $_LANG['relogin_lnk'], 'user.php?act=login', 'info');
        }
        else
        {
            show_message($_LANG['edit_password_failure'], $_LANG['back_page_up'], '', 'info');
        }
    }
    else
    {
        show_message($_LANG['edit_password_failure'], $_LANG['back_page_up'], '', 'info');
    }


}

/* 添加一个红包 */
elseif ($action == 'act_add_bonus')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');

    $bouns_sn = isset($_POST['bonus_sn']) ? intval($_POST['bonus_sn']) : '';

    if (add_bonus($user_id, $bouns_sn))
    {
        show_message($_LANG['add_bonus_sucess'], $_LANG['back_up_page'], 'user.php?act=bonus', 'info');
    }
    else
    {
        $err->show($_LANG['back_up_page'], 'user.php?act=bonus');
    }
}

/* 查看订单列表 */
elseif ($action == 'order_list')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
    $status=isset($_REQUEST['status']) ? intval($_REQUEST['status']) : 0;
    
   switch ($status) {
       case 1:
            $record_count = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('order_info'). " WHERE user_id = '$user_id' AND pay_status<2 AND order_status<4");
            break;
       case 2:
           $record_count = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('order_info'). " WHERE user_id = '$user_id' AND pay_status=2 AND order_status<4 ");
           break;
       case 3:
           $record_count = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('order_info'). " WHERE user_id = '$user_id' AND pay_status=2 AND (order_status=5 or order_status=6) AND (shipping_status=1 or shipping_status=4)");
           break;
       case 4:
           $record_count = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('order_info'). " WHERE user_id = '$user_id' AND order_status=5 AND shipping_status=2 OR pay_status=2 ");
           break;
       default:
           $record_count = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('order_info'). " WHERE user_id = '$user_id'");
       break;
   }
    
//    die($record_count);
    $pager  = get_pager('user.php', array('act' => $action), $record_count, $page);

  
    $orders = get_user_orders($user_id, $pager['size'], $pager['start'],$status);
    $merge  = get_user_merge($user_id);
    $smarty->assign('status',  $status);
    $smarty->assign('merge',  $merge);
    $smarty->assign('pager',  $pager);
    $smarty->assign('orders', $orders);
    $smarty->display('user_transaction.dwt');
}

/* 异步显示订单列表 by wang */
elseif ($action == 'async_order_list')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');
    
    $start = $_POST['last'];
    $limit = $_POST['amount'];
    
    $orders = get_user_orders($user_id, $limit, $start);
    if(is_array($orders)){
        foreach($orders as $vo){
            //获取订单第一个商品的图片
            $img = $db->getOne("SELECT g.goods_thumb FROM " .$ecs->table('order_goods'). " as og left join " .$ecs->table('goods'). " g on og.goods_id = g.goods_id WHERE og.order_id = ".$vo['order_id']." limit 1");
            $tracking = ($vo['shipping_id'] > 0) ? '<a href="user.php?act=order_tracking&order_id='.$vo['order_id'].'" class="c-btn3">订单跟踪</a>':'';
            $asyList[] = array(
                'order_status' => '订单状态：'.$vo['order_status'],
                'order_handler' => $vo['handler'],
                'order_content' => '<a href="user.php?act=order_detail&order_id='.$vo['order_id'].'"><table width="100%" border="0" cellpadding="5" cellspacing="0" class="ectouch_table_no_border">
            <tr>
                <td><img src="'.$config['site_url'].$img.'" width="50" height="50" /></td>
                <td>订单编号：'.$vo['order_sn'].'<br>
                订单金额：'.$vo['total_fee'].'<br>
                下单时间：'.$vo['order_time'].'</td>
                <td style="position:relative"><span class="new-arr"></span></td>
            </tr>
          </table></a>',
                'order_tracking' => $tracking
            );
        }
    }
    echo json_encode($asyList);
}

/* 包裹跟踪 by wang */
elseif ($action == 'order_tracking')
{
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    $ajax = isset($_GET['ajax']) ? intval($_GET['ajax']) : 0;
    
    include_once(ROOT_PATH . 'includes/lib_transaction.php');
    include_once(ROOT_PATH .'includes/lib_order.php');

    $sql = "SELECT order_id,order_sn,invoice_no,shipping_name,shipping_id FROM " .$ecs->table('order_info').
            " WHERE user_id = '$user_id' AND order_id = ".$order_id;
    $orders = $db->getRow($sql);
    //生成快递100查询接口链接
    $shipping   = get_shipping_object($orders['shipping_id']);
   
    //优先使用curl模式发送数据
    if (function_exists('curl_init') == 1){
      $curl = curl_init();
      curl_setopt ($curl, CURLOPT_URL, $query_link);
      curl_setopt ($curl, CURLOPT_HEADER,0);
      curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt ($curl, CURLOPT_USERAGENT,$_SERVER['HTTP_USER_AGENT']);
      curl_setopt ($curl, CURLOPT_TIMEOUT,5);
      $get_content = curl_exec($curl);
      curl_close ($curl);
    }
    
    $smarty->assign('trackinfo',      $get_content);
    $smarty->display('user_transaction.dwt');
}

/* 查看订单详情 */
elseif ($action == 'order_detail')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');
    include_once(ROOT_PATH . 'includes/lib_payment.php');
    include_once(ROOT_PATH . 'includes/lib_order.php');
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

    /* 订单详情 */
    $order = get_order_detail($order_id, $user_id);

    if ($order === false)
    {
        $err->show($_LANG['back_home_lnk'], './');

        exit;
    }

    /* 是否显示添加到购物车 */
    if ($order['extension_code'] != 'group_buy' && $order['extension_code'] != 'exchange_goods')
    {
        $smarty->assign('allow_to_cart', 1);
    }

    /* 订单商品 */
    $goods_list = order_goods($order_id);
    foreach ($goods_list AS $key => $value)
    {
        $goods_list[$key]['market_price'] = price_format($value['market_price'], false);
        $goods_list[$key]['goods_price']  = price_format($value['goods_price'], false);
        $goods_list[$key]['subtotal']     = price_format($value['subtotal'], false);
    }

     /* 设置能否修改使用余额数 */
    if ($order['order_amount'] > 0)
    {
        if ($order['order_status'] == OS_UNCONFIRMED || $order['order_status'] == OS_CONFIRMED)
        {
            $user = user_info($order['user_id']);
            if ($user['user_money'] + $user['credit_line'] > 0)
            {
                $smarty->assign('allow_edit_surplus', 1);
                $smarty->assign('max_surplus', sprintf($_LANG['max_surplus'], $user['user_money']));
            }
        }
    }

    /* 未发货，未付款时允许更换支付方式 */
    if ($order['order_amount'] > 0 && $order['pay_status'] == PS_UNPAYED && $order['shipping_status'] == SS_UNSHIPPED)
    {
		 $payment = payment_info($order['pay_id']);

        include_once('includes/modules/payment/' . $payment['pay_code'] . '.php');

        $pay_obj    = new $payment['pay_code'];

        $pay_online = $pay_obj->get_code($order, unserialize_config($payment['pay_config']));
		
		$smarty->assign('pay_online', $pay_online);
		
	
		$payment = payment_info($order['pay_id']);
// 		die($payment['pay_code']);
		include_once('includes/modules/payment/' . $payment['pay_code'] . '.php');
		$pay_obj    = new $payment['pay_code'];
		$pay_online = $pay_obj->get_code($order,unserialize_config($payment['pay_config']));
		$smarty->assign('pay_online', $pay_online);
		
        $payment_list = available_payment_list(false, 0, true);

        /* 过滤掉当前支付方式和余额支付方式 */
        if(is_array($payment_list))
        {
            foreach ($payment_list as $key => $payment)
            {
                if ($payment['pay_id'] == $order['pay_id'] || $payment['pay_code'] == 'balance')
                {
                    unset($payment_list[$key]);
                }
            }
        }
        $smarty->assign('payment_list', $payment_list);
    }

    /* 订单 支付 配送 状态语言项 */
    $order['order_status'] = $_LANG['os'][$order['order_status']];
    $order['pay_status'] = $_LANG['ps'][$order['pay_status']];
    $order['shipping_status'] = $_LANG['ss'][$order['shipping_status']];
    /*获取订单配送地址省、市*/
    $order_address['province_name']=get_regions_order(1,$order['province']);
    $order_address['city_name']=get_regions_order(2,$order['city']);
    $order_address['district_name']=get_regions_order(3,$order['district']);
    $order['province_name']=$order_address['province_name'][0][region_name];
    $order['city_name']=$order_address['city_name'][0][region_name];
    $order['district_name']=$order_address['district_name'][0][region_name];
    $smarty->assign('order',      $order);
    $smarty->assign('goods_list', $goods_list);
    $smarty->display('user_transaction.dwt');
}

/* 取消订单 */
elseif ($action == 'cancel_order')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');
    include_once(ROOT_PATH . 'includes/lib_order.php');
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    $order = order_info($order_id);
    $smarty->assign('order',$order);
    $smarty->display('cancel_order.dwt');
}

elseif($action == 'cancel_order_post'){
    include_once(ROOT_PATH . 'includes/lib_transaction.php');
    include_once(ROOT_PATH . 'includes/lib_order.php');
    $order_id = empty(intval($_POST['order_id']))?0:intval($_POST['order_id']);
    $reason = trim(htmlspecialchars(strval($_POST['reason_select'])));
    if(empty($reason)){
        $reason = trim(htmlspecialchars(strval($_POST['reason'])));
    }
    if(empty($order_id)||empty($reason)){
        show_message('订单取消失败');
    }
    if (cancel_order($order_id, $user_id, $reason))
    {
        show_message('订单取消成功','返回订单列表','user.php?act=order_list');
        //ecs_header("Location: user.php?act=order_list\n");
        exit;
    }
    else
    {
        $err->show($_LANG['order_list_lnk'], 'user.php?act=order_list');
    }
}

/* 收货地址列表界面*/
elseif ($action == 'address_list')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');
    include_once(ROOT_PATH . 'lang/' .$_CFG['lang']. '/shopping_flow.php');
    $smarty->assign('lang',  $_LANG);

    /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
    $smarty->assign('country_list',       get_regions());
    $smarty->assign('shop_province_list', get_regions(1, $_CFG['shop_country']));

    /* 获得用户所有的收货人信息 */
    $consignee_list = get_consignee_list($_SESSION['user_id']);

    if (count($consignee_list) < 5 && $_SESSION['user_id'] > 0)
    {
        /* 如果用户收货人信息的总数小于5 则增加一个新的收货人信息 by wang */
        //$consignee_list[] = array('country' => $_CFG['shop_country'], 'email' => isset($_SESSION['email']) ? $_SESSION['email'] : '');
    }

    $smarty->assign('consignee_list', $consignee_list);

    //取得国家列表，如果有收货人列表，取得省市区列表
    foreach ($consignee_list AS $region_id => $consignee)
    {
        $consignee['country']  = isset($consignee['country'])  ? intval($consignee['country'])  : 0;
        $consignee['province'] = isset($consignee['province']) ? intval($consignee['province']) : 0;
        $consignee['city']     = isset($consignee['city'])     ? intval($consignee['city'])     : 0;

        $province_list[$region_id] = get_regions(1, $consignee['country']);
        $city_list[$region_id]     = get_regions(2, $consignee['province']);
        $district_list[$region_id] = get_regions(3, $consignee['city']);
    }

    /* 获取默认收货ID */
    $address_id  = $db->getOne("SELECT address_id FROM " .$ecs->table('users'). " WHERE user_id='$user_id'");

    //赋值于模板
    $smarty->assign('real_goods_count', 1);
    $smarty->assign('shop_country',     $_CFG['shop_country']);
    $smarty->assign('shop_province',    get_regions(1, $_CFG['shop_country']));
    $smarty->assign('province_list',    $province_list);
    $smarty->assign('address',          $address_id);
    $smarty->assign('city_list',        $city_list);
    $smarty->assign('district_list',    $district_list);
    $smarty->assign('currency_format',  $_CFG['currency_format']);
    $smarty->assign('integral_scale',   $_CFG['integral_scale']);
    $smarty->assign('name_of_region',   array($_CFG['name_of_region_1'], $_CFG['name_of_region_2'], $_CFG['name_of_region_3'], $_CFG['name_of_region_4']));

    $smarty->display('user_transaction.dwt');
}
/* 添加/编辑收货地址的处理 */
elseif ($action == 'address')
{
   /* include_once(ROOT_PATH . 'includes/lib_transaction.php');
    include_once(ROOT_PATH . 'lang/' .$_CFG['lang']. '/shopping_flow.php');
    $smarty->assign('lang', $_LANG);
    if($_GET['flag'] == 'display'){
        $id = intval($_GET['id']);
        
        /* 取得国家列表、商店所在国家、商店所在国家的省列表 *//*
        $smarty->assign('country_list',       get_regions());
        $smarty->assign('shop_province_list', get_regions(1, $_CFG['shop_country']));

        /* 获得用户所有的收货人信息 *//*
        $consignee_list = get_consignee_list($_SESSION['user_id']);

        foreach ($consignee_list AS $region_id => $vo)
        {
            if($vo['address_id'] == $id){
                $consignee = $vo;
                $smarty->assign('consignee', $vo);                
            }
        }
        $province_list = get_regions(1, 1);
        $city_list     = get_regions(2, $consignee['province']);
        $district_list = get_regions(3, $consignee['city']);
 
        $smarty->assign('province_list',    $province_list);
        $smarty->assign('city_list',        $city_list);
        $smarty->assign('district_list',    $district_list);
        
        $smarty->display('user_transaction.dwt');
        
    }else{
        $consignee = get_consignee_by_id(0);*/
          /* 取得国家列表、商店所在国家、商店所在国家的省列表 *//*
        $smarty->assign('country_list',       get_regions());
        $smarty->assign('shop_province_list', get_regions(1, $_CFG['shop_country']));
         $province_list = get_regions(1, 1);
        $city_list     = get_regions(2, $consignee['province']);
        $district_list = get_regions(3, $consignee['city']);
        
         $smarty->assign('consignee', $consignee);
        $smarty->assign('province_list',    $province_list);
        $smarty->assign('city_list',        $city_list);
        $smarty->assign('district_list',    $district_list);
       
        $smarty->display('user_transaction.dwt');
        return false;
    }
    

    $address = array(
        'user_id'    => $user_id,
        'address_id' => intval($_POST['address_id']),
        'country'    => isset($_POST['country'])   ? intval($_POST['country'])  : 0,
        'province'   => isset($_POST['province'])  ? intval($_POST['province']) : 0,
        'city'       => isset($_POST['city'])      ? intval($_POST['city'])     : 0,
        'district'   => isset($_POST['district'])  ? intval($_POST['district']) : 0,
        'address'    => isset($_POST['address'])   ? compile_str(trim($_POST['address']))    : '',
        'consignee'  => isset($_POST['consignee']) ? compile_str(trim($_POST['consignee']))  : '',
        'email'      => isset($_POST['email'])     ? compile_str(trim($_POST['email']))      : '',
        'tel'        => isset($_POST['tel'])       ? compile_str(make_semiangle(trim($_POST['tel']))) : '',
        'mobile'     => isset($_POST['mobile'])    ? compile_str(make_semiangle(trim($_POST['mobile']))) : '',
        'best_time'  => isset($_POST['best_time']) ? compile_str(trim($_POST['best_time']))  : '',
        'sign_building' => isset($_POST['sign_building']) ? compile_str(trim($_POST['sign_building'])) : '',
        'zipcode'       => isset($_POST['zipcode'])       ? compile_str(make_semiangle(trim($_POST['zipcode']))) : '',
        );

    if (update_address($address))
    {
        show_message($_LANG['edit_address_success'], $_LANG['address_list_lnk'], 'user.php?act=address_list');
    }*/
    $user = $GLOBALS['user'];
	$_CFG = $GLOBALS['_CFG'];
	$_LANG = $GLOBALS['_LANG'];
	$smarty = $GLOBALS['smarty'];
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
        $address_id = empty($_REQUEST['address_id'])?0:intval($_REQUEST['address_id']);
	include_once (ROOT_PATH . 'includes/lib_transaction.php');
	include_once (ROOT_PATH . 'languages/' . $_CFG['lang'] . '/shopping_flow.php');
	$smarty->assign('lang', $_LANG);
	
	/* 取得国家列表、商店所在国家、商店所在国家的省列表 */
	$smarty->assign('country_list', get_regions());
	$smarty->assign('shop_province_list', get_regions(1, $_CFG['shop_country']));
	
	/* 获得用户所有的收货人信息 */
        
	$consignee = get_consignee_by_id($address_id);
	
	$smarty->assign('consignee', $consignee);

	// 取得国家列表，如果有收货人列表，取得省市区列表
                                 
		$consignee['country'] = isset($consignee['country']) ? intval($consignee['country']) : 1;
		$consignee['province'] = isset($consignee['province']) ? intval($consignee['province']) : -1;
		$consignee['city'] = isset($consignee['city']) ? intval($consignee['city']) : -1;
                $consignee['district'] = isset($consignee['district']) ? intval($consignee['district']) : -1;
                $province_list = get_regions_wap($consignee['country']);
		$city_list = get_regions_wap($consignee['province']);
		$district_list = get_regions_wap($consignee['city']);
                $xiangcun_list = get_regions_wap($consignee['district']);

	// 赋值于模板
	$smarty->assign('real_goods_count', 1);
	$smarty->assign('shop_country', $_CFG['shop_country']);
	$smarty->assign('shop_province', get_regions(1, $_CFG['shop_country']));
	$smarty->assign('province_list', $province_list);
	$smarty->assign('city_list', $city_list);
	$smarty->assign('district_list', $district_list);
        $smarty->assign('xiangcun_list', $xiangcun_list);
        $smarty->assign('address_id',$address_id);
	$smarty->assign('currency_format', $_CFG['currency_format']);
	$smarty->assign('integral_scale', $_CFG['integral_scale']);
	$smarty->assign('name_of_region', array(
	$_CFG['name_of_region_1'],$_CFG['name_of_region_2'],$_CFG['name_of_region_3'],$_CFG['name_of_region_4']
	));
	
	$smarty->display('user_transaction.dwt');
}

/* 删除收货地址 */
elseif ($action == 'drop_consignee')
{
    include_once('includes/lib_transaction.php');

    $consignee_id = intval($_GET['id']);

    if (drop_consignee($consignee_id))
    {
        ecs_header("Location: user.php?act=address_list\n");
        exit;
    }
    else
    {
        show_message($_LANG['del_address_false']);
    }
}

/* 显示收藏商品列表 */
elseif ($action == 'collection_list')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    $record_count = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('collect_goods').
                                " WHERE user_id='$user_id' ORDER BY add_time DESC");

    $pager = get_pager('user.php', array('act' => $action), $record_count, $page);
    $smarty->assign('pager', $pager);
    $smarty->assign('goods_list', get_collection_goods($user_id, $pager['size'], $pager['start']));
    $smarty->assign('url',        $ecs->url());
    $lang_list = array(
        'UTF8'   => $_LANG['charset']['utf8'],
        'GB2312' => $_LANG['charset']['zh_cn'],
        'BIG5'   => $_LANG['charset']['zh_tw'],
    );
    $smarty->assign('lang_list',  $lang_list);
    $smarty->assign('user_id',  $user_id);
    $smarty->display('user_clips.dwt');
}

/* 异步获取收藏 by wang */
elseif ($action == 'async_collection_list'){
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $start = $_POST['last'];
    $limit = $_POST['amount'];
    
    $collections = get_collection_goods($user_id, $limit, $start);
    if(is_array($collections)){
        foreach($collections as $vo){
            $img = $db->getOne("SELECT goods_thumb FROM " .$ecs->table('goods'). " WHERE goods_id = ".$vo['goods_id']);
            $t_price = (empty($vo['promote_price']))? $_LANG['shop_price'].$vo['shop_price']:$_LANG['promote_price'].$vo['promote_price'];
            
            $asyList[] = array(
                'collection' => '<a href="'.$vo['url'].'"><table width="100%" border="0" cellpadding="5" cellspacing="0" class="ectouch_table_no_border">
            <tr>
                <td><img src="'.$config['site_url'].$img.'" width="50" height="50" /></td>
                <td>'.$vo['goods_name'].'<br>'.$t_price.'</td>
                <td align="right"><a href="'.$vo['url'].'" style="color:#1CA2E1">'.$_LANG['add_to_cart'].'</a><br><a href="javascript:if (confirm(\''.$_LANG['remove_collection_confirm'].'\')) location.href=\'user.php?act=delete_collection&collection_id='.$vo['rec_id'].'\'" style="color:#1CA2E1">'.$_LANG['drop'].'</a></td>
            </tr>
          </table></a>'
            );
        }
    }
    echo json_encode($asyList);
}

/* 删除收藏的商品 */
elseif ($action == 'delete_collection')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    //$collection_id = isset($_GET['collection_id']) ? intval($_GET['collection_id']) : 0;
    $collection_id = !empty($_GET['collection_id'])?strval($_GET['collection_id']):0;
    if(!empty($collection_id)){
        $db->query('DELETE FROM ' .$ecs->table('collect_goods'). " WHERE rec_id in($collection_id) AND user_id ='$user_id'" );
    }

    ecs_header("Location: user.php?act=collection_list\n");
    exit;
}

/* 添加关注商品 */
elseif ($action == 'add_to_attention')
{
    $rec_id = (int)$_GET['rec_id'];
    if ($rec_id)
    {
        $db->query('UPDATE ' .$ecs->table('collect_goods'). "SET is_attention = 1 WHERE rec_id='$rec_id' AND user_id ='$user_id'" );
    }
    ecs_header("Location: user.php?act=collection_list\n");
    exit;
}
/* 取消关注商品 */
elseif ($action == 'del_attention')
{
    $rec_id = (int)$_GET['rec_id'];
    if ($rec_id)
    {
        $db->query('UPDATE ' .$ecs->table('collect_goods'). "SET is_attention = 0 WHERE rec_id='$rec_id' AND user_id ='$user_id'" );
    }
    ecs_header("Location: user.php?act=collection_list\n");
    exit;
}
/* 显示留言列表 */
elseif ($action == 'message_list')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    $order_id = empty($_GET['order_id']) ? 0 : intval($_GET['order_id']);
    $order_info = array();

    /* 获取用户留言的数量 */
    if ($order_id)
    {
        $sql = "SELECT COUNT(*) FROM " .$ecs->table('feedback').
                " WHERE parent_id = 0 AND order_id = '$order_id' AND user_id = '$user_id'";
        $order_info = $db->getRow("SELECT * FROM " . $ecs->table('order_info') . " WHERE order_id = '$order_id' AND user_id = '$user_id'");
        $order_info['url'] = 'user.php?act=order_detail&order_id=' . $order_id;
    }
    else
    {
        $sql = "SELECT COUNT(*) FROM " .$ecs->table('feedback').
           " WHERE parent_id = 0 AND user_id = '$user_id' AND user_name = '" . $_SESSION['user_name'] . "' AND order_id=0";
    }

    $record_count = $db->getOne($sql);
    $act = array('act' => $action);

    if ($order_id != '')
    {
        $act['order_id'] = $order_id;
    }

    $pager = get_pager('user.php', $act, $record_count, $page, 5);
    $smarty->assign('message_list', get_message_list($user_id, $_SESSION['user_name'], $pager['size'], $pager['start'], $order_id));
    $smarty->assign('pager',        $pager);
    $smarty->assign('order_info',   $order_info);
    $smarty->display('user_clips.dwt');
}

/* 异步获取留言 */
elseif ($action == 'async_message_list'){
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $order_id = empty($_GET['order_id']) ? 0 : intval($_GET['order_id']);
    $start = $_POST['last'];
    $limit = $_POST['amount'];
    
    $message_list = get_message_list($user_id, $_SESSION['user_name'], $limit, $start, $order_id);
    if(is_array($message_list)){
        foreach($message_list as $key=>$vo){
            $re_message = $vo['re_msg_content'] ? '<tr><td>'.$_LANG['shopman_reply'].' ('.$vo['re_msg_time'].')<br>'.$vo['re_msg_content'].'</td></tr>':'';
            $asyList[] = array(
                'message' => '<table width="100%" border="0" cellpadding="5" cellspacing="0" class="ectouch_table_no_border">
            <tr>
                <td><span style="float:right"><a href="user.php?act=del_msg&id='.$key.'&order_id='.$vo['order_id'].'" onclick="if (!confirm(\''.$_LANG['confirm_remove_msg'].'\')) return false;" style="color:#1CA2E1">删除</a></span>'.$vo['msg_type'].'：'.$vo['msg_title'].' - '.$vo['msg_time'].' </td>
            </tr>
            <tr>
                <td>'.$vo['msg_content'].'</td>
            </tr>'.$re_message.'
            
          </table>'
            );
        }
    }
    echo json_encode($asyList);
}

/* 显示评论列表 */
elseif ($action == 'comment_list')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    /* 获取用户留言的数量 */
    $sql = "SELECT COUNT(*) FROM " .$ecs->table('comment').
           " WHERE parent_id = 0 AND user_id = '$user_id'";
    $record_count = $db->getOne($sql);
    $pager = get_pager('user.php', array('act' => $action), $record_count, $page, 5);

    $smarty->assign('comment_list', get_comment_list($user_id, $pager['size'], $pager['start']));
    $smarty->assign('pager',        $pager);
    $smarty->display('user_clips.dwt');
}


/* 异步获取评论 */
elseif ($action == 'async_comment_list'){
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $start = $_POST['last'];
    $limit = $_POST['amount'];
    
    $comment_list = get_comment_list($user_id, $limit, $start);
    if(is_array($comment_list)){
        foreach($comment_list as $key=>$vo){
            $re_message = $vo['reply_content'] ? '<tr><td>'.$_LANG['reply_comment'].'<br>'.$vo['reply_content'].'</td></tr>':'';
            $asyList[] = array(
                'comment' => '<table width="100%" border="0" cellpadding="5" cellspacing="0" class="ectouch_table_no_border">
            <tr>
                <td><span style="float:right"><a href="user.php?act=del_cmt&id='.$vo['comment_id'].'" onclick="if (!confirm(\''.$_LANG['confirm_remove_msg'].'\')) return false;" style="color:#1CA2E1">删除</a></span>评论：'.$vo['cmt_name'].' - '.$vo['formated_add_time'].' </td>
            </tr>
            <tr>
                <td>'.$vo['content'].'</td>
            </tr>'.$re_message.'
          </table>'
            );
        }
    }
    echo json_encode($asyList);
}

/* 添加我的留言 */
elseif ($action == 'act_add_message')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $message = array(
        'user_id'     => $user_id,
        'user_name'   => $_SESSION['user_name'],
        'msg_type'    => isset($_POST['msg_type']) ? intval($_POST['msg_type'])     : 0,
        'msg_content' => isset($_POST['msg_content']) ? trim($_POST['msg_content']) : '',
        'order_id'=>empty($_POST['order_id']) ? 0 : intval($_POST['order_id']),
        'upload'      => (isset($_FILES['message_img']['error']) && $_FILES['message_img']['error'] == 0) || (!isset($_FILES['message_img']['error']) && isset($_FILES['message_img']['tmp_name']) && $_FILES['message_img']['tmp_name'] != 'none')
         ? $_FILES['message_img'] : array()
     );

    if (add_message($message))
    {
        show_message($_LANG['add_message_success'], $_LANG['message_list_lnk'], 'user.php?act=message_list&order_id=' . $message['order_id'],'info');
    }
    else
    {
        $err->show($_LANG['message_list_lnk'], 'user.php?act=message_list');
    }
}

/* 标签云列表 */
elseif ($action == 'tag_list')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $good_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    $smarty->assign('tags',      get_user_tags($user_id));
    $smarty->assign('tags_from', 'user');
    $smarty->display('user_clips.dwt');
}

/* 删除标签云的处理 */
elseif ($action == 'act_del_tag')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $tag_words = isset($_GET['tag_words']) ? trim($_GET['tag_words']) : '';
    delete_tag($tag_words, $user_id);

    ecs_header("Location: user.php?act=tag_list\n");
    exit;

}

/* 显示缺货登记列表 */
elseif ($action == 'booking_list')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    /* 获取缺货登记的数量 */
    $sql = "SELECT COUNT(*) " .
            "FROM " .$ecs->table('booking_goods'). " AS bg, " .
                     $ecs->table('goods') . " AS g " .
            "WHERE bg.goods_id = g.goods_id AND user_id = '$user_id'";
    $record_count = $db->getOne($sql);
    $pager = get_pager('user.php', array('act' => $action), $record_count, $page);

    $smarty->assign('booking_list', get_booking_list($user_id, $pager['size'], $pager['start']));
    $smarty->assign('pager',        $pager);
    $smarty->display('user_clips.dwt');
}
/* 添加缺货登记页面 */
elseif ($action == 'add_booking')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $goods_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($goods_id == 0)
    {
        show_message($_LANG['no_goods_id'], $_LANG['back_page_up'], '', 'error');
    }

    /* 根据规格属性获取货品规格信息 */
    $goods_attr = '';
    if ($_GET['spec'] != '')
    {
        $goods_attr_id = $_GET['spec'];

        $attr_list = array();
        $sql = "SELECT a.attr_name, g.attr_value " .
                "FROM " . $ecs->table('goods_attr') . " AS g, " .
                    $ecs->table('attribute') . " AS a " .
                "WHERE g.attr_id = a.attr_id " .
                "AND g.goods_attr_id " . db_create_in($goods_attr_id);
        $res = $db->query($sql);
        while ($row = $db->fetchRow($res))
        {
            $attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
        }
        $goods_attr = join(chr(13) . chr(10), $attr_list);
    }
    $smarty->assign('goods_attr', $goods_attr);

    $smarty->assign('info', get_goodsinfo($goods_id));
    $smarty->display('user_clips.dwt');

}

/* 添加缺货登记的处理 */
elseif ($action == 'act_add_booking')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $booking = array(
        'goods_id'     => isset($_POST['id'])      ? intval($_POST['id'])     : 0,
        'goods_amount' => isset($_POST['number'])  ? intval($_POST['number']) : 0,
        'desc'         => isset($_POST['desc'])    ? trim($_POST['desc'])     : '',
        'linkman'      => isset($_POST['linkman']) ? trim($_POST['linkman'])  : '',
        'email'        => isset($_POST['email'])   ? trim($_POST['email'])    : '',
        'tel'          => isset($_POST['tel'])     ? trim($_POST['tel'])      : '',
        'booking_id'   => isset($_POST['rec_id'])  ? intval($_POST['rec_id']) : 0
    );

    // 查看此商品是否已经登记过
    $rec_id = get_booking_rec($user_id, $booking['goods_id']);
    if ($rec_id > 0)
    {
        show_message($_LANG['booking_rec_exist'], $_LANG['back_page_up'], '', 'error');
    }

    if (add_booking($booking))
    {
        show_message($_LANG['booking_success'], $_LANG['back_booking_list'], 'user.php?act=booking_list',
        'info');
    }
    else
    {
        $err->show($_LANG['booking_list_lnk'], 'user.php?act=booking_list');
    }
}

/* 删除缺货登记 */
elseif ($action == 'act_del_booking')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id == 0 || $user_id == 0)
    {
        ecs_header("Location: user.php?act=booking_list\n");
        exit;
    }

    $result = delete_booking($id, $user_id);
    if ($result)
    {
        ecs_header("Location: user.php?act=booking_list\n");
        exit;
    }
}

/* 确认收货 */
elseif ($action == 'affirm_received')
{
    include dirname(__DIR__).'/includes/lib_jpush.php';
    include ROOT_PATH_WAP.'includes/wxSend/lib_wxsend.php';
    include_once(ROOT_PATH . 'includes/lib_transaction.php');

    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

    if (affirm_received($order_id, $user_id))
    {
        $sql = "update {$ecs->table('order_goods')} set is_finish=1 where order_id=$order_id";
        $db->query($sql);
        jinjiuchoujiang($order_id);
        
        /***
         * 用户推送，供货商推送，代理商家推送。。。。。。
         * **/
        $sql = "select o.supplier_id,o.wx_openId,o.order_sn,o.add_time,og.goods_name,og.goods_number,og.shipping_time from {$ecs->table('order')} o "
        ."inner join {$ecs->table('order_goods')} og on og.order_id=o.order_id where o.order_id=$order_id";
        $order_list = $db->getAll($sql);
        $keyword = array();
        foreach($order_list as $order){
            $keyword[] = $order['goods_name'].'*'.$order['goods_number'];
        }
        /**
        供货商推送         */
        $sql = "select distinct user_id from {$ecs->table('order_delivery')} where order_id=$order_id";
        $agent_list = $db->getAll($sql);
        if(!empty($agent_list)){
            foreach ($agent_list as $value){
                app_jpush('你配送的订单已确认收货，点击查看配送订单详情', '用户确认收货', $value['user_id'], array('type'=>'order_agent'));
                $sql = "select wx_openId from {$GLOBALS['ecs']->table('notice')} where type=2 and belong_id={$value['user_id']}";
                $openid_list = $GLOBALS['db']->getAll($sql);
                if(!empty($openid_list)){
                    foreach($openid_list as $value){
                        $openid = $value['wx_openId'];
                        $data=array(
                            'first'=>array('value'=>urlencode("用户确认收货"),'color'=>"#743A3A"),
                            'keyword1'=>array('value'=>urlencode($order['order_sn']),'color'=>'blue'),
                            'keyword2'=>array('value'=>urlencode(implode(' ', $keyword)),'color'=>'blue'),
                            'keyword3'=>array('value'=>urlencode(local_getdate($order['add_time'])),'color'=>'blue'),
                            'keyword4'=>array('value'=>urlencode(local_getdate($order['shipping_time'])),'color'=>'blue'),
                            'keyword5'=>array('value'=>urlencode(local_getdate()),'color'=>'blue'),
                            'remark'=>array('value'=>urlencode('感谢您的支持与厚爱。'),'color'=>'#743A3A')
                        );
                        $url="";
                        $ordertype=8;
                        send_order_message($openid,$data,$url,$ordertype);
                    }
                }
            }
        }
        /**
         * 用户推送
         * **/
        app_jpush('你的订单已确认收货，点击查看订单详情', '用户确认收货', $_SESSION['user_id'], array('type'=>'order','order_id'=>$order_id));
        if(empty($order['supplier_id'])){
            //给平台发送推送消息
            $sql = "select wx_openId from {$GLOBALS['ecs']->table('notice')} where type=0 and belong_id=0";
        }else{
            $sql = "select wx_openId from {$GLOBALS['ecs']->table('notice')} where type=1 and belong_id={$order['supplier_id']}";
        }
        $openid_list = $GLOBALS['db']->getAll($sql);
        if(!empty($openid_list)){
            foreach($openid_list as $value){
                $openid = $value['wx_openId'];
                $data=array(
                    'first'=>array('value'=>urlencode("用户确认收货"),'color'=>"#743A3A"),
                    'keyword1'=>array('value'=>urlencode($order['order_sn']),'color'=>'blue'),
                    'keyword2'=>array('value'=>urlencode(implode(' ', $keyword)),'color'=>'blue'),
                    'keyword3'=>array('value'=>urlencode(local_getdate($order['add_time'])),'color'=>'blue'),
                    'keyword4'=>array('value'=>urlencode(local_getdate($order['shipping_time'])),'color'=>'blue'),
                    'keyword5'=>array('value'=>urlencode(local_getdate()),'color'=>'blue'),
                    'remark'=>array('value'=>urlencode('感谢您的支持与厚爱。'),'color'=>'#743A3A')
                );
                $url="";
                $ordertype=8;
                send_order_message($openid,$data,$url,$ordertype);
            }
        }
        //向用户发送一条推送消息
        if(!empty($order['wx_openId'])){
            $data=array(
                'first'=>array('value'=>urlencode("用户确认收货"),'color'=>"#743A3A"),
                'keyword1'=>array('value'=>urlencode($order['order_sn']),'color'=>'blue'),
                'keyword2'=>array('value'=>urlencode(implode(' ', $keyword)),'color'=>'blue'),
                'keyword3'=>array('value'=>urlencode(local_getdate($order['add_time'])),'color'=>'blue'),
                'keyword4'=>array('value'=>urlencode(local_getdate($order['shipping_time'])),'color'=>'blue'),
                'keyword5'=>array('value'=>urlencode(local_getdate()),'color'=>'blue'),
                'remark'=>array('value'=>urlencode('感谢您的支持与厚爱。'),'color'=>'#743A3A')
            );
            $url="http://b.yidiandao.com/mobile_b2b/user.php?act=order_detail&order_id=".$order_id;
            $ordertype=8;
            send_order_message($openid,$data,$url,$ordertype);
        }
        ecs_header("Location: user.php?act=order_list\n");
        exit;
    }
    else
    {
        $err->show($_LANG['order_list_lnk'], 'user.php?act=order_list');
    }
}

//扫码确认收货
elseif($action == 'finish_order'){
    include dirname(__DIR__).'/includes/lib_jpush.php';
    include(ROOT_PATH_WAP. "includes/wxSend/lib_wxsend.php");
    header('Content-Type:text/html;charset=utf8');
    $agent_id = empty(intval($_GET['agent_id']))?0:intval($_GET['agent_id']);
    if(!empty($agent_id)){
        $rec_id = empty($_GET['rec_id'])?'':strval($_GET['rec_id']);
        if(empty($rec_id)){
            exit('<script type="text/javascript">alert("订单不存在");history.back();</script>');
        }
        $rec_id = explode(',', $rec_id);
        foreach($rec_id as $key=>$value){
            $rec_id[$key] = intval($value);
        }
        $rec_id = array_unique($rec_id);
        $rec_id = implode(',', $rec_id);
        $sql = "select count(*) from {$ecs->table('order_delivery')} where rec_id in($rec_id) and user_id=$agent_id";
        $count = $GLOBALS['db']->getOne($sql);
        if(empty($count)){
            exit('<script type="text/javascript">alert("订单不存在");history.back();</script>');
        }
        /* 查询订单信息，检查状态 */
        $sql = "SELECT og.rec_id,o.user_id, o.order_sn , o.order_status, o.shipping_status, o.pay_status,o.order_id,og.goods_name,"
                ."og.goods_number,o.add_time,o.shipping_time,o.supplier_id,o.wx_openId FROM ".$GLOBALS['ecs']->table('order_goods') ." og "
                . "inner join {$GLOBALS['ecs']->table('order_info')} o on og.order_id=o.order_id WHERE og.rec_id in($rec_id)";

        $order_list = $GLOBALS['db']->getAll($sql);
        if(empty($order_list)){
            exit('<script type="text/javascript">alert("'.$GLOBALS['_LANG']['order_invalid'].'");history.back();</script>');
        }
        
        $order_goods_array = array();
        
        foreach($order_list as $order){
            // 如果用户ID大于 0 。检查订单是否属于该用户
            if ($user_id > 0 && $order['user_id'] != $user_id)
            {
                exit('<script type="text/javascript">alert("'.$GLOBALS['_LANG']['no_priv'].'");history.back();</script>');
                //$GLOBALS['err'] -> add($GLOBALS['_LANG']['no_priv']);
            }
            /* 检查订单 */
            elseif ($order['shipping_status'] == SS_RECEIVED)
            {
                exit('<script type="text/javascript">alert("'.$GLOBALS['_LANG']['order_already_received'].'");history.back();</script>');
                //$GLOBALS['err'] ->add($GLOBALS['_LANG']['order_already_received']);

            }
            elseif ($order['shipping_status'] != SS_SHIPPED && $order['shipping_status'] != SS_SHIPPED_PART){
                exit('<script type="text/javascript">alert("'.$GLOBALS['_LANG']['order_invalid'].'");history.back();</script>');
            }

            //更改部分收货
            $sql = "update {$ecs->table('order_goods')} set is_finish=1 where rec_id={$order['rec_id']}";
            $db->query($sql);

            //判断是否已完成全部发货
            if($order['shipping_status'] == SS_SHIPPED){
                //判断是否都全部收货
                $sql = "select count(*) from {$ecs->table('order_goods')} where order_id={$order['order_id']} and is_finish=0";
                if(empty($db->getOne($sql))){
                    include_once(ROOT_PATH . 'includes/lib_transaction.php');
                    $recevie_flag = affirm_received($order['order_id'], $user_id);
                    if (empty($recevie_flag))
                    {
                        exit('<script type="text/javascript">alert("确认收货失败");history.back();</script>');
                    }else{
                        jinjiuchoujiang($order['order_id']);
                    }
                }
            }
            
            $order_goods_array[] = $order['goods_name'].'*'.$order['goods_number'];
            
        }
        
        //向用户发送一条推送消息
        $openid = $order['wx_openId'];
        $data=array(
            'first'=>array('value'=>urlencode("用户确认收货"),'color'=>"#743A3A"),
            'keyword1'=>array('value'=>urlencode($order['order_sn']),'color'=>'#743A3A'),
            'keyword2'=>array('value'=>urlencode(implode(' ', $order_goods_array)),'color'=>'#743A3A'),
            'keyword3'=>array('value'=>urlencode(local_date('Y-m-d H;i:s', $order['add_time'])),'color'=>'#743A3A'),
            'keyword4'=>array('value'=>urlencode(local_date('Y-m-d H;i:s',$order['shipping_time'])),'color'=>'#743A3A'),
            'keyword5'=>array('value'=>urlencode(local_date('Y-m-d H;i:s')),'color'=>'#743A3A'),
            'remark'=>array('value'=>urlencode('感谢您的支持与厚爱。'),'color'=>'#743A3A')
        );
        $url="http://b.yidiandao.com/mobile_b2b/user.php?act=order_detail&order_id=".$order['order_id'];
        $ordertype=8;
        send_order_message($openid,$data,$url,$ordertype);
        
        //确认收货后，向代理商家发送消息
        $sql = "select wx_openId from {$GLOBALS['ecs']->table('notice')} where type=2 and belong_id=$agent_id";
        $openid_list = $GLOBALS['db']->getAll($sql);
        if(!empty($openid_list)){
            foreach($openid_list as $value){
                $openid = $value['wx_openId'];
                $data=array(
                    'first'=>array('value'=>urlencode("用户确认收货"),'color'=>"#743A3A"),
                    'keyword1'=>array('value'=>urlencode($order['order_sn']),'color'=>'#743A3A'),
                    'keyword2'=>array('value'=>urlencode(implode(' ', $order_goods_array)),'color'=>'#743A3A'),
                    'keyword3'=>array('value'=>urlencode(local_date('Y-m-d H;i:s',$order['add_time'])),'color'=>'#743A3A'),
                    'keyword4'=>array('value'=>urlencode(local_date('Y-m-d H;i:s',$order['shipping_time'])),'color'=>'#743A3A'),
                    'keyword5'=>array('value'=>urlencode(local_date('Y-m-d H;i:s')),'color'=>'#743A3A'),
                    'remark'=>array('value'=>urlencode('感谢您的支持与厚爱。'),'color'=>'#743A3A')
                );
                $url="";
                $ordertype=8;
                send_order_message($openid,$data,$url,$ordertype);
            }
        }
        //代理商家和用户手机推送消息
        app_jpush('你配送的订单已确认收货，点击查看配送订单详情', '用户确认收货', $agent_id, array('type'=>'order_agent'));
        //向供应商发送消息
        if(!empty($order['supplier_id'])){
            $sql = "select wx_openId from {$GLOBALS['ecs']->table('notice')} where type=1 and belong_id={$order['supplier_id']}";
            $openid_list = $GLOBALS['db']->getAll($sql);
            if(!empty($openid_list)){
                foreach($openid_list as $value){
                    $openid = $value['wx_openId'];
                    $data=array(
                        'first'=>array('value'=>urlencode("用户确认收货"),'color'=>"#743A3A"),
                        'keyword1'=>array('value'=>urlencode($order['order_sn']),'color'=>'#743A3A'),
                        'keyword2'=>array('value'=>urlencode(implode(' ', $order_goods_array)),'color'=>'#743A3A'),
                        'keyword3'=>array('value'=>urlencode(local_date('Y-m-d H;i:s',$order['add_time'])),'color'=>'#743A3A'),
                        'keyword4'=>array('value'=>urlencode(local_date('Y-m-d H;i:s',$order['shipping_time'])),'color'=>'#743A3A'),
                        'keyword5'=>array('value'=>urlencode(local_date('Y-m-d H;i:s')),'color'=>'#743A3A'),
                        'remark'=>array('value'=>urlencode('感谢您的支持与厚爱。'),'color'=>'#743A3A')
                    );
                    $url="";
                    $ordertype=8;
                    send_order_message($openid,$data,$url,$ordertype);
                }
            }
        }
            
        
        exit('<script type="text/javascript">alert("确认收货成功");location.href="user.php?act=order_list"</script>');
        
        //代理商家订单二维码
    }else{
        //供货商家订单
        $order_id = empty(intval($_GET['order_id']))?0:intval($_GET['order_id']);
        include_once(ROOT_PATH . 'includes/lib_transaction.php');
        /*if (!affirm_received($order_id, $user_id))
        {
            exit('<script type="text/javascript">alert("确认收货失败");history.back();</script>');
        }*/
        $recevie_flag = affirm_received($order_id, $user_id);
        if (empty($recevie_flag))
        {
            exit('<script type="text/javascript">alert("确认收货失败");history.back();</script>');
        }else{
            jinjiuchoujiang($order_id);
        }
        /* 查询订单信息，检查状态 */
        $sql = "SELECT o.user_id, o.order_sn ,o.order_id,og.goods_name,og.goods_number,o.add_time,o.shipping_time,o.supplier_id,o.confirm_time,o.wx_openId "
                ."FROM ".$GLOBALS['ecs']->table('order_goods') ." og "
                . "inner join {$GLOBALS['ecs']->table('order_info')} o on og.order_id=o.order_id WHERE o.order_id=$order_id";

        $order = $GLOBALS['db']->GetRow($sql);
        //向用户发送一条推送消息
        $openid = $order['wx_openId'];
        $data=array(
            'first'=>array('value'=>urlencode("用户确认收货"),'color'=>"#743A3A"),
            'keyword1'=>array('value'=>urlencode($order['order_sn']),'color'=>'#743A3A'),
            'keyword2'=>array('value'=>urlencode($order['goods_name'].'*'.$order['goods_number']),'color'=>'#743A3A'),
            'keyword3'=>array('value'=>urlencode(local_date('Y-m-d H;i:s',$order['add_time'])),'color'=>'#743A3A'),
            'keyword4'=>array('value'=>urlencode(local_date('Y-m-d H;i:s',$order['shipping_time'])),'color'=>'#743A3A'),
            'keyword5'=>array('value'=>urlencode(local_date('Y-m-d H;i:s',$order['confirm_time'])),'color'=>'#743A3A'),
            'remark'=>array('value'=>urlencode('感谢您的支持与厚爱。'),'color'=>'#743A3A')
        );
        $url="http://b.yidiandao.com/mobile_b2b/user.php?act=order_detail&order_id=".$order['order_id'];
        $ordertype=8;
        send_order_message($openid,$data,$url,$ordertype);
        
        //给代理商家或平台发送推送消息
        if(empty($order['supplier_id'])){
            //给平台发送推送消息
            $sql = "select wx_openId from {$GLOBALS['ecs']->table('notice')} where type=0 and belong_id=0";
        }else{
            $sql = "select wx_openId from {$GLOBALS['ecs']->table('notice')} where type=1 and belong_id={$order['supplier_id']}";
        }
        $openid_list = $GLOBALS['db']->getAll($sql);
        if(!empty($openid_list)){
            foreach($openid_list as $value){
                $openid = $value['wx_openId'];
                $data=array(
                    'first'=>array('value'=>urlencode("用户确认收货"),'color'=>"#743A3A"),
                    'keyword1'=>array('value'=>urlencode($order['order_sn']),'color'=>'#743A3A'),
                    'keyword2'=>array('value'=>urlencode($order['goods_name'].'*'.$order['goods_number']),'color'=>'#743A3A'),
                    'keyword3'=>array('value'=>urlencode(local_date('Y-m-d H;i:s',$order['add_time'])),'color'=>'#743A3A'),
                    'keyword4'=>array('value'=>urlencode(local_date('Y-m-d H;i:s',$order['shipping_time'])),'color'=>'#743A3A'),
                    'keyword5'=>array('value'=>urlencode(local_date('Y-m-d H;i:s',$order['confirm_time'])),'color'=>'#743A3A'),
                    'remark'=>array('value'=>urlencode('感谢您的支持与厚爱。'),'color'=>'#743A3A')
                );
                $url="";
                $ordertype=8;
                send_order_message($openid,$data,$url,$ordertype);
            }
        }
        
        exit('<script type="text/javascript">alert("确认收货成功");location.href="user.php?act=order_list"</script>');
    }
}

//获取微信信息
elseif($action == 'get_wx_info'){
    $req_url = trim($_GET['url']);
    $timestamp = time();
    $appId = 'wx25e2f36a2ee13a10';
    $nostr = md5($timestamp);
    $jsApiList = array(
        'scanQRCode'
    );
    $jsapi_tacket = file_get_contents(__DIR__.'/jsapi_tacket.txt');
    if(empty($jsapi_tacket)){
        $jsapi_tacket = get_api_tacket($appId);
        $jsapi_tacket = json_decode($jsapi_tacket,true);
        $jsapi_tacket['reqtime'] = time();
        file_put_contents(__DIR__.'/jsapi_tacket.txt', json_encode($jsapi_tacket));
    }else{
        $jsapi_tacket = json_decode($jsapi_tacket,true);
        if(empty($jsapi_tacket)||(time()-intval($jsapi_tacket['reqtime']))>intval($jsapi_tacket['expires_in'])){
            $jsapi_tacket = get_api_tacket($appId);
            $jsapi_tacket = json_decode($jsapi_tacket,true);
            $jsapi_tacket['reqtime'] = time();
            file_put_contents(__DIR__.'/jsapi_tacket.txt', json_encode($jsapi_tacket));
        }
    }
    
    $array = array(
        'noncestr='.$nostr,
        'jsapi_ticket='.$jsapi_tacket['ticket'],
        'timestamp='.$timestamp,
        'url='.$req_url
    );
    sort($array);
    $array = implode('&', $array);
    $signature = sha1($array);
    echo $sinatrue;
    exit(json_encode(array(
        'appId'=>$appId,
        'timestamp'=>$timestamp,
        'nonceStr'=>$nostr,
        'signature'=>$signature,
        'jsApiList'=>$jsApiList
    )));
    
}

/* 会员退款申请界面 */
elseif ($action == 'account_raply')
{
    $smarty->display('user_transaction.dwt');
}

/* 会员预付款界面 */
elseif ($action == 'account_deposit')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $surplus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $account    = get_surplus_info($surplus_id);

    $smarty->assign('payment', get_online_payment_list(false));
    $smarty->assign('order',   $account);
    $smarty->display('user_transaction.dwt');
}

/* 会员账目明细界面 */
elseif ($action == 'account_detail')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    $account_type = 'user_money';

    /* 获取记录条数 */
    $sql = "SELECT COUNT(*) FROM " .$ecs->table('account_log').
           " WHERE user_id = '$user_id'" .
           " AND $account_type <> 0 ";
    $record_count = $db->getOne($sql);

    //分页函数
    $pager = get_pager('user.php', array('act' => $action), $record_count, $page);

    //获取剩余余额
    $surplus_amount = get_user_surplus($user_id);
    if (empty($surplus_amount))
    {
        $surplus_amount = 0;
    }

    //获取余额记录
    $account_log = array();
    $sql = "SELECT * FROM " . $ecs->table('account_log') .
           " WHERE user_id = '$user_id'" .
           " AND $account_type <> 0 " .
           " ORDER BY log_id DESC";
    $res = $GLOBALS['db']->selectLimit($sql, $pager['size'], $pager['start']);
    while ($row = $db->fetchRow($res))
    {
        $row['change_time'] = local_date($_CFG['date_format'], $row['change_time']);
        $row['type'] = $row[$account_type] > 0 ? $_LANG['account_inc'] : $_LANG['account_dec'];
        $row['user_money'] = price_format(abs($row['user_money']), false);
        $row['frozen_money'] = price_format(abs($row['frozen_money']), false);
        $row['rank_points'] = abs($row['rank_points']);
        $row['pay_points'] = abs($row['pay_points']);
        $row['short_change_desc'] = sub_str($row['change_desc'], 60);
        $row['amount'] = $row[$account_type];
        $account_log[] = $row;
    }

    //模板赋值
    $smarty->assign('surplus_amount', price_format($surplus_amount, false));
    $smarty->assign('account_log',    $account_log);
    $smarty->assign('pager',          $pager);
    $smarty->display('user_transaction.dwt');
}

/* 会员充值和提现申请记录 */
elseif ($action == 'account_log')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    /* 获取记录条数 */
    $sql = "SELECT COUNT(*) FROM " .$ecs->table('user_account').
           " WHERE user_id = '$user_id'" .
           " AND process_type " . db_create_in(array(SURPLUS_SAVE, SURPLUS_RETURN));
    $record_count = $db->getOne($sql);

    //分页函数
    $pager = get_pager('user.php', array('act' => $action), $record_count, $page);

    //获取剩余余额
    $surplus_amount = get_user_surplus($user_id);
    if (empty($surplus_amount))
    {
        $surplus_amount = 0;
    }

    //获取余额记录
    $account_log = get_account_log($user_id, $pager['size'], $pager['start']);

    //模板赋值
    $smarty->assign('surplus_amount', price_format($surplus_amount, false));
    $smarty->assign('account_log',    $account_log);
    $smarty->assign('pager',          $pager);
    $smarty->display('user_transaction.dwt');
}

/* 对会员余额申请的处理 */
elseif ($action == 'act_account')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');
    include_once(ROOT_PATH . 'includes/lib_order.php');
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    if ($amount <= 0)
    {
        show_message($_LANG['amount_gt_zero']);
    }

    /* 变量初始化 */
    $surplus = array(
            'user_id'      => $user_id,
            'rec_id'       => !empty($_POST['rec_id'])      ? intval($_POST['rec_id'])       : 0,
            'process_type' => isset($_POST['surplus_type']) ? intval($_POST['surplus_type']) : 0,
            'payment_id'   => isset($_POST['payment_id'])   ? intval($_POST['payment_id'])   : 0,
            'user_note'    => isset($_POST['user_note'])    ? trim($_POST['user_note'])      : '',
            'amount'       => $amount
    );

    /* 退款申请的处理 */
    if ($surplus['process_type'] == 1)
    {
        /* 判断是否有足够的余额的进行退款的操作 */
        $sur_amount = get_user_surplus($user_id);
        if ($amount > $sur_amount)
        {
            $content = $_LANG['surplus_amount_error'];
            show_message($content, $_LANG['back_page_up'], '', 'info');
        }

        //插入会员账目明细
        $amount = '-'.$amount;
        $surplus['payment'] = '';
        $surplus['rec_id']  = insert_user_account($surplus, $amount);

        /* 如果成功提交 */
        if ($surplus['rec_id'] > 0)
        {
            $content = $_LANG['surplus_appl_submit'];
            show_message($content, $_LANG['back_account_log'], 'user.php?act=account_log', 'info');
        }
        else
        {
            $content = $_LANG['process_false'];
            show_message($content, $_LANG['back_page_up'], '', 'info');
        }
    }
    /* 如果是会员预付款，跳转到下一步，进行线上支付的操作 */
    else
    {
        if ($surplus['payment_id'] <= 0)
        {
            show_message($_LANG['select_payment_pls']);
        }

        include_once(ROOT_PATH .'includes/lib_payment.php');

        //获取支付方式名称
        $payment_info = array();
        $payment_info = payment_info($surplus['payment_id']);
        $surplus['payment'] = $payment_info['pay_name'];

        if ($surplus['rec_id'] > 0)
        {
            //更新会员账目明细
            $surplus['rec_id'] = update_user_account($surplus);
        }
        else
        {
            //插入会员账目明细
            $surplus['rec_id'] = insert_user_account($surplus, $amount);
        }

        //取得支付信息，生成支付代码
        $payment = unserialize_config($payment_info['pay_config']);

        //生成伪订单号, 不足的时候补0
        $order = array();
        $order['order_sn']       = $surplus['rec_id'];
        $order['user_name']      = $_SESSION['user_name'];
        $order['surplus_amount'] = $amount;

        //计算支付手续费用
        $payment_info['pay_fee'] = pay_fee($surplus['payment_id'], $order['surplus_amount'], 0);

        //计算此次预付款需要支付的总金额
        $order['order_amount']   = $amount + $payment_info['pay_fee'];

        //记录支付log
        $order['log_id'] = insert_pay_log($surplus['rec_id'], $order['order_amount'], $type=PAY_SURPLUS, 0);

        /* 调用相应的支付方式文件 */
        include_once(ROOT_PATH . 'includes/modules/payment/' . $payment_info['pay_code'] . '.php');

        /* 取得在线支付方式的支付按钮 */
        $pay_obj = new $payment_info['pay_code'];
        $payment_info['pay_button'] = $pay_obj->get_code($order, $payment);

        /* 模板赋值 */
        $smarty->assign('payment', $payment_info);
        $smarty->assign('pay_fee', price_format($payment_info['pay_fee'], false));
        $smarty->assign('amount',  price_format($amount, false));
        $smarty->assign('order',   $order);
        $smarty->display('user_transaction.dwt');
    }
}

/* 删除会员余额 */
elseif ($action == 'cancel')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id == 0 || $user_id == 0)
    {
        ecs_header("Location: user.php?act=account_log\n");
        exit;
    }

    $result = del_user_account($id, $user_id);
    if ($result)
    {
        ecs_header("Location: user.php?act=account_log\n");
        exit;
    }
}

/* 会员通过帐目明细列表进行再付款的操作 */
elseif ($action == 'pay')
{
    include_once(ROOT_PATH . 'includes/lib_clips.php');
    include_once(ROOT_PATH . 'includes/lib_payment.php');
    include_once(ROOT_PATH . 'includes/lib_order.php');

    //变量初始化
    $surplus_id = isset($_GET['id'])  ? intval($_GET['id'])  : 0;
    $payment_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;

    if ($surplus_id == 0)
    {
        ecs_header("Location: user.php?act=account_log\n");
        exit;
    }

    //如果原来的支付方式已禁用或者已删除, 重新选择支付方式
    if ($payment_id == 0)
    {
        ecs_header("Location: user.php?act=account_deposit&id=".$surplus_id."\n");
        exit;
    }

    //获取单条会员帐目信息
    $order = array();
    $order = get_surplus_info($surplus_id);

    //支付方式的信息
    $payment_info = array();
    $payment_info = payment_info($payment_id);

    /* 如果当前支付方式没有被禁用，进行支付的操作 */
    if (!empty($payment_info))
    {
        //取得支付信息，生成支付代码
        $payment = unserialize_config($payment_info['pay_config']);

        //生成伪订单号
        $order['order_sn'] = $surplus_id;

        //获取需要支付的log_id
        $order['log_id'] = get_paylog_id($surplus_id, $pay_type = PAY_SURPLUS);

        $order['user_name']      = $_SESSION['user_name'];
        $order['surplus_amount'] = $order['amount'];

        //计算支付手续费用
        $payment_info['pay_fee'] = pay_fee($payment_id, $order['surplus_amount'], 0);

        //计算此次预付款需要支付的总金额
        $order['order_amount']   = $order['surplus_amount'] + $payment_info['pay_fee'];

        //如果支付费用改变了，也要相应的更改pay_log表的order_amount
        $order_amount = $db->getOne("SELECT order_amount FROM " .$ecs->table('pay_log')." WHERE log_id = '$order[log_id]'");
        if ($order_amount <> $order['order_amount'])
        {
            $db->query("UPDATE " .$ecs->table('pay_log').
                       " SET order_amount = '$order[order_amount]' WHERE log_id = '$order[log_id]'");
        }

        /* 调用相应的支付方式文件 */
        include_once(ROOT_PATH . 'includes/modules/payment/' . $payment_info['pay_code'] . '.php');

        /* 取得在线支付方式的支付按钮 */
        $pay_obj = new $payment_info['pay_code'];
        $payment_info['pay_button'] = $pay_obj->get_code($order, $payment);

        /* 模板赋值 */
        $smarty->assign('payment', $payment_info);
        $smarty->assign('order',   $order);
        $smarty->assign('pay_fee', price_format($payment_info['pay_fee'], false));
        $smarty->assign('amount',  price_format($order['surplus_amount'], false));
        $smarty->assign('action',  'act_account');
        $smarty->display('user_transaction.dwt');
    }
    /* 重新选择支付方式 */
    else
    {
        include_once(ROOT_PATH . 'includes/lib_clips.php');

        $smarty->assign('payment', get_online_payment_list());
        $smarty->assign('order',   $order);
        $smarty->assign('action',  'account_deposit');
        $smarty->display('user_transaction.dwt');
    }
}

/* 添加标签(ajax) */
elseif ($action == 'add_tag')
{
    include_once('includes/cls_json.php');
    include_once('includes/lib_clips.php');

    $result = array('error' => 0, 'message' => '', 'content' => '');
    $id     = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $tag    = isset($_POST['tag']) ? json_str_iconv(trim($_POST['tag'])) : '';

    if ($user_id == 0)
    {
        /* 用户没有登录 */
        $result['error']   = 1;
        $result['message'] = $_LANG['tag_anonymous'];
    }
    else
    {
        add_tag($id, $tag); // 添加tag
        clear_cache_files('goods'); // 删除缓存

        /* 重新获得该商品的所有缓存 */
        $arr = get_tags($id);

        foreach ($arr AS $row)
        {
            $result['content'][] = array('word' => htmlspecialchars($row['tag_words']), 'count' => $row['tag_count']);
        }
    }

    $json = new JSON;

    echo $json->encode($result);
    exit;
}

/* 添加收藏商品(ajax) */
elseif ($action == 'collect')
{
    include_once(ROOT_PATH .'includes/cls_json.php');
    $json = new JSON();
    $result = array('error' => 0, 'message' => '');
    $goods_id = $_GET['id'];

    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0)
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['login_please'];
        die($json->encode($result));
    }
    else
    {
        /* 检查是否已经存在于用户的收藏夹 */
        $sql = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('collect_goods') .
            " WHERE user_id='$_SESSION[user_id]' AND goods_id = '$goods_id'";
        if ($GLOBALS['db']->GetOne($sql) > 0)
        {
            $result['error'] = 1;
            $result['message'] = $GLOBALS['_LANG']['collect_existed'];
            die($json->encode($result));
        }
        else
        {
            $time = gmtime();
            $sql = "INSERT INTO " .$GLOBALS['ecs']->table('collect_goods'). " (user_id, goods_id, add_time)" .
                    "VALUES ('$_SESSION[user_id]', '$goods_id', '$time')";

            if ($GLOBALS['db']->query($sql) === false)
            {
                $result['error'] = 1;
                $result['message'] = $GLOBALS['db']->errorMsg();
                die($json->encode($result));
            }
            else
            {
                $result['error'] = 0;
                $result['message'] = $GLOBALS['_LANG']['collect_success'];
                die($json->encode($result));
            }
        }
    }
}

/* 删除留言 */
elseif ($action == 'del_msg')
{
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $order_id = empty($_GET['order_id']) ? 0 : intval($_GET['order_id']);

    if ($id > 0)
    {
        $sql = 'SELECT user_id, message_img FROM ' .$ecs->table('feedback'). " WHERE msg_id = '$id' LIMIT 1";
        $row = $db->getRow($sql);
        if ($row && $row['user_id'] == $user_id)
        {
            /* 验证通过，删除留言，回复，及相应文件 */
            if ($row['message_img'])
            {
                @unlink(ROOT_PATH . DATA_DIR . '/feedbackimg/'. $row['message_img']);
            }
            $sql = "DELETE FROM " .$ecs->table('feedback'). " WHERE msg_id = '$id' OR parent_id = '$id'";
            $db->query($sql);
        }
    }
    ecs_header("Location: user.php?act=message_list&order_id=$order_id\n");
    exit;
}

/* 删除评论 */
elseif ($action == 'del_cmt')
{
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id > 0)
    {
        $sql = "DELETE FROM " .$ecs->table('comment'). " WHERE comment_id = '$id' AND user_id = '$user_id'";
        $db->query($sql);
    }
    ecs_header("Location: user.php?act=comment_list\n");
    exit;
}

/* 合并订单 */
elseif ($action == 'merge_order')
{
    include_once(ROOT_PATH .'includes/lib_transaction.php');
    include_once(ROOT_PATH .'includes/lib_order.php');
    $from_order = isset($_POST['from_order']) ? trim($_POST['from_order']) : '';
    $to_order   = isset($_POST['to_order']) ? trim($_POST['to_order']) : '';
    if (merge_user_order($from_order, $to_order, $user_id))
    {
        show_message($_LANG['merge_order_success'],$_LANG['order_list_lnk'],'user.php?act=order_list', 'info');
    }
    else
    {
        $err->show($_LANG['order_list_lnk']);
    }
}
/* 将指定订单中商品添加到购物车 */
elseif ($action == 'return_to_cart')
{
    include_once(ROOT_PATH .'includes/cls_json.php');
    include_once(ROOT_PATH .'includes/lib_transaction.php');
    $json = new JSON();

    $result = array('error' => 0, 'message' => '', 'content' => '');
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    if ($order_id == 0)
    {
        $result['error']   = 1;
        $result['message'] = $_LANG['order_id_empty'];
        die($json->encode($result));
    }

    if ($user_id == 0)
    {
        /* 用户没有登录 */
        $result['error']   = 1;
        $result['message'] = $_LANG['login_please'];
        die($json->encode($result));
    }

    /* 检查订单是否属于该用户 */
    $order_user = $db->getOne("SELECT user_id FROM " .$ecs->table('order_info'). " WHERE order_id = '$order_id'");
    if (empty($order_user))
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['order_exist'];
        die($json->encode($result));
    }
    else
    {
        if ($order_user != $user_id)
        {
            $result['error'] = 1;
            $result['message'] = $_LANG['no_priv'];
            die($json->encode($result));
        }
    }

    $message = return_to_cart($order_id);

    if ($message === true)
    {
        $result['error'] = 0;
        $result['message'] = $_LANG['return_to_cart_success'];
        die($json->encode($result));
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['order_exist'];
        die($json->encode($result));
    }

}

/* 编辑使用余额支付的处理 */
elseif ($action == 'act_edit_surplus')
{
    /* 检查是否登录 */
    if ($_SESSION['user_id'] <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查订单号 */
    $order_id = intval($_POST['order_id']);
    if ($order_id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查余额 */
    $surplus = floatval($_POST['surplus']);
    if ($surplus <= 0)
    {
        $err->add($_LANG['error_surplus_invalid']);
        $err->show($_LANG['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
    }

    include_once(ROOT_PATH . 'includes/lib_order.php');

    /* 取得订单 */
    $order = order_info($order_id);
    if (empty($order))
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查订单用户跟当前用户是否一致 */
    if ($_SESSION['user_id'] != $order['user_id'])
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查订单是否未付款，检查应付款金额是否大于0 */
    if ($order['pay_status'] != PS_UNPAYED || $order['order_amount'] <= 0)
    {
        $err->add($_LANG['error_order_is_paid']);
        $err->show($_LANG['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
    }

    /* 计算应付款金额（减去支付费用） */
    $order['order_amount'] -= $order['pay_fee'];

    /* 余额是否超过了应付款金额，改为应付款金额 */
    if ($surplus > $order['order_amount'])
    {
        $surplus = $order['order_amount'];
    }

    /* 取得用户信息 */
    $user = user_info($_SESSION['user_id']);

    /* 用户帐户余额是否足够 */
    if ($surplus > $user['user_money'] + $user['credit_line'])
    {
        $err->add($_LANG['error_surplus_not_enough']);
        $err->show($_LANG['order_detail'], 'user.php?act=order_detail&order_id=' . $order_id);
    }

    /* 修改订单，重新计算支付费用 */
    $order['surplus'] += $surplus;
    $order['order_amount'] -= $surplus;
    if ($order['order_amount'] > 0)
    {
        $cod_fee = 0;
        if ($order['shipping_id'] > 0)
        {
            $regions  = array($order['country'], $order['province'], $order['city'], $order['district']);
            $shipping = shipping_area_info($order['shipping_id'], $regions);
            if ($shipping['support_cod'] == '1')
            {
                $cod_fee = $shipping['pay_fee'];
            }
        }

        $pay_fee = 0;
        if ($order['pay_id'] > 0)
        {
            $pay_fee = pay_fee($order['pay_id'], $order['order_amount'], $cod_fee);
        }

        $order['pay_fee'] = $pay_fee;
        $order['order_amount'] += $pay_fee;
    }

    /* 如果全部支付，设为已确认、已付款 */
    if ($order['order_amount'] == 0)
    {
        if ($order['order_status'] == OS_UNCONFIRMED)
        {
            $order['order_status'] = OS_CONFIRMED;
            $order['confirm_time'] = gmtime();
        }
        $order['pay_status'] = PS_PAYED;
        $order['pay_time'] = gmtime();
    }
    $order = addslashes_deep($order);
    update_order($order_id, $order);

    /* 更新用户余额 */
    $change_desc = sprintf($_LANG['pay_order_by_surplus'], $order['order_sn']);
    log_account_change($user['user_id'], (-1) * $surplus, 0, 0, 0, $change_desc);

    /* 跳转 */
    ecs_header('Location: user.php?act=order_detail&order_id=' . $order_id . "\n");
    exit;
}

/* 编辑使用余额支付的处理 */
elseif ($action == 'act_edit_payment')
{
    /* 检查是否登录 */
    if ($_SESSION['user_id'] <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查支付方式 */
    $pay_id = intval($_POST['pay_id']);
    if ($pay_id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    include_once(ROOT_PATH . 'includes/lib_order.php');
    $payment_info = payment_info($pay_id);
    if (empty($payment_info))
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查订单号 */
    $order_id = intval($_POST['order_id']);
    if ($order_id <= 0)
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 取得订单 */
    $order = order_info($order_id);
    if (empty($order))
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查订单用户跟当前用户是否一致 */
    if ($_SESSION['user_id'] != $order['user_id'])
    {
        ecs_header("Location: ./\n");
        exit;
    }

    /* 检查订单是否未付款和未发货 以及订单金额是否为0 和支付id是否为改变*/
    if ($order['pay_status'] != PS_UNPAYED || $order['shipping_status'] != SS_UNSHIPPED || $order['goods_amount'] <= 0 || $order['pay_id'] == $pay_id)
    {
        ecs_header("Location: user.php?act=order_detail&order_id=$order_id\n");
        exit;
    }

    $order_amount = $order['order_amount'] - $order['pay_fee'];
    $pay_fee = pay_fee($pay_id, $order_amount);
    $order_amount += $pay_fee;

    $sql = "UPDATE " . $ecs->table('order_info') .
           " SET pay_id='$pay_id', pay_name='$payment_info[pay_name]', pay_fee='$pay_fee', order_amount='$order_amount'".
           " WHERE order_id = '$order_id'";
    $db->query($sql);

    /* 跳转 */
    ecs_header("Location: user.php?act=order_detail&order_id=$order_id\n");
    exit;
}

/* 保存订单详情收货地址 */
elseif ($action == 'save_order_address')
{
    include_once(ROOT_PATH .'includes/lib_transaction.php');
    
    $address = array(
        'consignee' => isset($_POST['consignee']) ? compile_str(trim($_POST['consignee']))  : '',
        'email'     => isset($_POST['email'])     ? compile_str(trim($_POST['email']))      : '',
        'address'   => isset($_POST['address'])   ? compile_str(trim($_POST['address']))    : '',
        'zipcode'   => isset($_POST['zipcode'])   ? compile_str(make_semiangle(trim($_POST['zipcode']))) : '',
        'tel'       => isset($_POST['tel'])       ? compile_str(trim($_POST['tel']))        : '',
        'mobile'    => isset($_POST['mobile'])    ? compile_str(trim($_POST['mobile']))     : '',
        'sign_building' => isset($_POST['sign_building']) ? compile_str(trim($_POST['sign_building'])) : '',
        'best_time' => isset($_POST['best_time']) ? compile_str(trim($_POST['best_time']))  : '',
        'order_id'  => isset($_POST['order_id'])  ? intval($_POST['order_id']) : 0
        );
    if (save_order_address($address, $user_id))
    {
        ecs_header('Location: user.php?act=order_detail&order_id=' .$address['order_id']. "\n");
        exit;
    }
    else
    {
        $err->show($_LANG['order_list_lnk'], 'user.php?act=order_list');
    }
}

/* 我的红包列表 */
elseif ($action == 'bonus')
{
    include_once(ROOT_PATH .'includes/lib_transaction.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
    $record_count = $db->getOne("SELECT COUNT(*) FROM " .$ecs->table('user_bonus'). " WHERE user_id = '$user_id'");

    $pager = get_pager('user.php', array('act' => $action), $record_count, $page);
    $bonus = get_user_bouns_list($user_id, $pager['size'], $pager['start']);

    $smarty->assign('pager', $pager);
    $smarty->assign('bonus', $bonus);
    $smarty->display('user_transaction.dwt');
}

/* 我的团购列表 */
elseif ($action == 'group_buy')
{
    include_once(ROOT_PATH .'includes/lib_transaction.php');

    //待议
    $smarty->display('user_transaction.dwt');
}

/* 团购订单详情 */
elseif ($action == 'group_buy_detail')
{
    include_once(ROOT_PATH .'includes/lib_transaction.php');

    //待议
    $smarty->display('user_transaction.dwt');
}

// 用户推荐页面
elseif ($action == 'affiliate')
{
    $goodsid = intval(isset($_REQUEST['goodsid']) ? $_REQUEST['goodsid'] : 0);
    if(empty($goodsid))
    {
        //我的推荐页面

        $page       = !empty($_REQUEST['page'])  && intval($_REQUEST['page'])  > 0 ? intval($_REQUEST['page'])  : 1;
        $size       = !empty($_CFG['page_size']) && intval($_CFG['page_size']) > 0 ? intval($_CFG['page_size']) : 10;

        empty($affiliate) && $affiliate = array();

        if(empty($affiliate['config']['separate_by']))
        {
            //推荐注册分成
            $affdb = array();
            $num = count($affiliate['item']);
            $up_uid = "'$user_id'";
            $all_uid = "'$user_id'";
            for ($i = 1 ; $i <=$num ;$i++)
            {
                $count = 0;
                if ($up_uid)
                {
                    $sql = "SELECT user_id FROM " . $ecs->table('users') . " WHERE parent_id IN($up_uid)";
                    $query = $db->query($sql);
                    $up_uid = '';
                    while ($rt = $db->fetch_array($query))
                    {
                        $up_uid .= $up_uid ? ",'$rt[user_id]'" : "'$rt[user_id]'";
                        if($i < $num)
                        {
                            $all_uid .= ", '$rt[user_id]'";
                        }
                        $count++;
                    }
                }
                $affdb[$i]['num'] = $count;
                $affdb[$i]['point'] = $affiliate['item'][$i-1]['level_point'];
                $affdb[$i]['money'] = $affiliate['item'][$i-1]['level_money'];
            }
            $smarty->assign('affdb', $affdb);

            $sqlcount = "SELECT count(*) FROM " . $ecs->table('order_info') . " o".
        " LEFT JOIN".$ecs->table('users')." u ON o.user_id = u.user_id".
        " LEFT JOIN " . $ecs->table('affiliate_log') . " a ON o.order_id = a.order_id" .
        " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)";

            $sql = "SELECT o.*, a.log_id, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type FROM " . $ecs->table('order_info') . " o".
                    " LEFT JOIN".$ecs->table('users')." u ON o.user_id = u.user_id".
                    " LEFT JOIN " . $ecs->table('affiliate_log') . " a ON o.order_id = a.order_id" .
        " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)".
                    " ORDER BY order_id DESC" ;

            /*
                SQL解释：

                订单、用户、分成记录关联
                一个订单可能有多个分成记录

                1、订单有效 o.user_id > 0
                2、满足以下之一：
                    a.直接下线的未分成订单 u.parent_id IN ($all_uid) AND o.is_separate = 0
                        其中$all_uid为该ID及其下线(不包含最后一层下线)
                    b.全部已分成订单 a.user_id = '$user_id' AND o.is_separate > 0

            */

            $affiliate_intro = nl2br(sprintf($_LANG['affiliate_intro'][$affiliate['config']['separate_by']], $affiliate['config']['expire'], $_LANG['expire_unit'][$affiliate['config']['expire_unit']], $affiliate['config']['level_register_all'], $affiliate['config']['level_register_up'], $affiliate['config']['level_money_all'], $affiliate['config']['level_point_all']));
        }
        else
        {
            //推荐订单分成
            $sqlcount = "SELECT count(*) FROM " . $ecs->table('order_info') . " o".
                    " LEFT JOIN".$ecs->table('users')." u ON o.user_id = u.user_id".
                    " LEFT JOIN " . $ecs->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                    " WHERE o.user_id > 0 AND (o.parent_id = '$user_id' AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)";


            $sql = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $ecs->table('order_info') . " o".
                    " LEFT JOIN".$ecs->table('users')." u ON o.user_id = u.user_id".
                    " LEFT JOIN " . $ecs->table('affiliate_log') . " a ON o.order_id = a.order_id" .
                    " WHERE o.user_id > 0 AND (o.parent_id = '$user_id' AND o.is_separate = 0 OR a.user_id = '$user_id' AND o.is_separate > 0)" .
                    " ORDER BY order_id DESC" ;

            /*
                SQL解释：

                订单、用户、分成记录关联
                一个订单可能有多个分成记录

                1、订单有效 o.user_id > 0
                2、满足以下之一：
                    a.订单下线的未分成订单 o.parent_id = '$user_id' AND o.is_separate = 0
                    b.全部已分成订单 a.user_id = '$user_id' AND o.is_separate > 0

            */

            $affiliate_intro = nl2br(sprintf($_LANG['affiliate_intro'][$affiliate['config']['separate_by']], $affiliate['config']['expire'], $_LANG['expire_unit'][$affiliate['config']['expire_unit']], $affiliate['config']['level_money_all'], $affiliate['config']['level_point_all']));

        }

        $count = $db->getOne($sqlcount);

        $max_page = ($count> 0) ? ceil($count / $size) : 1;
        if ($page > $max_page)
        {
            $page = $max_page;
        }

        $res = $db->SelectLimit($sql, $size, ($page - 1) * $size);
        $logdb = array();
        while ($rt = $GLOBALS['db']->fetchRow($res))
        {
            if(!empty($rt['suid']))
            {
                //在affiliate_log有记录
                if($rt['separate_type'] == -1 || $rt['separate_type'] == -2)
                {
                    //已被撤销
                    $rt['is_separate'] = 3;
                }
            }
            $rt['order_sn'] = substr($rt['order_sn'], 0, strlen($rt['order_sn']) - 5) . "***" . substr($rt['order_sn'], -2, 2);
            $logdb[] = $rt;
        }

        $url_format = "user.php?act=affiliate&page=";

        $pager = array(
                    'page'  => $page,
                    'size'  => $size,
                    'sort'  => '',
                    'order' => '',
                    'record_count' => $count,
                    'page_count'   => $max_page,
                    'page_first'   => $url_format. '1',
                    'page_prev'    => $page > 1 ? $url_format.($page - 1) : "javascript:;",
                    'page_next'    => $page < $max_page ? $url_format.($page + 1) : "javascript:;",
                    'page_last'    => $url_format. $max_page,
                    'array'        => array()
                );
        for ($i = 1; $i <= $max_page; $i++)
        {
            $pager['array'][$i] = $i;
        }

        $smarty->assign('url_format', $url_format);
        $smarty->assign('pager', $pager);


        $smarty->assign('affiliate_intro', $affiliate_intro);
        $smarty->assign('affiliate_type', $affiliate['config']['separate_by']);

        $smarty->assign('logdb', $logdb);
    }
    else
    {
        //单个商品推荐
        $smarty->assign('userid', $user_id);
        $smarty->assign('goodsid', $goodsid);

        $types = array(1,2,3,4,5);
        $smarty->assign('types', $types);

        $goods = get_goods_info($goodsid);
        $shopurl = $ecs->url();
        $goods['goods_img'] = (strpos($goods['goods_img'], 'http://') === false && strpos($goods['goods_img'], 'https://') === false) ? $shopurl . $goods['goods_img'] : $goods['goods_img'];
        $goods['goods_thumb'] = (strpos($goods['goods_thumb'], 'http://') === false && strpos($goods['goods_thumb'], 'https://') === false) ? $shopurl . $goods['goods_thumb'] : $goods['goods_thumb'];
        $goods['shop_price'] = price_format($goods['shop_price']);

        $smarty->assign('goods', $goods);
    }

    $smarty->assign('shopname', $_CFG['shop_name']);
    $smarty->assign('userid', $user_id);
    $smarty->assign('shopurl', $ecs->url());
    $smarty->assign('logosrc', 'themes/' . $_CFG['template'] . '/images/logo.gif');

    $smarty->display('user_clips.dwt');
}

//首页邮件订阅ajax操做和验证操作
elseif ($action =='email_list')
{
    $job = $_GET['job'];

    if($job == 'add' || $job == 'del')
    {
        if(isset($_SESSION['last_email_query']))
        {
            if(time() - $_SESSION['last_email_query'] <= 30)
            {
                die($_LANG['order_query_toofast']);
            }
        }
        $_SESSION['last_email_query'] = time();
    }

    $email = trim($_GET['email']);
    $email = htmlspecialchars($email);

    if (!is_email($email))
    {
        $info = sprintf($_LANG['email_invalid'], $email);
        die($info);
    }
    $ck = $db->getRow("SELECT * FROM " . $ecs->table('email_list') . " WHERE email = '$email'");
    if ($job == 'add')
    {
        if (empty($ck))
        {
            $hash = substr(md5(time()), 1, 10);
            $sql = "INSERT INTO " . $ecs->table('email_list') . " (email, stat, hash) VALUES ('$email', 0, '$hash')";
            $db->query($sql);
            $info = $_LANG['email_check'];
            $url = $ecs->url() . "user.php?act=email_list&job=add_check&hash=$hash&email=$email";
            send_mail('', $email, $_LANG['check_mail'], sprintf($_LANG['check_mail_content'], $email, $_CFG['shop_name'], $url, $url, $_CFG['shop_name'], local_date('Y-m-d')), 1);
        }
        elseif ($ck['stat'] == 1)
        {
            $info = sprintf($_LANG['email_alreadyin_list'], $email);
        }
        else
        {
            $hash = substr(md5(time()),1 , 10);
            $sql = "UPDATE " . $ecs->table('email_list') . "SET hash = '$hash' WHERE email = '$email'";
            $db->query($sql);
            $info = $_LANG['email_re_check'];
            $url = $ecs->url() . "user.php?act=email_list&job=add_check&hash=$hash&email=$email";
            send_mail('', $email, $_LANG['check_mail'], sprintf($_LANG['check_mail_content'], $email, $_CFG['shop_name'], $url, $url, $_CFG['shop_name'], local_date('Y-m-d')), 1);
        }
        die($info);
    }
    elseif ($job == 'del')
    {
        if (empty($ck))
        {
            $info = sprintf($_LANG['email_notin_list'], $email);
        }
        elseif ($ck['stat'] == 1)
        {
            $hash = substr(md5(time()),1,10);
            $sql = "UPDATE " . $ecs->table('email_list') . "SET hash = '$hash' WHERE email = '$email'";
            $db->query($sql);
            $info = $_LANG['email_check'];
            $url = $ecs->url() . "user.php?act=email_list&job=del_check&hash=$hash&email=$email";
            send_mail('', $email, $_LANG['check_mail'], sprintf($_LANG['check_mail_content'], $email, $_CFG['shop_name'], $url, $url, $_CFG['shop_name'], local_date('Y-m-d')), 1);
        }
        else
        {
            $info = $_LANG['email_not_alive'];
        }
        die($info);
    }
    elseif ($job == 'add_check')
    {
        if (empty($ck))
        {
            $info = sprintf($_LANG['email_notin_list'], $email);
        }
        elseif ($ck['stat'] == 1)
        {
            $info = $_LANG['email_checked'];
        }
        else
        {
            if ($_GET['hash'] == $ck['hash'])
            {
                $sql = "UPDATE " . $ecs->table('email_list') . "SET stat = 1 WHERE email = '$email'";
                $db->query($sql);
                $info = $_LANG['email_checked'];
            }
            else
            {
                $info = $_LANG['hash_wrong'];
            }
        }
        show_message($info, $_LANG['back_home_lnk'], 'index.php');
    }
    elseif ($job == 'del_check')
    {
        if (empty($ck))
        {
            $info = sprintf($_LANG['email_invalid'], $email);
        }
        elseif ($ck['stat'] == 1)
        {
            if ($_GET['hash'] == $ck['hash'])
            {
                $sql = "DELETE FROM " . $ecs->table('email_list') . "WHERE email = '$email'";
                $db->query($sql);
                $info = $_LANG['email_canceled'];
            }
            else
            {
                $info = $_LANG['hash_wrong'];
            }
        }
        else
        {
            $info = $_LANG['email_not_alive'];
        }
        show_message($info, $_LANG['back_home_lnk'], 'index.php');
    }
}

elseif ($action=='follow_shop')
{
    $user = $GLOBALS['user'];
    $_CFG = $GLOBALS['_CFG'];
    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
    $user_id = $_SESSION['user_id'];
    
    include_once (ROOT_PATH . 'includes/lib_clips.php');
    
    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
    
    $record_count = $db->getOne("SELECT COUNT(*) FROM " . $ecs->table('supplier_guanzhu') . " WHERE userid='$user_id'");
    
    $pager = get_pager('user.php', array(
        'act' => $action
    ), $record_count, $page);
    $smarty->assign('pager', $pager);
    $smarty->assign('shop_list', get_follow_shops($user_id, $pager['size'], $pager['start']));
    $smarty->assign('url', $ecs->url());
    $lang_list = array(
        'UTF8' => $_LANG['charset']['utf8'], 'GB2312' => $_LANG['charset']['zh_cn'], 'BIG5' => $_LANG['charset']['zh_tw']
    );
    $smarty->assign('lang_list', $lang_list);
    $smarty->assign('user_id', $user_id);
    $smarty->display('user_clips.dwt');
}

elseif ($action=='del_follow')
{
    $user = $GLOBALS['user'];
    $_CFG = $GLOBALS['_CFG'];
    $_LANG = $GLOBALS['_LANG'];
    $smarty = $GLOBALS['smarty'];
    $db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
    $user_id = $_SESSION['user_id'];
    
    $rec_id = empty($_GET['rec_id'])?0:strval($_GET['rec_id']);
    if($rec_id)
    {
        $db->query('DELETE FROM ' . $ecs->table('supplier_guanzhu') . " WHERE id in($rec_id) AND userid ='$user_id'");
    }
    ecs_header("Location: user.php?act=follow_shop\n");
    exit();
}

/* ajax 发送验证邮件 */
elseif ($action == 'send_hash_mail')
{
    include_once(ROOT_PATH .'includes/cls_json.php');
    include_once(ROOT_PATH .'includes/lib_passport.php');
    $json = new JSON();

    $result = array('error' => 0, 'message' => '', 'content' => '');

    if ($user_id == 0)
    {
        /* 用户没有登录 */
        $result['error']   = 1;
        $result['message'] = $_LANG['login_please'];
        die($json->encode($result));
    }

    if (send_regiter_hash($user_id))
    {
        $result['message'] = $_LANG['validate_mail_ok'];
        die($json->encode($result));
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = $GLOBALS['err']->last_message();
    }

    die($json->encode($result));
}
else if ($action == 'track_packages')
{
    include_once(ROOT_PATH . 'includes/lib_transaction.php');
    include_once(ROOT_PATH .'includes/lib_order.php');

    $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

    $orders = array();

    $sql = "SELECT order_id,order_sn,invoice_no,shipping_id FROM " .$ecs->table('order_info').
            " WHERE user_id = '$user_id' AND shipping_status = '" . SS_SHIPPED . "'";
    $res = $db->query($sql);
    $record_count = 0;
    while ($item = $db->fetch_array($res))
    {
        $shipping   = get_shipping_object($item['shipping_id']);

        if (method_exists ($shipping, 'query'))
        {
            $query_link = $shipping->query($item['invoice_no']);
        }
        else
        {
            $query_link = $item['invoice_no'];
        }

        if ($query_link != $item['invoice_no'])
        {
            $item['query_link'] = $query_link;
            $orders[]  = $item;
            $record_count += 1;
        }
    }
    $pager  = get_pager('user.php', array('act' => $action), $record_count, $page);
    $smarty->assign('pager',  $pager);
    $smarty->assign('orders', $orders);
    $smarty->display('user_transaction.dwt');
}
else if ($action == 'order_query')
{
    $_GET['order_sn'] = trim(substr($_GET['order_sn'], 1));
    $order_sn = empty($_GET['order_sn']) ? '' : addslashes($_GET['order_sn']);
    include_once(ROOT_PATH .'includes/cls_json.php');
    $json = new JSON();

    $result = array('error'=>0, 'message'=>'', 'content'=>'');

    if(isset($_SESSION['last_order_query']))
    {
        if(time() - $_SESSION['last_order_query'] <= 10)
        {
            $result['error'] = 1;
            $result['message'] = $_LANG['order_query_toofast'];
            die($json->encode($result));
        }
    }
    $_SESSION['last_order_query'] = time();

    if (empty($order_sn))
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['invalid_order_sn'];
        die($json->encode($result));
    }

    $sql = "SELECT order_id, order_status, shipping_status, pay_status, ".
           " shipping_time, shipping_id, invoice_no, user_id ".
           " FROM " . $ecs->table('order_info').
           " WHERE order_sn = '$order_sn' LIMIT 1";

    $row = $db->getRow($sql);
    if (empty($row))
    {
        $result['error'] = 1;
        $result['message'] = $_LANG['invalid_order_sn'];
        die($json->encode($result));
    }

    $order_query = array();
    $order_query['order_sn'] = $order_sn;
    $order_query['order_id'] = $row['order_id'];
    $order_query['order_status'] = $_LANG['os'][$row['order_status']] . ',' . $_LANG['ps'][$row['pay_status']] . ',' . $_LANG['ss'][$row['shipping_status']];

    if ($row['invoice_no'] && $row['shipping_id'] > 0)
    {
        $sql = "SELECT shipping_code FROM " . $ecs->table('touch_shipping') . " WHERE shipping_id = '$row[shipping_id]'";
        $shipping_code = $db->getOne($sql);
        $plugin = ROOT_PATH . 'includes/modules/shipping/' . $shipping_code . '.php';
        if (file_exists($plugin))
        {
            include_once($plugin);
            $shipping = new $shipping_code;
            $order_query['invoice_no'] = $shipping->query((string)$row['invoice_no']);
        }
        else
        {
            $order_query['invoice_no'] = (string)$row['invoice_no'];
        }
    }

    $order_query['user_id'] = $row['user_id'];
    /* 如果是匿名用户显示发货时间 */
    if ($row['user_id'] == 0 && $row['shipping_time'] > 0)
    {
        $order_query['shipping_date'] = local_date($GLOBALS['_CFG']['date_format'], $row['shipping_time']);
    }
    $smarty->assign('order_query',    $order_query);
    $result['content'] = $smarty->fetch('library/order_query.lbi');
    die($json->encode($result));
}
elseif ($action == 'transform_points')
{
    $rule = array();
    if (!empty($_CFG['points_rule']))
    {
        $rule = unserialize($_CFG['points_rule']);
    }
    $cfg = array();
    if (!empty($_CFG['integrate_config']))
    {
        $cfg = unserialize($_CFG['integrate_config']);
        $_LANG['exchange_points'][0] = empty($cfg['uc_lang']['credits'][0][0])? $_LANG['exchange_points'][0] : $cfg['uc_lang']['credits'][0][0];
        $_LANG['exchange_points'][1] = empty($cfg['uc_lang']['credits'][1][0])? $_LANG['exchange_points'][1] : $cfg['uc_lang']['credits'][1][0];
    }
    $sql = "SELECT user_id, user_name, pay_points, rank_points FROM " . $ecs->table('users')  . " WHERE user_id='$user_id'";
    $row = $db->getRow($sql);
    if ($_CFG['integrate_code'] == 'ucenter')
    {
        $exchange_type = 'ucenter';
        $to_credits_options = array();
        $out_exchange_allow = array();
        foreach ($rule as $credit)
        {
            $out_exchange_allow[$credit['appiddesc'] . '|' . $credit['creditdesc'] . '|' . $credit['creditsrc']] = $credit['ratio'];
            if (!array_key_exists($credit['appiddesc']. '|' .$credit['creditdesc'], $to_credits_options))
            {
                $to_credits_options[$credit['appiddesc']. '|' .$credit['creditdesc']] = $credit['title'];
            }
        }
        $smarty->assign('selected_org', $rule[0]['creditsrc']);
        $smarty->assign('selected_dst', $rule[0]['appiddesc']. '|' .$rule[0]['creditdesc']);
        $smarty->assign('descreditunit', $rule[0]['unit']);
        $smarty->assign('orgcredittitle', $_LANG['exchange_points'][$rule[0]['creditsrc']]);
        $smarty->assign('descredittitle', $rule[0]['title']);
        $smarty->assign('descreditamount', round((1 / $rule[0]['ratio']), 2));
        $smarty->assign('to_credits_options', $to_credits_options);
        $smarty->assign('out_exchange_allow', $out_exchange_allow);
    }
    else
    {
        $exchange_type = 'other';

        $bbs_points_name = $user->get_points_name();
        $total_bbs_points = $user->get_points($row['user_name']);

        /* 论坛积分 */
        $bbs_points = array();
        foreach ($bbs_points_name as $key=>$val)
        {
            $bbs_points[$key] = array('title'=>$_LANG['bbs'] . $val['title'], 'value'=>$total_bbs_points[$key]);
        }

        /* 兑换规则 */
        $rule_list = array();
        foreach ($rule as $key=>$val)
        {
            $rule_key = substr($key, 0, 1);
            $bbs_key = substr($key, 1);
            $rule_list[$key]['rate'] = $val;
            switch ($rule_key)
            {
                case TO_P :
                    $rule_list[$key]['from'] = $_LANG['bbs'] . $bbs_points_name[$bbs_key]['title'];
                    $rule_list[$key]['to'] = $_LANG['pay_points'];
                    break;
                case TO_R :
                    $rule_list[$key]['from'] = $_LANG['bbs'] . $bbs_points_name[$bbs_key]['title'];
                    $rule_list[$key]['to'] = $_LANG['rank_points'];
                    break;
                case FROM_P :
                    $rule_list[$key]['from'] = $_LANG['pay_points'];$_LANG['bbs'] . $bbs_points_name[$bbs_key]['title'];
                    $rule_list[$key]['to'] =$_LANG['bbs'] . $bbs_points_name[$bbs_key]['title'];
                    break;
                case FROM_R :
                    $rule_list[$key]['from'] = $_LANG['rank_points'];
                    $rule_list[$key]['to'] = $_LANG['bbs'] . $bbs_points_name[$bbs_key]['title'];
                    break;
            }
        }
        $smarty->assign('bbs_points', $bbs_points);
        $smarty->assign('rule_list',  $rule_list);
    }
    $smarty->assign('shop_points', $row);
    $smarty->assign('exchange_type',     $exchange_type);
    $smarty->assign('action',     $action);
    $smarty->assign('lang',       $_LANG);
    $smarty->display('user_transaction.dwt');
}
elseif ($action == 'act_transform_points')
{
    $rule_index = empty($_POST['rule_index']) ? '' : trim($_POST['rule_index']);
    $num = empty($_POST['num']) ? 0 : intval($_POST['num']);


    if ($num <= 0 || $num != floor($num))
    {
        show_message($_LANG['invalid_points'], $_LANG['transform_points'], 'user.php?act=transform_points');
    }

    $num = floor($num); //格式化为整数

    $bbs_key = substr($rule_index, 1);
    $rule_key = substr($rule_index, 0, 1);

    $max_num = 0;

    /* 取出用户数据 */
    $sql = "SELECT user_name, user_id, pay_points, rank_points FROM " . $ecs->table('users') . " WHERE user_id='$user_id'";
    $row = $db->getRow($sql);
    $bbs_points = $user->get_points($row['user_name']);
    $points_name = $user->get_points_name();

    $rule = array();
    if ($_CFG['points_rule'])
    {
        $rule = unserialize($_CFG['points_rule']);
    }
    list($from, $to) = explode(':', $rule[$rule_index]);

    $max_points = 0;
    switch ($rule_key)
    {
        case TO_P :
            $max_points = $bbs_points[$bbs_key];
            break;
        case TO_R :
            $max_points = $bbs_points[$bbs_key];
            break;
        case FROM_P :
            $max_points = $row['pay_points'];
            break;
        case FROM_R :
            $max_points = $row['rank_points'];
    }

    /* 检查积分是否超过最大值 */
    if ($max_points <=0 || $num > $max_points)
    {
        show_message($_LANG['overflow_points'], $_LANG['transform_points'], 'user.php?act=transform_points' );
    }

    switch ($rule_key)
    {
        case TO_P :
            $result_points = floor($num * $to / $from);
            $user->set_points($row['user_name'], array($bbs_key=>0 - $num)); //调整论坛积分
            log_account_change($row['user_id'], 0, 0, 0, $result_points, $_LANG['transform_points'], ACT_OTHER);
            show_message(sprintf($_LANG['to_pay_points'],  $num, $points_name[$bbs_key]['title'], $result_points), $_LANG['transform_points'], 'user.php?act=transform_points');

        case TO_R :
            $result_points = floor($num * $to / $from);
            $user->set_points($row['user_name'], array($bbs_key=>0 - $num)); //调整论坛积分
            log_account_change($row['user_id'], 0, 0, $result_points, 0, $_LANG['transform_points'], ACT_OTHER);
            show_message(sprintf($_LANG['to_rank_points'], $num, $points_name[$bbs_key]['title'], $result_points), $_LANG['transform_points'], 'user.php?act=transform_points');

        case FROM_P :
            $result_points = floor($num * $to / $from);
            log_account_change($row['user_id'], 0, 0, 0, 0-$num, $_LANG['transform_points'], ACT_OTHER); //调整商城积分
            $user->set_points($row['user_name'], array($bbs_key=>$result_points)); //调整论坛积分
            show_message(sprintf($_LANG['from_pay_points'], $num, $result_points,  $points_name[$bbs_key]['title']), $_LANG['transform_points'], 'user.php?act=transform_points');

        case FROM_R :
            $result_points = floor($num * $to / $from);
            log_account_change($row['user_id'], 0, 0, 0-$num, 0, $_LANG['transform_points'], ACT_OTHER); //调整商城积分
            $user->set_points($row['user_name'], array($bbs_key=>$result_points)); //调整论坛积分
            show_message(sprintf($_LANG['from_rank_points'], $num, $result_points, $points_name[$bbs_key]['title']), $_LANG['transform_points'], 'user.php?act=transform_points');
    }
}
elseif ($action == 'act_transform_ucenter_points')
{
    $rule = array();
    if ($_CFG['points_rule'])
    {
        $rule = unserialize($_CFG['points_rule']);
    }
    $shop_points = array(0 => 'rank_points', 1 => 'pay_points');
    $sql = "SELECT user_id, user_name, pay_points, rank_points FROM " . $ecs->table('users')  . " WHERE user_id='$user_id'";
    $row = $db->getRow($sql);
    $exchange_amount = intval($_POST['amount']);
    $fromcredits = intval($_POST['fromcredits']);
    $tocredits = trim($_POST['tocredits']);
    $cfg = unserialize($_CFG['integrate_config']);
    if (!empty($cfg))
    {
        $_LANG['exchange_points'][0] = empty($cfg['uc_lang']['credits'][0][0])? $_LANG['exchange_points'][0] : $cfg['uc_lang']['credits'][0][0];
        $_LANG['exchange_points'][1] = empty($cfg['uc_lang']['credits'][1][0])? $_LANG['exchange_points'][1] : $cfg['uc_lang']['credits'][1][0];
    }
    list($appiddesc, $creditdesc) = explode('|', $tocredits);
    $ratio = 0;

    if ($exchange_amount <= 0)
    {
        show_message($_LANG['invalid_points'], $_LANG['transform_points'], 'user.php?act=transform_points');
    }
    if ($exchange_amount > $row[$shop_points[$fromcredits]])
    {
        show_message($_LANG['overflow_points'], $_LANG['transform_points'], 'user.php?act=transform_points');
    }
    foreach ($rule as $credit)
    {
        if ($credit['appiddesc'] == $appiddesc && $credit['creditdesc'] == $creditdesc && $credit['creditsrc'] == $fromcredits)
        {
            $ratio = $credit['ratio'];
            break;
        }
    }
    if ($ratio == 0)
    {
        show_message($_LANG['exchange_deny'], $_LANG['transform_points'], 'user.php?act=transform_points');
    }
    $netamount = floor($exchange_amount / $ratio);
    include_once(ROOT_PATH . './includes/lib_uc.php');
    $result = exchange_points($row['user_id'], $fromcredits, $creditdesc, $appiddesc, $netamount);
    if ($result === true)
    {
        $sql = "UPDATE " . $ecs->table('users') . " SET {$shop_points[$fromcredits]}={$shop_points[$fromcredits]}-'$exchange_amount' WHERE user_id='{$row['user_id']}'";
        $db->query($sql);
        $sql = "INSERT INTO " . $ecs->table('account_log') . "(user_id, {$shop_points[$fromcredits]}, change_time, change_desc, change_type)" . " VALUES ('{$row['user_id']}', '-$exchange_amount', '". gmtime() ."', '" . $cfg['uc_lang']['exchange'] . "', '98')";
        $db->query($sql);
        show_message(sprintf($_LANG['exchange_success'], $exchange_amount, $_LANG['exchange_points'][$fromcredits], $netamount, $credit['title']), $_LANG['transform_points'], 'user.php?act=transform_points');
    }
    else
    {
        show_message($_LANG['exchange_error_1'], $_LANG['transform_points'], 'user.php?act=transform_points');
    }
}
/* 清除商品浏览历史 */
elseif ($action == 'clear_history')
{
    setcookie('ECS[history]',   '', 1);
}

//代理商品查看
elseif($action == 'agent_goods'){
    $page = empty(intval($_GET['page']))?1:intval($_GET['page']);
    $_GET['page'] = $page;
    $sql = "select goods_data from {$ecs->table('agent_goods')} where agent_id={$_SESSION['user_id']}";
    $goods_data = $db->getOne($sql);
    if(empty($goods_data)){
        $goods_data = array();
    }else{
        $goods_data = json_decode($goods_data,true);
    }
    $count = count($goods_data);
    if(!empty($count)){
        $condition = array('act'=>$action);
        $select_supplier_id = intval($_GET['supplier_id']);
        if(!empty($select_supplier_id)){
            $condition['supplier_id'] = $select_supplier_id;
        }
        $select_grade_id = intval($_GET['grade_id']);
        if(!empty($select_grade_id)){
            $condition['grade_id'] = $select_grade_id;
        }
        $goods_ids = array_keys($goods_data);
        $sql = "select distinct s.supplier_name,s.supplier_id from {$ecs->table('goods')} g "
        . "inner join {$ecs->table('supplier')} s on s.supplier_id=g.supplier_id where goods_id in(".implode(',', $goods_ids).")";
        $supplier_list = $db->getAll($sql);
        $smarty->assign('supplier_list',$supplier_list);
        //对商品进行了筛选，重新获取count值
        if(!empty($select_grade_id)){
            $goods_ids = array();
            foreach($goods_data as $key=>$value){
                if($value['grade_id']==$select_grade_id){
                    $goods_ids[] = $key;
                }
            }
        }
        if(!empty($select_supplier_id)&&!empty($goods_ids)){
            $sql = "select count(*) from {$ecs->table('goods')} where goods_id in(".implode(',', $goods_ids).") and supplier_id=$select_supplier_id";
            $count = $db->getOne($sql);
        }else{
            $count = count($goods_ids);
        }
        if(!empty($count)){
            $pager = get_pager('/user.php', $condition, $count, $page, 15);
            $smarty->assign('pager',$pager);
            $goods_ids = array_splice($goods_ids, $pager['start'],$pager['size']);
            $sql = "select g.goods_id,g.goods_name,g.goods_sn,g.shop_price,g.supplier_id,s.supplier_name,s.company_name from {$ecs->table('goods')} g "
            . "inner join {$ecs->table('supplier')} s on s.supplier_id=g.supplier_id where goods_id in(".implode(',', $goods_ids).")";
            $goods_list = $db->getAll($sql);
            if(!empty($goods_list)){
                $supplier_ids = array();
                foreach ($goods_list as $key=>$value){
                    if(!in_array($value['supplier_id'], $supplier_ids)){
                        $supplier_ids[] = $value['supplier_id'];
                    }
                }
                $sql = "select * from {$ecs->table('agent_grade')} where supplier_id in(".implode(',', $supplier_ids).")";
                $grade_list = $db->getAll($sql);
                $new_grade_list = array();
                foreach ($grade_list as $value){
                    $new_grade_list[$value['supplier_id']][$value['grade_id']] = $value['grade_name'];
                }
                foreach ($goods_list as $key=>$value){
                    $goods_list[$key]['grade_price'] = $goods_data[$value['goods_id']]['grade_price'];
                    $goods_list[$key]['grade_name'] = $new_grade_list[$value['supplier_id']][$goods_data[$value['goods_id']]['grade_id']];
                }
            }
        }
    }
    $smarty->assign('goods_list',$goods_list);
    $smarty->assign('grade_list',array(1=>'一级代理商',2=>'二级代理商',3=>'三级代理商',4=>'四级代理商',));
    $smarty->display('user_agent.dwt');
}


//获取配送的订单
elseif($action == 'delivery_order'){
    $page = empty(intval($_GET['page']))?1:intval($_GET['page']);
    $_GET['page'] = $page;
    $sql = "select count(*) from {$ecs->table('order_delivery')} where user_id={$_SESSION['user_id']}";
    $count = $db->getOne($sql);
    $pager = get_pager('/user.php', array('act'=>$action), $count, $page, 15);
    if(!empty($count)){
        $sql = "select og.order_id,og.goods_name,og.goods_number,o.order_sn,u.dp_name,og.goods_id from {$ecs->table('order_delivery')} "
        ."do inner join {$ecs->table('order_info')} o on o.order_id=do.order_id inner join {$ecs->table('order_goods')} og on og.rec_id=do.rec_id "
        ."left join {$ecs->table('users')} u on u.user_id=o.user_id where do.user_id={$_SESSION['user_id']} order by do.add_time desc "
        ."limit {$pager['start']},{$pager['size']}";
        $order_list = $db->getAll($sql);
        $new_order_list = array();
        foreach($order_list as $value){
            $new_order_list[$value['order_id']]['order_sn'] = $value['order_sn'];
            $new_order_list[$value['order_id']]['dp_name'] = $value['dp_name'];
            $new_order_list[$value['order_id']]['goods_list'][$value['goods_id']]['goods_name'] = $value['goods_name'];
            $new_order_list[$value['order_id']]['goods_list'][$value['goods_id']]['goods_number'] = $value['goods_number'];
        }
    }
    $smarty->assign('order_list',$new_order_list);
    $smarty->assign('pager',$pager);
    $smarty->display('user_delivery_order.dwt');
}


//查看配送订单详情
elseif($action == 'delivery_order_detail'){
    $rec_id = intval($_GET['rec_id']);
    if(empty($rec_id)){
        show_message('参数传递有误', '', '', 'error');
    }
    $sql = "select o.order_id,o.order_sn,o.order_status,o.consignee,o.country,o.province,o.city,o.district,o.address,"
    ."o.zipcode,o.tel,mobile,o.email,o.pay_name,o.add_time,o.supplier_id,o.user_id,o.froms,og.goods_name,og.goods_sn,"
    ."og.goods_id,og.goods_number,og.market_price,og.goods_price as order_goods_price,g.shop_price as shop_goods_price,"
    ."g.goods_thumb,c.cat_name,s.supplier_name,s.company_name,s.address as supplier_address from {$ecs->table('order_delivery')} "
    ."od inner join {$ecs->table('order_info')} o on o.order_id=od.order_id inner join {$ecs->table('order_goods')} "
    ."og on og.rec_id=od.rec_id inner join {$ecs->table('supplier')} s on s.supplier_id=od.supplier_id left join "
    ."{$ecs->table('goods')} g on g.goods_id=od.goods_id left join {$ecs->table('category')} c on c.cat_id=g.cat_id "
    ."where od.rec_id=$rec_id and od.user_id={$_SESSION['user_id']}";
    $order_info = $db->getRow($sql);
    $sql = "select region_id,region_name from {$ecs->table('region')} where region_id in({$order_info['country']},"
    ."{$order_info['province']},{$order_info['district']},{$order_info['city']}) order by region_type asc";
    $region_list = $db->getAll($sql);
    $area_info = array();
    foreach ($region_list as $value){
        $area_info[] = $value['region_name'];
    }
    $smarty->assign('area_info',implode(' ', $area_info));
    $smarty->assign('order_info',$order_info);
    $smarty->display('user_delivery_order_detail.dwt');
}

//生成随机数 by wang
function random($length = 6, $numeric = 0) {
    PHP_VERSION < '4.2.0' && mt_srand((double) microtime() * 1000000);
    if ($numeric) {
        $hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
    } else {
        $hash = '';
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
    }
    return $hash;
}

function check_register($username,$mobile_phone,$vcode,$password){
    global $_LANG,$ecs,$db;
    if (strlen($username) < 3)
    {
        show_message($_LANG['passport_js']['username_shorter']);
    }
    if(empty($mobile_phone)){
        show_message('手机号码不得为空','','','error');
    }
    if(!preg_match('/^1[3|4|5|7|8]\d{9}$/', $mobile_phone)){
        show_message('手机号码格式不合法','','','error');
    }
    $sql = "select count(*) from {$ecs->table('users')} where mobile_phone=$mobile_phone or user_name=$mobile_phone";
    $count = $db->getOne($sql);
    if(!empty($count)){
        show_message('你输入的手机号已经注册使用了','','','error');
    }
    if (strlen($password) < 6)
    {
        show_message($_LANG['passport_js']['password_shorter'],'','','error');
    }
    if (strpos($password, ' ') > 0)
    {
        show_message($_LANG['passwd_balnk'],'','','error');
    }
    if(empty($vcode)){
        show_message('短信验证码不得为空','','','error');
    }
    $sql = "select code,add_time from {$ecs->table('users_vcode')} where mobile=$mobile_phone";
    $vcode_info = $db->getRow($sql);
    if(empty($vcode_info)){
        show_message('请点击“获取短信验证码”按钮获取短信验证码','','','error');
    }
    if(strval($vcode_info['code'])!==$vcode){
        show_message('短信验证码输入有误','','','error');
    }
    if(time()-$vcode_info['add_time']>1800){
        show_message('短信验证码已过期，请重新获取','','','error');
    }
}

function register_success($username,$other=array()){
    //注册成功
        /* 设置成登录状态 */
       // $GLOBALS['user']->set_session($username);
      //  $GLOBALS['user']->set_cookie($username);

        /* 注册送积分 */
        if (!empty($GLOBALS['_CFG']['register_points']))
        {
            log_account_change($_SESSION['user_id'], 0, 0, $GLOBALS['_CFG']['register_points'], $GLOBALS['_CFG']['register_points'], $GLOBALS['_LANG']['register_points']);
        }

        /*推荐处理*/
        $affiliate  = unserialize($GLOBALS['_CFG']['affiliate']);
        if (isset($affiliate['on']) && $affiliate['on'] == 1)
        {
            // 推荐开关开启
            $up_uid     = get_affiliate();
            empty($affiliate) && $affiliate = array();
            $affiliate['config']['level_register_all'] = intval($affiliate['config']['level_register_all']);
            $affiliate['config']['level_register_up'] = intval($affiliate['config']['level_register_up']);
            if ($up_uid)
            {
                if (!empty($affiliate['config']['level_register_all']))
                {
                    if (!empty($affiliate['config']['level_register_up']))
                    {
                        $rank_points = $GLOBALS['db']->getOne("SELECT rank_points FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id = '$up_uid'");
                        if ($rank_points + $affiliate['config']['level_register_all'] <= $affiliate['config']['level_register_up'])
                        {
                            log_account_change($up_uid, 0, 0, $affiliate['config']['level_register_all'], 0, sprintf($GLOBALS['_LANG']['register_affiliate'], $_SESSION['user_id'], $username));
                        }
                    }
                    else
                    {
                        log_account_change($up_uid, 0, 0, $affiliate['config']['level_register_all'], 0, $GLOBALS['_LANG']['register_affiliate']);
                    }
                }

                //设置推荐人
                $sql = 'UPDATE '. $GLOBALS['ecs']->table('users') . ' SET parent_id = ' . $up_uid . ' WHERE user_id = ' . $_SESSION['user_id'];

                $GLOBALS['db']->query($sql);
            }
        }

        //定义other合法的变量数组
        $other_key_array = array('msn', 'qq', 'office_phone', 'home_phone', 'mobile_phone', 'sina_weibo_id');
        $update_data['reg_time'] = local_strtotime(local_date('Y-m-d H:i:s'));
        if ($other)
        {
            foreach ($other as $key=>$val)
            {
                //删除非法key值
                if (!in_array($key, $other_key_array))
                {
                    unset($other[$key]);
                }
                else
                {
                    $other[$key] =  htmlspecialchars(trim($val)); //防止用户输入javascript代码
                }
            }
            $update_data = array_merge($update_data, $other);
        }
        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('users'), $update_data, 'UPDATE', 'user_id = ' . $_SESSION['user_id']);

        update_user_info();      // 更新用户信息
        recalculate_price();     // 重新计算购物车中的商品价格

        return true;
}

//获取地区列表
function getAreaList($parent_id=0){
    $sql = 'SELECT region_id, region_name,region_type FROM ' . $GLOBALS['ecs']->table('region') .
            " WHERE parent_id = '$parent_id'";
    return $GLOBALS['db']->getAll($sql);
}

function check_register2($dp_name,$real_name,$country_id,$province_id,$city_id,$area_id,$address,$picture){
    global $_LANG,$ecs,$db;
    /*if(empty($dp_name)){
        exit(json_encode(array('error'=>'酒商名称不能为空')));
    }
    $sql = "select count(*) from {$ecs->table('users')} where dp_name='$dp_name'";
    if(!empty($db->getOne($sql))){
        exit(json_encode(array('error'=>'酒商已存在')));
    }*/
    if(empty($real_name)){
        exit(json_encode(array('error'=>'联系人姓名不得为空')));
    }
    if(mb_strlen($real_name, 'utf8')>5){
        exit(json_encode(array('error'=>'联系人姓名长度不得超过5位')));
    }
    if(empty($country_id) || empty($province_id) || empty($city_id) || empty($area_id)){
        exit(json_encode(array('error'=>'请补充完整区域选择')));
    }
    $sql = "select region_id,parent_id from {$ecs->table('region')} where region_id in($province_id,$city_id,$area_id)";
    $region_list = $db->getAll($sql);
    $flag = true;
    $new_region_list = array();
    foreach($region_list as $value){
        $new_region_list[intval($value['region_id'])] = intval($value['parent_id']);
    }
    unset($region_list);
    if($new_region_list[$province_id] !== $country_id){
        $flag = false;
    }
    if($new_region_list[$city_id] !== $province_id){
        $flag = false;
    }
    if($new_region_list[$area_id] !== $city_id){
        $flag = false;
    }
    if($flag===false){
        exit(json_encode(array('error'=>'所在区域选择有误')));
    }
    unset($flag);
    if(empty($address)){
        exit(json_encode(array('error'=>'详细地址不得为空')));
    }
    if(mb_strlen($address,'utf8')>120){
        exit(json_encode(array('error'=>'详细地址长度不得大于120字')));
    }
    if(!empty($picture)){
        $picture = ROOT_PATH.$picture;
        if(!(is_file($picture)&&file_exists($picture))){
            exit(json_encode(array('error'=>'请上传合法的营业执照')));
        }
    }
}
function get_regions_wap($region_id){
    $sql = 'SELECT region_id,region_name FROM ' . $GLOBALS['ecs']->table('region') .
            " WHERE parent_id = '$region_id' ";
    return $GLOBALS['db']->getAll($sql);
}

/**
 * 去个人中心获取
 * **/
function getUserFromCenter($username){
    include ROOT_PATH_WAP.'OcApi/OCenter/OCenter.php';
    $Ocapi = new OCApi();
    return $Ocapi->ocGetUserInfo("mobile='$username' or email='$username'");
}

function get_api_tacket($appId){
    $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appId.'&secret=0497592b1835c43102d1063edd22e96f';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $output = curl_exec($ch);
    curl_close($ch);
    $output = json_decode($output,true);
    $access_token = $output['access_token'];
    $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$access_token.'&type=jsapi';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

/**
    劲酒抽奖活动
 *  */
function jinjiuchoujiang($order_id){
    require_once(ROOT_PATH . 'includes/lib_jingjiuchoujiang.php');
    create_code($_SESSION['user_id'],$order_id);
}

?>