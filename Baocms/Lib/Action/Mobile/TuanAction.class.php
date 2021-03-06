<?php

/*
 * 软件为合肥生活宝网络公司出品，未经授权许可不得使用！
 * 作者：baocms团队
 * 官网：www.baocms.com
 * 邮件: youge@baocms.com  QQ 800026911
 */

class TuanAction extends CommonAction {

    public function main() {
        $aready = (int) $this->_param('aready');
        $this->assign('aready', $aready);
        $this->display();
    }

    public function mainload() {
        $aready = (int) $this->_param('aready');
        $t = D('Tuan');
        if ($aready == 1) {
            $order = 'create_time desc';
        } elseif ($aready == 2) {
            $order = 'sold_num desc';
        } elseif ($aready == 3) {
            $order = 'views desc';
        }
        import('ORG.Util.Page'); // 导入分页类
        $map = array('audit' => 1, 'closed' => 0, 'city_id' => $this->city_id, 'end_date' => array('EGT', TODAY));
        $count = $t->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $t->where($map)->order($order)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('tuans', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出

        $this->display();
    }

    public function push() {  // 这里的代码在mobile首页被调用。新版6.0重新编写
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));

        if (empty($lat) || empty($lng)) {
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        if ($this->uid) {
            $looks = D('Userslook')->where(array('user_id' => $this->uid))->order(array('look_id' => 'desc'))->limit(0, 50)->select();
            if (count($looks) > 20) { //保险起见
                $shop_ids = array();
                foreach ($looks as $val) {
                    $shop_ids[$val['shop_id']] = $val['shop_id'];
                }
                $tuans = D('Tuan')->order(" (ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc ")->where(array('shop_id' => array('IN', $shop_ids), 'closed' => 0,'city_id'=>$this->city_id, 'audit' => 1, 'bg_date' => array('ELT', TODAY), 'end_date' => array('EGT', TODAY)))->limit(0, 10)->select();
            } else {
                $tuans = D('Tuan')->order(" (ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc ")->where(array('closed' => 0,'city_id'=>$this->city_id, 'audit' => 1, 'bg_date' => array('ELT', TODAY), 'end_date' => array('EGT', TODAY)))->limit(0, 10)->select();
            }
        } else {
            $tuans = D('Tuan')->order(" (ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc ")->where(array('closed' => 0,'city_id'=>$this->city_id, 'audit' => 1, 'bg_date' => array('ELT', TODAY), 'end_date' => array('EGT', TODAY)))->limit(0, 10)->select();
        }

        foreach ($tuans as $k => $val) {
            $tuans[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
        }
        $this->assign('tuans', $tuans);

        $this->display();
    }

    public function tuancate() {
        $this->display();
    }

    public function index() {
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        $cat = (int) $this->_param('cat');
        $this->assign('cat', $cat);
        //$areas = D('Area')->fetchAll();
        //$area = (int) $this->_param('area');

        //$this->assign('area_id', $area);

        //$this->assign('areas', $areas);
        $order = $this->_param('order', 'htmlspecialchars');
        $this->assign('order', $order);
        $biz = D('Business')->fetchAll();
        $business = (int) $this->_param('business');
        $this->assign('business_id', $business);
        $this->assign('biz', $biz);
        $this->assign('nextpage', LinkTo('tuan/loaddata', array('cat' => $cat, 'area' => $area, 'business' => $business, 'order' => $order, 't' => NOW_TIME, 'keyword' => $keyword, 'p' => '0000')));
        $this->display(); // 输出模板
    }

    public function loaddata() {
        $Tuan = D('Tuan');
        import('ORG.Util.Page'); // 导入分页类
         $map = array('audit' => 1, 'closed' => 0, 'city_id' => $this->city_id, 'end_date' => array('EGT', TODAY));
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['title'] = array('LIKE', '%' . $keyword . '%');
        }
        $cat = (int) $this->_param('cat');
        if ($cat) {
            $catids = D('Tuancate')->getChildren($cat);
            if (!empty($catids)) {
                $map['cate_id'] = array('IN', $catids);
            } else {
                $map['cate_id'] = $cat;
            }
        }
        $area = (int) $this->_param('area');
        if ($area) {
            $map['area_id'] = $area;
        }
        $order = $this->_param('order', 'htmlspecialchars');

        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if (empty($lat) || empty($lng)) {
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $orderby = '';
        switch ($order) {
            case 2:
                $orderby = array('orderby' => 'asc', 'tuan_id' => 'desc');
                break;
            default:
                $orderby = " (ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc ";
                break;
        }
        $count = $Tuan->where($map)->count(); // 查询满足要求的总记录数 
        $Page = new Page($count, 30); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }

        $list = $Tuan->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $val) {
            if ($val['shop_id']) {
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $val['end_time'] = strtotime($val['end_date']) - NOW_TIME + 86400;
            $list[$k] = $val;
        }
        if ($shop_ids) {
            $shops = D('Shop')->itemsByIds($shop_ids);
            $ids = array();
            foreach ($shops as $k => $val) {
                $shops[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
                $d = getDistanceNone($lat, $lng, $val['lat'], $val['lng']);
                $ids[$d][] = $k; //防止同样的距离出现 
            }
            ksort($ids);
            $showshops = array();

            foreach ($ids as $arr1) {
                foreach ($arr1 as $val) {
                    $showshops[$val] = $shops[$val];
                }
            }

            $this->assign('shops', $showshops);
        }
        $this->assign('list', $list); // 赋值数据集
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function detail() {
        $tuan_id = (int) $this->_get('tuan_id');
        if (empty($tuan_id)) {
            $this->error('该抢购信息不存在！');
            die;
        }
        if (!$detail = D('Tuan')->find($tuan_id)) {
            $this->error('该抢购信息不存在！');
            die;
        }
        if ($detail['closed']) {
            $this->error('该抢购信息不存在！');
            die;
        }
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if (empty($lat) || empty($lng)) {
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $detail = D('Tuan')->_format($detail);
        $detail['d'] =  getDistance($lat, $lng, $detail['lat'], $detail['lng']);
        $detail['end_time'] = strtotime($detail['end_date']) - NOW_TIME + 86400;
        $this->assign('detail', $detail);
        $shop_id = $detail['shop_id'];
        $shop = D('Shop')->find($shop_id);
        $this->assign('tuans', D('Tuan')->where(array('audit' => 1, 'closed' => 0, 'shop_id' => $shop_id, 'bg_date' => array('ELT', TODAY), 'end_date' => array('EGT', TODAY), 'tuan_id' => array('NEQ', $tuan_id)))->limit(0, 5)->select());





//修复团购评分不显示
		$pingnum = D('Tuandianping')->where(array('tuan_id'=>$tuan_id))->count();
		$this->assign('pingnum', $pingnum);
		//p($pingnum);
		$score = (int) D('Tuandianping')->where(array('tuan_id'=>$tuan_id))->avg('score');
		if($score == 0){
			$score = 5;
		}
		$this->assign('score', $score);
		
//修复结束		
		
		
        $tuandetails = D('Tuandetails')->find($tuan_id);
        $this->assign('tuandetails', $tuandetails);
        $this->assign('shop', $shop);
        $this->display();
    }

    public function order() {
        if (!$this->uid) {
            $this->niuMsg('登录状态失效!', U('passport/login'));
        }

        $tuan_id = (int) $this->_get('tuan_id');
        if (!$detail = D('Tuan')->find($tuan_id)) {
            $this->niuMsg('该商品不存在');
        }
        if ($detail['closed'] == 1 || $detail['end_date'] < TODAY) {
            $this->niuMsg('该商品已经结束');
        }
        $num = (int) $this->_post('num');
        if ($num <= 0 || $num > 99) {
            $this->niuMsg('请输入正确的购买数量');
        }

        $data = array(
            'tuan_id' => $tuan_id,
            'num' => $num,
            'user_id' => $this->uid,
            'shop_id' => $detail['shop_id'],
            'create_time' => NOW_TIME,
            'create_ip' => get_client_ip(),
            'total_price' => $detail['tuan_price'] * $num,
            'mobile_fan' => $detail['mobile_fan']*$num,
            'need_pay' => $detail['tuan_price'] * $num - $detail['mobile_fan']*$num,
            'status' => 0,
            'is_mobile' => 1,
        );
        if ($order_id = D('Tuanorder')->add($data)) {
            $this->niuMsg('创建订单成功，下一步选择支付方式！',U('tuan/pay', array('order_id' => $order_id)));
            die;
        }
        $this->niuMsg('创建订单失败！');
    }

    public function buy() {
        if (empty($this->uid)) {
            header("Location: " . U('passport/login'));
            die;
        }
        $tuan_id = (int) $this->_get('tuan_id');
        if (!$detail = D('Tuan')->find($tuan_id)) {

            $this->error('该商品不存在');
            die;
        }
        if ($detail['closed'] == 1 || $detail['end_date'] < TODAY) {
            $this->error('该商品已经结束');
            die;
        }
        $detail = D('Tuan')->_format($detail);
        $this->assign('detail', $detail);
        $this->display();
    }

    public function pay() {

        if (empty($this->uid)) {
            header("Location:" . U('passport/login'));
            die;
        }

        $this->check_mobile();

        $order_id = (int) $this->_get('order_id');
        $order = D('Tuanorder')->find($order_id);
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->error('该订单不存在');
            die;
        }
        $tuan = D('Tuan')->find($order['tuan_id']);
        if (empty($tuan) || $tuan['closed'] == 1 || $tuan['end_date'] < TODAY) {
            $this->error('该抢购不存在');
            die;
        }
        $this->assign('use_integral', $tuan['use_integral'] * $order['num']);
        $this->assign('payment', D('Payment')->getPayments(true));
        $this->assign('tuan', $tuan);
        $this->assign('order', $order);
        $this->display();
    }

    public function tuan_mobile() {
        $this->mobile();
    }

    public function tuan_mobile2() {
        $this->mobile2();
    }

    public function tuan_sendsms() {
        $this->sendsms();
    }

    public function pay2() {

        if (empty($this->uid)) {
            $this->niuMsg('登录状态失效!', U('passport/login'));
        }

        $order_id = (int) $this->_get('order_id');
        $order = D('Tuanorder')->find($order_id);

        if (empty($order) || (int) $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->niuMsg('该订单不存在');
        }
        if (!$code = $this->_post('code')) {
            $this->niuMsg('请选择支付方式！');
        }
        $pay_mode = "在线支付";


        if ($code == 'wait') { // 到店付 将不会有支付记录，并且不能使用在线的积分
            $pay_mode = "货到支付";
            $codes = array();
            $obj = D('Tuancode');
            if (D('Tuanorder')->save(array('order_id' => $order_id, 'status' => '-1'))) { //更新成到店付的状态
                $tuan = D('Tuan')->find($order['tuan_id']);
                for ($i = 0; $i < $order['num']; $i++) {
                    $local = $obj->getCode();
                    $insert = array(
                        'user_id' => $this->uid,
                        'shop_id' => $tuan['shop_id'],
                        'order_id' => $order['order_id'],
                        'tuan_id' => $order['tuan_id'],
                        'code' => $local,
                        'price' => 0, //价值用0来代替了这样就说明他是到店付
                        'real_money' => 0, //退款的时候用
                        'real_integral' => 0, //退款的时候用
                        'fail_date' => $tuan['fail_date'],
                        'settlement_price' => 0, //结算时候
                        'create_time' => NOW_TIME,
                        'create_ip' => $ip,
                    );
                    $codes[] = $local;
                    $obj->add($insert);
                }
                D('Tuan')->updateCount($tuan['tuan_id'], 'sold_num'); //更新卖出产品
                $codestr = join(',', $codes);
                //发送短信
                D('Sms')->sendSms('sms_tuan', $this->member['mobile'], array(
                    'code' => $codestr,
                    'nickname' => $this->member['nickname'],
                    'tuan' => $tuan['title'],
                ));
                //更新贡献度
                D('Users')->prestige($this->uid, 'tuan');
                D('Sms')->tuanTZshop($tuan['shop_id']);
                
                
           //====================微信支付通知==抢购=========================
            $tuan     = D('Tuan')->find($order['tuan_id']);
            $uaddr = D('UserAddr') -> where('user_id ='.$order['user_id']) -> find();
            include_once "Baocms/Lib/Net/Wxmesg.class.php";
            $_data_pay = array(
                'url'       =>  "http://".$_SERVER['HTTP_HOST']."/mcenter/tuan/detail/order_id/".$order_id.".html",
                'topcolor'  =>  '#F55555',
                'first'     =>  '亲,您的货到付款订单成功创建！',
                'remark'    =>  '更多信息,请登录http://'.$_SERVER['HTTP_HOST'].'！感谢您的惠顾！',
                'money'     =>  ($order['need_pay']/100).'元',
                'orderInfo' =>  $tuan['title'],
                'addr'      =>  $uaddr['addr'],
                'orderNum'  =>  $order_id
            );
            $pay_data = Wxmesg::pay($_data_pay);
            $return   = Wxmesg::net($this->uid, 'OPENTM201490088', $pay_data);

            //====================微信支付通知==============================

                $this->niuMsg('恭喜您下单成功！', U('tuan/index'));
            } else {
                $this->niuMsg('您已经设置过该抢购为到店付了！');
            }
        } else {
            $payment = D('Payment')->checkPayment($code);
            if (empty($payment)) {
                $this->niuMsg('该支付方式不存在');
            }
            if (empty($order['use_integral'])) {
                $tuan = D('Tuan')->find($order['tuan_id']);
                if (empty($tuan) || $tuan['closed'] == 1 || $tuan['end_date'] < TODAY) {
                    $this->niuMsg('该抢购不存在');
                    die;
                }
                $canuse = $tuan['use_integral'] * $order['num'];
                if (!empty($this->member['integral'])) {
                    //if (!D('Lock')->lock($this->uid))
                       // $this->error('服务器繁忙，1分钟后再试');
                    $member = D('Users')->find($this->uid); //重新查是因为绕过了之前的判断是很可能的
                    $used = 0;
                    if ($member['integral'] < $canuse) {
                        $used = $member['integral'];
                        $member['integral'] = 0;
                    } else {
                        $used = $canuse;
                        $member['integral'] -= $canuse;
                    }
                    D('Users')->save(array('user_id' => $this->uid, 'integral' => $member['integral']));
                    D('Userintegrallogs')->add(array(
                        'user_id' => $this->uid,
                        'integral' => -$used,
                        'intro' => "订单" . $order_id . "积分抵用",
                        'create_time' => NOW_TIME,
                        'create_ip' => get_client_ip()
                    ));
                    $order['use_integral'] = $used;
                    $order['need_pay'] = $order['total_price'] - $order['mobile_fan'] - $used;
                    D('Tuanorder')->save($order);
                    //D('Lock')->unlock();
                }
            }


            $logs = D('Paymentlogs')->getLogsByOrderId('tuan', $order_id);
            if (empty($logs)) {
                $logs = array(
                    'type' => 'tuan',
                    'user_id' => $this->uid,
                    'order_id' => $order_id,
                    'code' => $code,
                    'need_pay' => $order['need_pay'],
                    'create_time' => NOW_TIME,
                    'create_ip' => get_client_ip(),
                    'is_paid' => 0
                );
                $logs['log_id'] = D('Paymentlogs')->add($logs);
            } else {
                $logs['need_pay'] = $order['need_pay'];
                $logs['code'] = $code;
                D('Paymentlogs')->save($logs);
            }
            
            //====================微信支付通知==抢购=========================
            $tuan     = D('Tuan')->find($order['tuan_id']);
            $uaddr = D('UserAddr') -> where('user_id ='.$order['user_id']) -> find();
            include_once "Baocms/Lib/Net/Wxmesg.class.php";
            $_data_pay = array(
                'url'       =>  "http://".$_SERVER['HTTP_HOST']."/mcenter/tuan/detail/order_id/".$order_id.".html",
                'topcolor'  =>  '#F55555',
                'first'     =>  '亲,您的在线支付订单成功创建,请尽快支付！',
                'remark'    =>  '更多信息,请登录http://'.$_SERVER['HTTP_HOST'].'！感谢您的惠顾！',
                'money'     =>  round($order['need_pay']/100).'元',
                'orderInfo' =>  $tuan['title'],
                'addr'      =>  $uaddr['addr'],
                'orderNum'  =>  $order_id
            );
            $pay_data = Wxmesg::pay($_data_pay);
            $return   = Wxmesg::net($this->uid, 'OPENTM201490088', $pay_data);

            //====================微信支付通知==============================
           $this->niuMsg('订单设置完毕，即将进入付款。',U('payment/payment', array('log_id' => $logs['log_id'])));
            die;
        }
        
    }

    public function delete() {
        $id = (int) ($_GET['order_id']);
        if (is_numeric($id) && $id > 0) {
            $map = array('order_id' => $id);
            $findone = D('Tuanorder')->where($map)->find();
            if (!empty($findone)) {
                $res = D('Tuanorder')->delete($id);
                $this->success('删除成功!');
            }
        }
    }

    public function near() {
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if (empty($lat) || empty($lng)) {
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $tuans = D('Tuan')->order(" (ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc ")->where(array('closed' => 0, 'audit' => 1, 'bg_date' => array('ELT', TODAY), 'end_date' => array('EGT', TODAY)))->limit(0, 4)->select();
        foreach ($tuans as $k => $val) {
            $tuans[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
        }
        $this->assign('tuans', $tuans);
        $this->display();
    }
	
	public function loadindex() {
		$Tuan = D('Tuan');
		import('ORG.Util.Page');
		// 导入分页类
		$map = array('audit' => 1, 'closed' => 0, 'city_id' => $this -> city_id, 'end_date' => array('EGT', TODAY));
		if ($keyword = $this -> _param('keyword', 'htmlspecialchars')) {
			$map['title'] = array('LIKE', '%' . $keyword . '%');
		}
		$cat = (int)$this -> _param('cat');
		if ($cat) {
			$catids = D('Tuancate') -> getChildren($cat);
			if (!empty($catids)) {
				$map['cate_id'] = array('IN', $catids);
			} else {
				$map['cate_id'] = $cat;
			}
		}
		$area = (int)$this -> _param('area');
		if ($area) {
			$map['area_id'] = $area;
		}
		$order = $this -> _param('order', 'htmlspecialchars');

		$lat = addslashes(cookie('lat'));
		$lng = addslashes(cookie('lng'));
		if (empty($lat) || empty($lng)) {
			$lat = $this -> city['lat'];
			$lng = $this -> city['lng'];
		}
		$orderby = array('orderby' => 'asc', 'tuan_id' => 'desc');

		$count = $Tuan -> where($map) -> count();
		// 查询满足要求的总记录数
		$Page = new Page($count, 15);
		// 实例化分页类 传入总记录数和每页显示的记录数
		$show = $Page -> show();
		// 分页显示输出
		$var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
		$p = $_GET[$var];
		if ($Page -> totalPages < $p) {
			die('0');
		}

		$list = $Tuan -> where($map) -> order($orderby) -> limit($Page -> firstRow . ',' . $Page -> listRows) -> select();
		foreach ($list as $k => $val) {
			if ($val['shop_id']) {
				$shop_ids[$val['shop_id']] = $val['shop_id'];
			}
			$val['end_time'] = strtotime($val['end_date']) - NOW_TIME + 86400;
			$list[$k] = $val;
		}
		if ($shop_ids) {
			$shops = D('Shop') -> itemsByIds($shop_ids);
			$ids = array();
			foreach ($shops as $k => $val) {
				$shops[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
				$d = getDistanceNone($lat, $lng, $val['lat'], $val['lng']);
				$ids[$d][] = $k;
				//防止同样的距离出现
			}
			ksort($ids);
			$showshops = array();

			foreach ($ids as $arr1) {
				foreach ($arr1 as $val) {
					$showshops[$val] = $shops[$val];
				}
			}

			$this -> assign('shops', $showshops);
		}
		$this -> assign('list', $list);
		// 赋值数据集
		$this -> assign('page', $show);
		// 赋值分页输出
		$this -> display();
		// 输出模板
	}

}
