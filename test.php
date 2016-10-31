<?php
define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once __DIR__.'/includes/lib_jpush.php';
app_jpush('测试个推', '测试推送标题', 1973, array('type'=>'order','order_id'=>5458));
/*app_jpush_all('测试个推', '测试推送标题', array('type'=>'activity'));*/
echo 1;exit;
exit;
/*$sql = "select region_id,region_name,region_type,parent_id from {$ecs->table('region')}";
$region_list = $db->getAll($sql);
$new_region_list = array();
foreach ($region_list as $value){
    switch ($value['region_type']){
        case 1:
            $new_region_list['province_list'][] = array(
                'region_id'=>$value['region_id'],
                'region_name'=>$value['region_name'],
                'parent_id'=>$value['parent_id']
            );
            break;
        case 2:
            $new_region_list['city_list'][] = array(
                'region_id'=>$value['region_id'],
                'region_name'=>$value['region_name'],
                'parent_id'=>$value['parent_id']
            );
            break;
        case 3:
            $new_region_list['district_list'][] = array(
                'region_id'=>$value['region_id'],
                'region_name'=>$value['region_name'],
                'parent_id'=>$value['parent_id']
            );
            break;
    }
}
exit(json_encode($new_region_list, JSON_UNESCAPED_UNICODE));*/

include_once(ROOT_PATH. "includes/wxSend/lib_wxsend.php");



/* 
 * $ordertype=1 订单发货通知
 * {{first.DATA}}
    订单内容：{{keyword1.DATA}}
    物流服务：{{keyword2.DATA}}
    快递单号：{{keyword3.DATA}}
    收货信息：{{keyword4.DATA}}
    {{remark.DATA}}
    在发送时，需要将内容中的参数（{{.DATA}}内为参数）赋值替换为需要的信息
    
    内容示例
    嗖嗖嗖，您的证照和回执已发货，我们正加速送到您的手上。
    订单内容：证件照 （居民身份证）
    物流服务：顺丰即日达
    快递单号：XW5244030005646
    收货信息：陈璐 广东省 广州市 天河区 科韵北路112号
    请您耐心等候。
 *  
 *  $ordertype=2 退款成功通知
 *  {{first.DATA}}
    退款金额：{{orderProductPrice.DATA}}
    商品详情：{{orderProductName.DATA}}
    订单编号：{{orderName.DATA}}
    {{remark.DATA}}
    在发送时，需要将内容中的参数（{{.DATA}}内为参数）赋值替换为需要的信息
    
    内容示例
    您的订单已经完成退款，¥145.25已经退回您的付款账户，请留意查收。
    
    退款金额：¥145.25
    商品详情：七匹狼正品 牛皮男士钱包 真皮钱…
    订单编号：546787944-55446467-544749
 *  
 *  
 *   $ordertype=3 订单生成通知
 *  
 *  详细内容
    {{first.DATA}}
    时间：{{keyword1.DATA}}
    商品名称：{{keyword2.DATA}}
    订单号：{{keyword3.DATA}}
    {{remark.DATA}}
    在发送时，需要将内容中的参数（{{.DATA}}内为参数）赋值替换为需要的信息
    内容示例
    订单生成通知
    时间：2014年7月21日 18:36
    商品名称：苹果
    订单号：007
    订单成功
 *  
 *  $ordertype=4 任务处理通知
 *  
 *  详细内容
    {{first.DATA}}
    任务名称：{{keyword1.DATA}}
    通知类型：{{keyword2.DATA}}
    {{remark.DATA}}
    在发送时，需要将内容中的参数（{{.DATA}}内为参数）赋值替换为需要的信息
    内容示例
    您好，您有新的待办任务
    任务名称：张三申请年假3天
    通知类型：待办
    请抽空处理
 *  
 *  $ordertype=5 自提订单提交成功通知
 *  详细内容
    {{first.DATA}}
    自提码：{{keyword1.DATA}}
    商品详情：{{keyword2.DATA}}
    提货地址：{{keyword3.DATA}}
    提货时间：{{keyword4.DATA}}
    {{remark.DATA}}
    在发送时，需要将内容中的参数（{{.DATA}}内为参数）赋值替换为需要的信息
    内容示例
    您的订单已提交成功！
    自提码：140987567809
    商品详情：水杯，1个，2元
    提货地址：朝阳区方恒国际C座宝诚发超市
    提货时间：2014-11-6 12:08 至 2014-11-8 15:00
    客服电话：4008-888-888
 *  
 *  
 *  $ordertype=6 提现成功通知
 *  详细内容
    {{first.DATA}}
    
    提现金额:{{money.DATA}}
    提现时间:{{timet.DATA}}
    {{remark.DATA}}
    在发送时，需要将内容中的参数（{{.DATA}}内为参数）赋值替换为需要的信息
    内容示例
    理财通余额资金已到账
    
    提现金额:1000.00元
    到账时间:2014-04-02 11:45:08
 *  
 *  
 *  $ordertype=7 订单提交成功通知
 *  详细内容
    {{first.DATA}}
    店铺：{{keyword1.DATA}}
    下单时间：{{keyword2.DATA}}
    商品：{{keyword3.DATA}}
    金额：{{keyword4.DATA}}
    {{remark.DATA}}
    在发送时，需要将内容中的参数（{{.DATA}}内为参数）赋值替换为需要的信息
    内容示例
    您的订单已提交成功
    店铺：有间便利店
    下单时间：2014-10-31 19:44:51
    商品：软装经典 3份
    金额：¥33.0
    您的订单我们已经收到，配货后将尽快配送~
 *  
 *  $ordertype=8 订单确认收货通知
 *  详细内容
    {{first.DATA}}
    订单号：{{keyword1.DATA}}
    商品名称：{{keyword2.DATA}}
    下单时间：{{keyword3.DATA}}
    发货时间：{{keyword4.DATA}}
    确认收货时间：{{keyword5.DATA}}
    {{remark.DATA}}
    在发送时，需要将内容中的参数（{{.DATA}}内为参数）赋值替换为需要的信息
    内容示例
    亲：您在我们商城买的宝贝已经确认收货。
    订单号：323232323232
    商品名称：最新款男鞋
    下单时间：2015 01 01 12:00
    发货时间：2015 01 01 14:00
    确认收货时间：2015 01 02 14:00
    感谢您的支持与厚爱。
 *  
 *  
 * $ordertype=9 充值成功通知 
 *  
 *  详细内容
    {{first.DATA}}
    
    充值金额:{{money.DATA}}
    充值方式:{{product.DATA}}
    {{remark.DATA}}
    在发送时，需要将内容中的参数（{{.DATA}}内为参数）赋值替换为需要的信息
    内容示例
    成功充值理财通余额
    
    充值金额:1000.00元
    充值方式:工商银行(尾号4593)/pc大额充值
 *  
 *  
 *  $ordertype=10 提现失败通知
 *  
 *  详细内容
    {{first.DATA}}
    
    提现金额:{{money.DATA}} 
    提现时间:{{time.DATA}}
    {{remark.DATA}} 
    在发送时，需要将内容中的参数（{{.DATA}}内为参数）赋值替换为需要的信息
    内容示例
    理财通余额提现失败，已将资金退回至理财通余额，点击【详情】查看取出失败原因。如有疑问请联系客服0755-86010333
    
    提现金额:1000.00元
    提现时间:2014-04-02 11:45:08
 *  
 *  {{first.DATA}}
订单号：{{keyword1.DATA}}
用户姓名：{{keyword2.DATA}}
联系电话：{{keyword3.DATA}}
退货商品：{{keyword4.DATA}}
{{remark.DATA}}
 * 您好，有新的退货通知
订单号：12345678
用户姓名：老王
联系电话：12345678910
退货商品：土豆、白菜、哈密瓜
感谢你的使用
 *  
 *  
 * {{first.DATA}} 
订单金额：{{orderProductPrice.DATA}} 
商品详情：{{orderProductName.DATA}} 
收货信息：{{orderAddress.DATA}} 
订单编号：{{orderName.DATA}} 
{{remark.DATA}}
 *  
 *  
 *  */






$sql = "select * from {$ecs->table('notice')} where type=0";
$notice_list = $db->getAll($sql);

foreach($notice_list as $info){
    $openid=$info['wx_openId'];
    $data=array(
                    'first'=>array('value'=>urlencode("您有新的配送订单取消了"),'color'=>"#743A3A"),
                    'orderProductPrice'=>array('value'=>urlencode("100元"),'color'=>'#743A3A'),
                    'orderProductName'=>array('value'=>urlencode("测试商品"),'color'=>'#743A3A'),
                    'orderAddress'=>array('value'=>urlencode("测试收货信息"),'color'=>'#743A3A'),
                    'orderName'=>array('value'=>urlencode("测试订单"),'color'=>'#743A3A'),
                    'remark'=>array('value'=>urlencode("请查看后台"),'color'=>'#743A3A')
                );
    $ordertype=18;

    $result=send_order_message($openid,$data,$url,$ordertype);
}