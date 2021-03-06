<?php
/**
 * 用户模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net> 
 * @version TS3.0
 */
class UserModel extends Model {
	protected $tableName = 'user';
	protected $error = '';
	protected $fields = array (
			0 => 'uid',
			1 => 'login',
			2 => 'password',
			3 => 'login_salt',
			4 => 'uname',
			5 => 'email',
			6 => 'sex',
			7 => 'location',
			8 => 'is_audit',
			9 => 'is_active',
			10 => 'is_init',
			11 => 'ctime',
			12 => 'identity',
			13 => 'api_key',
			14 => 'domain',
			15 => 'province',
			16 => 'city',
			17 => 'area',
			18 => 'reg_ip',
			19 => 'lang',
			20 => 'timezone',
			21 => 'is_del',
			22 => 'first_letter',
			23 => 'intro',
			24 => 'last_login_time',
			25 => 'last_feed_id',
			26 => 'last_post_time',
			27 => 'search_key',
			28 => 'invite_code',
			28 => 'invite_code',
			29 => 'feed_email_time',
			30 => 'send_email_time',
	        31 => 'birthday',
			32=>'utype',
			33=>'nation',
			34 => 'z-code',
			35=>'education',
			36=>'p-level',
			37=>'post',
			38=>'weixin',
			39=>'qq',
			40=>'post',
			41=>'school-age',
			42=>'subject',
			43=>'reg_source',
			'_autoinc' => true,
			'_pk' => 'uid' 
	);
	
	/**
	 * 获取用户列表，后台可以根据用户组查询
	 * 
	 * @param integer $limit
	 *        	结果集数目，默认为20
	 * @param array $map
	 *        	查询条件
	 * @return array 用户列表信息
	 */
	public function getUserList($limit = 20, $map = array(), $order = "uid DESC") {
		// 添加用户表的查询，用于关联查询
		// $table = $this->tablePrefix."user AS u";
		if (isset ( $_POST )) {
			$_POST ['uid'] && $map ['uid'] = intval ( $_POST ['uid'] );
			$_POST ['uname'] && $map ['uname'] = array('LIKE', '%'.t($_POST['uname']).'%');
			$_POST ['email'] && $map ['email'] = array('LIKE', '%'.t($_POST['email']).'%');
			isset ( $_POST ['is_audit'] ) && $map ['is_audit'] = intval ( $_POST ['is_audit'] );
			! empty ( $_POST ['sex'] ) && $map ['sex'] = intval ( $_POST ['sex'] );
			
			// 注册时间判断，ctime为数组格式
			if (! empty ( $_POST ['ctime'] )) {
				if (! empty ( $_POST ['ctime'] [0] ) && ! empty ( $_POST ['ctime'] [1] )) {
					// 时间区间条件
					$map ['ctime'] = array (
							'BETWEEN',
							array (
									strtotime ( $_POST ['ctime'] [0] ),
									strtotime ( $_POST ['ctime'] [1] ) 
							) 
					);
				} else if (! empty ( $_POST ['ctime'] [0] )) {
					// 时间大于条件
					$map ['ctime'] = array (
							'GT',
							strtotime ( $_POST ['ctime'] [0] ) 
					);
				} elseif (! empty ( $_POST ['ctime'] [1] )) {
					// 时间小于条件
					$map ['ctime'] = array (
							'LT',
							strtotime ( $_POST ['ctime'] [1] ) 
					);
				}
			}
			
			// 用户部门信息过滤
			/*
			 * if(!empty($_POST['department'])) { $table .= " left join {$this->tablePrefix}user_department d on u.uid = d.uid and d.department_id = '".intval($_POST['department'])."'"; }
			 */
			
			// 用户组信息过滤
			if (! $_POST ['uid']) {
				if (! empty ( $_POST ['user_group'] )) {
					if (is_array ( $_POST ['user_group'] )) {
						$user_group_id = " user_group_id IN ('" . implode ( "','", $_POST ['user_group'] ) . "') ";
					} else {
						$user_group_id = " user_group_id = '{$_POST['user_group']}' ";
					}
					// 关联查询，用户查询出指定用户组的用户信息
					// $table .= ' LEFT JOIN (SELECT MAX(id), uid FROM `'.$this->tablePrefix.'user_group_link` WHERE '.$user_group_id.' GROUP BY uid) AS b ON u.uid = b.uid';
					$group_uid = getSubByKey ( D ( 'user_group_link' )->where ( 'user_group_id=' . intval ( $_POST ['user_group'] ) )->findAll (), 'uid' );
					$map ['uid'] = array (
							'in',
							$group_uid 
					);
				}
				
				// 用户标签过滤
				if (! empty ( $_POST ['user_category'] )) {
					// $table .= ' LEFT JOIN '.$this->tablePrefix.'user_category_link AS l ON l.uid = u.uid WHERE user_category_id = '.intval($_POST['user_category']);
					$title = D('user_category')->where('user_category_id='.intval($_POST['user_category']))->getField('title');
					$tagId = D('tag')->where('name=\''.t($title).'\'')->getField('tag_id');
					$a['app'] = 'public';
					$a['table'] = 'user';
					$a['tag_id'] = intval ($tagId);
					$tag_uid = getSubByKey ( D ( 'app_tag' )->where ( $a )->findAll (), 'row_id' );
					if ($group_uid) {
						$map ['uid'] = array (
								'in',
								array_intersect ( $group_uid, $tag_uid ) 
						);
					} else {
						$map ['uid'] = array (
								'in',
								$tag_uid 
						);
					}
				}
			}
		}
		
		// 查询数据
		$list = $this->where ( $map )->order ( $order )->findPage ( $limit );
		
		// 数据组装
		$userGroupHash = array ();
		$uids = array ();
		foreach ( $list ['data'] as $k => $v ) {
			$userGroupHash [$v ['uid']] = array ();
			$uids [] = $v ['uid'];
			$list ['data'] [$k] ['user_group'] = &$userGroupHash [$v ['uid']];
		}
		$gmap ['uid'] = array (
				'IN',
				$uids 
		);
		$userGroupLink = D ( 'user_group_link' )->where ( $gmap )->findAll ();
		foreach ( $userGroupLink as $v ) {
			$userGroupHash [$v ['uid']] [] = $v ['user_group_id'];
		}
		
		return $list;
	}
	
	/**
	 * 获取用户列表信息 - 未分页型
	 * 
	 * @param array $map
	 *        	查询条件
	 * @param integer $limit
	 *        	结果集数目，默认为20
	 * @param string $field
	 *        	需要显示的字段，多个字段之间使用“,”分割，默认显示全部
	 * @param string $order
	 *        	排序条件，默认uid DESC
	 * @return array 用户列表信息
	 */
	public function getList($map = array(), $limit = 20, $field = '*', $order = 'uid DESC') {
		$data = $this->where ( $map )->limit ( $limit )->field ( $field )->order ( $order )->findAll ();
		return $data;
	}
	
	/**
	 * 获取用户列表信息 - 分页型
	 * 
	 * @param array $map
	 *        	查询条件
	 * @param integer $limit
	 *        	结果集数目，默认为20
	 * @param string $field
	 *        	需要显示的字段，多个字段之间使用“,”分割，默认显示全部
	 * @param string $order
	 *        	排序条件，默认uid DESC
	 * @return array 用户列表信息
	 */
	public function getPage($map = array(), $limit = 20, $field = '*', $order = 'uid DESC') {
		$data = $this->where ( $map )->limit ( $limit )->field ( $field )->order ( $order )->findPage ();
		return $data;
	}
	
	/**
	 * 获取指定用户的相关信息
	 * 
	 * @param integer $uid
	 *        	用户UID
	 * @return array 指定用户的相关信息
	 */
	public function getUserInfo($uid) {
		$uid = intval ( $uid );
		if ($uid <= 0) {
			$this->error = L ( 'PUBLIC_UID_INDEX_ILLEAGAL' ); // UID参数值不合法
			return false;
		}
// 		if ($user = static_cache ( 'user_info_' . $uid )) {
// 			return $user;
// 		}
		// 查询缓存数据
		$user = model ( 'Cache' )->get ( 'ui_' . $uid );
		
		if (! $user) {
			$this->error = L ( 'PUBLIC_GET_USERINFO_FAIL' ); // 获取用户信息缓存失败
			$map ['uid'] = $uid;
			$user = $this->_getUserInfo ( $map );
		}
// 		static_cache ( 'user_info_' . $uid, $user );
		
		return $user;
	}
	
	/**
	 * 为@搜索提供用户信息
	 * 
	 * @param integer $uid
	 *        	用户UID
	 * @return array 指定用户的相关信息
	 */
	public function getUserInfoForSearch($uid, $field) {
		$uid = intval ( $uid );
		if ($uid <= 0) {
			$this->error = L ( 'PUBLIC_UID_INDEX_ILLEAGAL' ); // UID参数值不合法
			return false;
		}
		if ($user = static_cache ( 'user_info_search' . $uid )) {
			return $user;
		}
		// 查询缓存数据
		$user = model ( 'Cache' )->get ( 'ui_' . $uid );
		if (! $user) {
			$this->error = L ( 'PUBLIC_GET_USERINFO_FAIL' ); // 获取用户信息缓存失败
			$map ['uid'] = $uid;
			$user = $this->_getUserInfo ( $map, $field );
		}
		static_cache ( 'user_info_search' . $uid, $user );
		
		return $user;
	}
	
	/**
	 * 通过用户昵称查询用户相关信息
	 * 
	 * @param string $uname
	 *        	昵称信息
	 * @return array 指定昵称用户的相关信息
	 */
	public function getUserInfoByName($uname, $map) {
		if (empty ( $uname )) {
			$this->error = L ( 'PUBLIC_USER_EMPTY' ); // 用户名不能为空
			return false;
		}
		$map ['uname'] = t ( $uname );
		$data = $this->_getUserInfo ( $map );
		return $data;
	}
	
	/**
	 * 通过邮箱查询用户相关信息
	 * 
	 * @param string $email
	 *        	用户邮箱
	 * @return array 指定昵称用户的相关信息
	 */
	public function getUserInfoByEmail($email, $map) {
		if (empty ( $email )) {
			$this->error = L ( 'PUBLIC_USER_EMPTY' ); // 用户名不能为空
			return false;
		}
		$map ['email'] = t ( $email );
		$data = $this->_getUserInfo ( $map );
		return $data;
	}
	
	/**
	 * 通过邮箱查询用户相关信息
	 * 
	 * @param string $email
	 *        	用户邮箱
	 * @return array 指定昵称用户的相关信息
	 */
	public function getUserInfoByDomain($domain, $map) {
		if (empty ( $domain )) {
			$this->error = L ( 'PUBLIC_USER_EMPTY' ); // 用户名不能为空
			return false;
		}
		$map ['domain'] = t ( $domain );
		$data = $this->_getUserInfo ( $map );
		return $data;
	}
	
	/**
	 * 根据UID批量获取多个用户的相关信息
	 * 
	 * @param array $uids
	 *        	用户UID数组
	 * @return array 指定用户的相关信息
	 */
	public function getUserInfoByUids($uids) {
		! is_array ( $uids ) && $uids = explode ( ',', $uids );
		
		$cacheList = model ( 'Cache' )->getList ( 'ui_', $uids );
		foreach ( $uids as $v ) {
			! $cacheList [$v] && $cacheList [$v] = $this->getUserInfo ( $v );
		}
		
		return $cacheList;
	}
	
	/**
	 * 获取指定用户的档案信息
	 * 
	 * @param integer $uid
	 *        	用户UID
	 * @param string $category
	 *        	档案分类
	 * @return array 指定用户的档案信息
	 */
	public function getUserProfile($uid, $category = '*') {
		$uid = intval ( $uid );
		if ($uid <= 0) {
			$this->error = L ( 'PUBLIC_UID_INDEX_ILLEAGAL' ); // UID参数值不合法
			return false;
		}
		// 设置档案分类过滤
		$category != '*' && $map ['category'] = $category;
		// 查询数据
		$profile = D ( 'UserProfile' )->where ( $map )->findAll ( $uid );
		if (! $profile) {
			$this->error = L ( 'PUBLIC_GET_USERPROFILE_FAIL' ); // 获取用户档案失败
			return false;
		} else {
			return $profile;
		}
	}
	
	/**
	 * 获取用户档案配置信息
	 * 
	 * @return array 用户档案配置信息
	 */
	public function getUserProfileSetting() {
		$profileSetting = D ( 'UserProfileSetting' )->findAll ();
		if (! $profileSetting) {
			$this->error = L ( 'PUBLIC_GET_USERPROFILE_FAIL' ); // 获取用户档案失败
			return false;
		}
		
		return $profileSetting;
	}
	
	/**
	 * 添加用户
	 * 
	 * @param array|object $user
	 *        	新用户的相关信息|新用户对象
	 * @return boolean 是否添加成功
	 */
	public function addUser($user) {
		// 验证用户名称是否重复
		$map ['uname'] = t ( $user ['uname'] );
		$isExist = $this->where ( $map )->count ();
		if ($isExist > 0) {
			$this->error = '用户昵称已存在，请使用其他昵称';
			return false;
		}
		if (is_object ( $user )) {
			$salt = rand ( 11111, 99999 );
			$user->login_salt = $salt;
			$user->login = $user->email;
			$user->ctime = time ();
			$user->reg_ip = get_client_ip ();
			$user->password = $this->encryptPassword ( $user->password, $salt );
		} else if (is_array ( $user )) {
			$salt = rand ( 11111, 99999 );
			$user ['login_salt'] = $salt;
			$user ['login'] = $user ['email'];
			$user ['ctime'] = time ();
			$user ['reg_ip'] = get_client_ip ();
			$user ['password'] = $this->encryptPassword ( $user ['password'], $salt );
		}
		// 添加昵称拼音索引
		$user ['first_letter'] = getFirstLetter ( $user ['uname'] );
		// 如果包含中文将中文翻译成拼音
		if (preg_match ( '/[\x7f-\xff]+/', $user ['uname'] )) {
			// 昵称和呢称拼音保存到搜索字段
			$user ['search_key'] = $user ['uname'] . ' ' . model ( 'PinYin' )->Pinyin ( $user ['uname'] );
		} else {
			$user ['search_key'] = $user ['uname'];
		}
		// 添加用户操作
		$result = $this->add ( $user );
		if (! $result) {
			$this->error = L ( 'PUBLIC_ADD_USER_FAIL' ); // 添加用户失败
			return false;
		} else {
			// 添加部门关联信息
			model ( 'Department' )->updateUserDepartById ( $result, intval ( $_POST ['department_id'] ) );
			// 添加用户组关联信息
			if (! empty ( $_POST ['user_group'] )) {
				model ( 'UserGroupLink' )->domoveUsergroup ( $result, implode ( ',', $_POST ['user_group'] ) );
			}
			// 添加用户职业关联信息
			if (! empty ( $_POST ['user_category'] )) {
				model ( 'UserCategory' )->updateRelateUser ( $result, $_POST ['user_category'] );
			}
			return true;
		}
	}
	
	/**
	 * 密码加密处理
	 * 
	 * @param string $password
	 *        	密码
	 * @param string $salt
	 *        	密码附加参数，默认为11111
	 * @return string 加密后的密码
	 */
	public function encryptPassword($password, $salt = '11111') {
		return md5 ( md5 ( $password ) . $salt );
	}
	
	/**
	 * 禁用指定用户账号操作
	 * 
	 * @param array $ids
	 *        	禁用的用户ID数组
	 * @return boolean 是否禁用成功
	 */
	public function deleteUsers($ids) {
		// 处理数据
		$uid_array = $this->_parseIds ( $ids );
		// 进行用户假删除
		$map ['uid'] = array (
				'IN',
				$uid_array 
		);
		$save ['is_del'] = 1;
		$result = $this->where ( $map )->save ( $save );
		$this->cleanCache ( $uid_array );
		if (! $result) {
			$this->error = L ( 'PUBLIC_DISABLE_ACCOUNT_FAIL' ); // 禁用帐号失败
			return false;
		} else {
			$this->deleteUserWeiBoData ( $uid_array );
			$this->dealUserAppData ( $uid_array );
			return true;
		}
	}
	/**
	 * 彻底删除指定用户账号操作
	 *
	 * @param array $ids
	 *        	彻底删除的用户ID数组
	 * @return boolean 是否彻底删除成功
	 */
	public function trueDeleteUsers($ids) {
		// 处理数据
		$uid_array = $this->_parseIds ( $ids );
		// 进行用户假删除
		$map ['uid'] = array (
				'IN',
				$uid_array
		);
		$result = $this->where ( $map )->delete ();
		$this->cleanCache ( $uid_array );
		if (! $result) {
			$this->error = L ( 'PUBLIC_REMOVE_COMPLETELY_FAIL' ); // 彻底删除帐号失败
			return false;
		} else {
			$this->trueDeleteUserCoreData ( $uid_array );
			// 更新用户统计数目
			model('UserData')->updateUserDataByuid($uid_array);
			return true;
		}
	}	
	
	/**
	 * 恢复指定用户账号操作
	 * 
	 * @param array $ids
	 *        	恢复的用户UID数组
	 * @return boolean 是否恢复成功
	 */
	public function rebackUsers($ids) {
		// 处理数据
		$uid_array = $this->_parseIds ( $ids );
		// 恢复用户假删除
		$map ['uid'] = array (
				'IN',
				$uid_array 
		);
		$save ['is_del'] = 0;
		$result = $this->where ( $map )->save ( $save );
		$this->cleanCache ( $uid_array );
		if (! $result) {
			$this->error = L ( 'PUBLIC_RECOVER_ACCOUNT_FAIL' ); // 恢复帐号失败
			return false;
		} else {
			$this->rebackUserWeiBoData ( $uid_array );
			$this->dealUserAppData ( $uid_array, 'rebackUserAppData' );
			return true;
		}
	}
	
	/**
	 * 改变用户的激活状态
	 * 
	 * @param array $ids
	 *        	用户UID数组
	 * @param integer $type
	 *        	用户的激活状态，0表示未激活；1表示激活
	 * @return boolean 是否操作成功
	 */
	public function activeUsers($ids, $type = 1) {
		// 类型参数仅仅只能为0或1
		if ($type != 1 && $type != 0) {
			$this->error = L ( 'PUBLIC_ILLEGAL_TYPE_INDEX' ); // 非法的type参数
			return false;
		}
		// 处理数据
		$uid_array = $this->_parseIds ( $ids );
		// 改变指定用户的激活状态
		$map ['uid'] = array (
				'IN',
				$uid_array 
		);
		$result = $this->where ( $map )->setField ( 'is_active', $type );
// 		$this->cleanCache ( $uid_array );
		$this->updateCache($ids,array('is_active'=>'1'));
		
		if (! $result) {
			$this->error = L ( 'PUBLIC_ACTIVATE_USER_FAIL' ); // 激活用户失败
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * 删除指定用户的档案信息
	 * 
	 * @param array $ids
	 *        	用户UID数组
	 * @return boolean 是否删除用户档案成功
	 */
	public function deleteUserProfile($ids) {
		// 处理数据
		$uid_array = $this->_parseIds ( $ids );
		// 删除指定用户的档案信息
		$map ['uid'] = array (
				'IN',
				$uid_array 
		);
		$result = D ( 'UserProfileSetting' )->where ( $map )->delete ();
		if (! $result) {
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * 转移指定用户到指定部门
	 * 
	 * @param array $uids
	 *        	用户UID数组
	 * @param integer $user_department
	 *        	部门ID
	 * @return boolean 是否转移成功
	 */
	public function domoveDepart($uids, $user_department) {
		// TODO:后期需要加入清理缓存操作
		$uids = explode ( ',', $uids );
		foreach ( $uids as $uid ) {
			model ( 'Department' )->updateUserDepartById ( $uid, $user_department );
		}
		
		return true;
	}
	
	/**
	 * 清除指定用户UID的缓存
	 * 
	 * @param array $uids
	 *        	用户UID数组
	 * @return boolean 是否清除缓存成功
	 */
	public function cleanCache($uids) {
		if (empty ( $uids )) {
			return false;
		}
		! is_array ( $uids ) && $uids = explode ( ',', $uids );
		foreach ( $uids as $uid ) {
			model ( 'Cache' )->rm ( 'ui_' . $uid );
			static_cache ( 'user_info_' . $uid, false );
			
			$keys = model('Cache')->get('getUserDataByCache_keys_'.$uid);
			foreach ($keys as $k){
				model ( 'Cache' )->rm ( $k );
			}
			model('Cache')->rm('getUserDataByCache_keys_'.$uid);
		}
		
		return true;
	}
	
	/**
	 * 获取指定用户所感兴趣人的UID数组
	 * 
	 * @param integer $uid
	 *        	指定用户UID
	 * @param integer $num
	 *        	感兴趣人的个数
	 * @return array 感兴趣人的UID数组
	 */
	public function relatedUser($uid, $num) {
		$user_info = $this->getUserInfo ( $uid );
		if (! $user_info || $num <= 0) {
			return false;
		}
		
		// $map['department_id'] = $user_info['department_id'];
		$map ['uid'] = array (
				'NEQ',
				$uid 
		);
		$map ['is_active'] = 1;
		
		$uids = $this->where ( $map )->limit ( $num )->getAsFieldArray ( 'uid' );
		
		if (! $uids || count ( $uids ) < $num) {
			$limit = count ( $uids ) + $num + 1;
			$sql = "SELECT `uid` FROM {$this->tablePrefix}user 
					WHERE uid >= ((SELECT MAX(uid) FROM {$this->tablePrefix}user) - (SELECT MIN(uid) FROM {$this->tablePrefix}user)) * RAND() + (SELECT MIN(uid) FROM {$this->tablePrefix}user) 
					AND is_active = 1 LIMIT {$limit}";
			$random_uids = $this->query ( $sql );
			$random_uids = getSubByKey ( $random_uids, 'uid' );
			$uids = is_array ( $uids ) ? array_merge ( $uids, $random_uids ) : $random_uids;
		}
		// 去除可能由随机产生的登录用户UID
		unset ( $uids [array_search ( $uid, $uids )] );
		// 截取感兴趣人的个数
		$uids = array_slice ( $uids, 0, $num );
		// 批量获取指定用户信息
		$related_users = $this->getUserInfoByUids ( $uids );
		
		return $related_users;
	}
	
	/**
	 * 处理用户UID数据为数组形式
	 * 
	 * @param mix $ids
	 *        	用户UID
	 * @return array 数组形式的用户UID
	 */
	private function _parseIds($ids) {
		// 转换数字ID和字符串形式ID串
		if (is_numeric ( $ids )) {
			$ids = array (
					$ids 
			);
		} else if (is_string ( $ids )) {
			$ids = explode ( ',', $ids );
		}
		// 过滤、去重、去空
		if (is_array ( $ids )) {
			foreach ( $ids as $id ) {
				$id_array [] = intval ( $id );
			}
		}
		$id_array = array_unique ( array_filter ( $id_array ) );
		
		if (count ( $id_array ) == 0) {
			$this->error = L ( 'PUBLIC_INSERT_INDEX_ILLEGAL' ); // 传入ID参数不合法
			return false;
		} else {
			return $id_array;
		}
	}
	/**
	 * 更新用户缓存信息
	 * <p>该方法只会修改缓存中用户的指定数据，并不会更新数据库。
	 * 该方法存在的意义在于直接刷新缓存，规避了主从数据库同步时间延迟导致的缓存与数据库不一致的问题。
	 * 对实时性要求不高的业务不推荐使用该方法，而使用 {@link cleanCache cleanCache} 
	 * @param unknown $uid 用户ID
	 * @param array $values 要更新的值,修改多维数组中的子数组以.隔开key，
	 * 例如：api_user_group.0.user_group_id=>3，实际更新为：$user['api_user_group'][0]['user_group_id']=3;
	 */
	public function updateCache($uids,array $values){
		if (empty ( $uids )||empty($values)) {
			return false;
		}
		! is_array ( $uids ) && $uids = explode ( ',', $uids );
		$cache=model ( 'Cache' );
		foreach ($values as $k=>$v){
			$tempk=explode('.', $k);
			$isDel=false;
			if(is_array($v)){
				if(count($v)>1){
					$isDel=$v[0]=='del';
					$v=$v[1];
				}else{
					$v=$v[0];
				}
			}
			$tempv=$v;
			$kv[]=array('key'=>$tempk,'value'=>$tempv,'isDel'=>$isDel);
		}
		foreach ( $uids as $uid ) {
			$user=$cache->get ( 'ui_' . $uid );	
			if($user){
				foreach ($kv as $v){
					$target='$user';
					foreach($v['key'] as $key){
						$target.='[\''.$key.'\']';		
					}
					if($v['isDel']){
						$target='unset('.$target.')';
					}else{
						$target.='=\''.$v['value'].'\';';
					}
					eval($target);
// 					echo $target;	
				}
				$cache->set('ui_'.$uid,$user,86400);
			}			
			$keys = $cache->get('getUserDataByCache_keys_'.$uid);
			foreach ($keys as $k){
				$user=$cache->get ( $k );
				if($user){
					foreach ($kv as $v){
						$target='$user';
						foreach($v['key'] as $key){
							$target.='[\''.$key.'\']';		
						}
						if($v['isDel']){
							$target='unset('.$target.')';
						}else{
							$target.='=\''.$v['value'].'\';';
						}
						eval($target);
// 						echo $target;
					}
					$cache->set($k,$user,86400);
				}
			}
		}
		
		return true;
		
	}
	/**
	 * 获取ts_user表的数据，带缓存功能
	 * 
	 * @param array $map
	 *        	查询条件
	 * @return array 指定用户的相关信息
	 */
	function getUserDataByCache($map, $field = "*"){
		$key = 'userData_';
		foreach ($map as $k=>$v){
			$key .= $k.$v;
		}
		if($field!='*'){
			$key .= '_'.str_replace(array("`",","," "), '', $field);
		}

		$user = model('Cache')->get($key);
		if($user==false){
			$user = $this->where ( $map )->field ( $field )->find ();
			model('Cache')->set($key, $user,86400);  //缓存24小时
			//保存key和uid的关系，以方便后面用户资料变化时可以删除这些缓存
			if(isset($user['uid'])){  
			     $keys = model('Cache')->get('getUserDataByCache_keys_'.$user['uid']);
			     $keys[$key] = $key;
			     model('Cache')->set('getUserDataByCache_keys_'.$user['uid'], $keys,86500);
			}
		}

		return $user;
	}
	/**
	 * 获取指定用户的相关信息
	 *
	 * @param array $map
	 *        	查询条件
	 * @return array 指定用户的相关信息
	 */	
	private function _getUserInfo($map, $field = "*") {
		$user = $this->getUserDataByCache($map, $field);
		unset ( $user ['password'] );
		if (! $user) {
			$this->error = L ( 'PUBLIC_GET_INFORMATION_FAIL' ); // 获取用户信息失败
			return false;
		} else {
			$uid = $user ['uid'];
			$user = array_merge ( $user, model ( 'Avatar' )->init ( $user ['uid'] )->getUserAvatar () );
			$user ['avatar_url'] = U ( 'public/Attach/avatar', array (
					'uid' => $user ["uid"] 
			) );
			$user ['space_url'] = ! empty ( $user ['domain'] ) ? U ( 'public/Profile/index', array (
					'uid' => $user ["domain"] 
			) ) : U ( 'public/Profile/index', array (
					'uid' => $user ["uid"] 
			) );
			$user ['space_link'] = "<a href='" . $user ['space_url'] . "' target='_blank' uid='{$user['uid']}' event-node='face_card'>" . $user ['uname'] . "</a>";
			$user ['space_link_no'] = "<a href='" . $user ['space_url'] . "' title='" . $user ['uname'] . "' target='_blank'>" . $user ['uname'] . "</a>";
			// 用户勋章
			$user ['medals'] = model ( 'Medal' )->getMedalByUid ( $user ['uid'] );
			// 用户认证图标
			$groupIcon = array ();
			$userGroup = model ( 'UserGroupLink' )->getUserGroupData ( $uid );
			$user ['api_user_group'] = $userGroup [$uid];
			$user ['user_group'] = $userGroup [$uid];
			foreach ( $userGroup [$uid] as $value ) {
				$groupIcon [] = '<img title="' . $value ['user_group_name'] . '" src="' . $value ['user_group_icon_url'] . '" style="width:auto;height:auto;display:inline;cursor:pointer;" />';
			}
			$user ['group_icon'] = implode ( '&nbsp;', $groupIcon );
			
			model ( 'Cache' )->set ( 'ui_' . $uid, $user, 600 );
			static_cache ( 'user_info_' . $uid, $user );
			return $user;
		}
	}
	
	/**
	 * * API使用 **
	 */
	/**
	 * 格式化API数据
	 * 
	 * @param array $data
	 *        	API数据
	 * @param integer $uid
	 *        	粉丝用户UID
	 * @param integer $mid
	 *        	登录用户UID
	 * @return array API输出数据
	 */
	public function formatForApi($data, $uid, $mid = '') {
		empty ( $mid ) && $mid = $GLOBALS ['ts'] ['mid'];
		$userInfo = $this->getUserInfo ( $uid );
		$data ['uname'] = $userInfo ['uname'];
		$data ['space_url'] = $userInfo ['space_url'];
		$data ['follow_state'] = model ( 'Follow' )->getFollowState ( $mid, $uid ); // 登录用户与其粉丝之间的关注状态
		$data ['profile'] = model ( 'UserProfile' )->getUserProfileForApi ( $uid );
		$data ['avatar_big'] = $userInfo ['avatar_big'];
		$data ['avatar_middle'] = $userInfo ['avatar_middle'];
		$data ['avatar_small'] = $userInfo ['avatar_small'];
		$data ['sex'] = $userInfo['sex'];
		$data ['intro'] = $userInfo['intro'];
		$count = model ( 'UserData' )->getUserData ( $uid );
		empty ( $count ['following_count'] ) && $count ['following_count'] = 0;
		empty ( $count ['follower_count'] ) && $count ['follower_count'] = 0;
		empty ( $count ['feed_count'] ) && $count ['feed_count'] = 0;
		empty ( $count ['favorite_count'] ) && $count ['favorite_count'] = 0;
		empty ( $count ['unread_atme'] ) && $count ['weibo_count'] = 0;
		$data ['count_info'] = $count;
		
		return $data;
	}
	
	/**
	 * * 用于搜索引擎 **
	 */
	/**
	 * 搜索用户
	 * 
	 * @param string $key
	 *        	关键字
	 * @param integer $follow
	 *        	关注状态值
	 * @param integer $limit
	 *        	结果集数目，默认为100
	 * @param integer $max_id
	 *        	主键最大值
	 * @param string $type
	 *        	类型
	 * @param integer $noself
	 *        	搜索结果是否包含登录用户，默认为0
	 * @return array 用户列表数据
	 */
	public function searchUser($key = '', $follow = 0, $limit = 100, $max_id = '', $type = '', $noself = '0', $page, $atme) {
		// 判断类型？
		switch ($type) {
			case '' :
				$where = " (search_key LIKE '%{$key}%')";
				// 过滤未激活和未审核的用户
				// if($atme == 'at') {
				$where .= " AND is_active=1 AND is_audit=1 AND is_init=1";
				// }
				if (! empty ( $max_id )) {
					$where .= " AND uid < " . intval ( $max_id );
				}
				if (! empty ( $noself )) {
					$where .= " AND uid !=" . intval ( $GLOBALS ['ts'] ['mid'] );
				}
				if ($follow == 1) {
					// 只选择我关注的人
					$where .= " AND uid IN (SELECT fid FROM " . $this->tablePrefix . "user_follow WHERE uid = '{$GLOBALS['ts']['mid']}')";
				}
				if ($page) {
					// 分页形式
					$nameUserlist = $this->where ( $where )->field ( 'uid' )->limit ( $limit )->order ( 'uid desc' )->findAll (); // 按用户名搜
					if ($nameUserlist) {
						$nameUserIdList = getSubByKey ( $nameUserlist, 'uid' );
					} else {
						$nameUserIdList = array ();
					}
					
					$datas ['name'] = $key;
					$tagid = D ( 'tag' )->where ( $datas )->getField ( 'tag_id' );
					$maps ['app'] = 'public';
					$maps ['table'] = 'user';
					$maps ['tag_id'] = $tagid;
					$tagUserlist = D ( 'app_tag' )->where ( $maps )->field ( 'row_id as uid' )->order ( 'row_id desc' )->findAll (); // 按标签搜
					if ($tagUserlist) {
						$tagUserIdList = getSubByKey ( $tagUserlist, 'uid' );
					} else {
						$tagUserIdList = array ();
					}
					
					$uidList = array_unique ( array_merge ( $tagUserIdList, $nameUserIdList ) );
					$data ['uid'] = array (
							'in',
							$uidList 
					);
					$list = $this->where ( $data )->field ( 'uid' )->limit ( $limit )->order ( 'uname ASC' )->findpage ( $page );
				} else {
					// 未分页形式
					$list ['data'] = $this->where ( $where )->field ( 'uid' )->limit ( $limit )->order ( 'uname ASC' )->findAll ();
				}
				break;
		}
		// 添加用户信息
		foreach ( $list ['data'] as &$v ) {
			$v = $this->getUserInfoForSearch ( $v ['uid'], 'uid,uname,sex,location,domain,search_key' );
		}
		
		return $list;
	}
	
	/**
	 * 根据标示符(uid或uname或email或domain)获取用户信息
	 *
	 * 首先检查缓存(缓存ID: user_用户uid / user_用户uname), 然后查询数据库(并设置缓存).
	 *
	 * @param string|int $identifier
	 *        	标示符内容
	 * @param string $identifier_type
	 *        	标示符类型. (uid, uname, email, domain之一)
	 */
	public function getUserByIdentifier($identifier, $identifier_type = 'uid') {
		if ($identifier_type == 'uid') {
			return $this->getUserInfo ( $identifier );
		} elseif ($identifier_type == 'uname') {
			return $this->getUserInfoByName ( $identifier );
		} elseif ($identifier_type == 'email') {
			return $this->getUserInfoByEmail ( $identifier );
		} elseif ($identifier_type == 'domain') {
			return $this->getUserInfoByDomain ( $identifier );
		}
	}
	
	/**
	 * 获取最后错误信息
	 * 
	 * @return string 最后错误信息
	 */
	public function getLastError() {
		return $this->error;
	}
	/**
	 * 假删除用户微博数据
	 *
	 * @param int $uid
	 *        	用户UID
	 * @return BOOL
	 */
	public function deleteUserWeiBoData($uid_array) {
		$map ['uid'] = array (
				'in',
				$uid_array 
		);
		$map ['is_del'] = 0;
		$feed_id_list = model ( 'Feed' )->where ( $map )->field ( 'feed_id' )->findAll ();
		if (empty ( $feed_id_list ))
			return true; // 如果没有可删除的微博，直接返回
		
		$idArr = getSubByKey ( $feed_id_list, 'feed_id' );
		$return = model ( 'Feed' )->doEditFeed ( $idArr, 'delFeed', L ( 'PUBLIC_STREAM_DELETE' ) );
		return $return;
	}
	
	/**
	 * 恢复用户的微博数据
	 *
	 * @param int $uid
	 *        	用户UID
	 * @return BOOL
	 */
	public function rebackUserWeiBoData($uid_array) {
		$map ['uid'] = array (
				'in',
				$uid_array 
		);
		$map ['is_del'] = 1;
		$feed_id_list = model ( 'Feed' )->where ( $map )->field ( 'feed_id' )->findAll ();
		if (empty ( $feed_id_list ))
			return true; // 如果没有可恢复的微博，直接返回
		
		$idArr = getSubByKey ( $feed_id_list, 'feed_id' );
		$return = model ( 'Feed' )->doEditFeed ( $idArr, 'feedRecover', L ( 'PUBLIC_RECOVER' ) );
		return $return;
	}
	
	/**
	 * 彻底删除用户的微博数据
	 *
	 * @param int $uid
	 *        	用户UID
	 * @return BOOL
	 */
	public function trueDeleteUserCoreData($uid_array) {
		$map ['uid'] = array (
				'in',
				$uid_array 
		);
		
		//删除微博
		$feed_id_list = model ( 'Feed' )->where ( $map )->field ( 'feed_id' )->findAll ();
		if (!empty ( $feed_id_list )){
			$idArr = getSubByKey ( $feed_id_list, 'feed_id' );
			$return = model ( 'Feed' )->doEditFeed ( $idArr, 'deleteFeed', L ( 'PUBLIC_STREAM_DELETE' ) );
			// 删除收藏
			$cmap['source_id'] = array('IN', $idArr);
			$cmap['source_table_name'] = 'feed';
			model('Collection')->where($cmap)->delete();
			// 删除@信息
			$amap['row_id'] = array('IN', $idArr);
			$amap['table'] = 'feed';
			model('Atme')->where($amap)->delete();
		}
		unset($map);
		
		$tableStr = $this->_getUserField();
		$tableArr = explode('|', $tableStr);
		$uidStr = implode(',', $uid_array);
		$prefix = C('DB_PREFIX');
		foreach ($tableArr as $table){
			$vo = explode(':', $table);
			
			$sql = 'DELETE FROM '.$prefix.$vo[0].' WHERE '.$vo[1].' IN ('.$uidStr.')';
			$this->execute($sql);
		}

		return $return;
	}
	
	/**
	 * 删除或者恢复用户应用数据
	 *
	 * @param array $uid_array
	 *        	用户UID
	 * @param string $type
	 *        	操作类型：deleteUserAppData 删除数据
	 *        	rebackUserAppData 恢复数据
	 *        	trueDeleteUserAppData 彻底删除数据
	 * @return void
	 */
	public function dealUserAppData($uid_array, $type = 'deleteUserAppData') {
		// 取全部APP信息
		$appList = model ( 'App' )->where('status=1')->field ( 'app_name' )->findAll ();
		
		foreach ( $appList as $app ) {
			$appName = strtolower ( $app ['app_name'] );
			$className = ucfirst ( $appName );
			
			$dao = D ( $className . 'Protocol', $className, false );
			if (method_exists ( $dao, $type )) {
				$dao->$type ( $uid_array );
			}
			unset ( $dao );
		}
	}
	private function _getUserField() {
		$str = 'user_follow:fid';  //特殊情况下的配置
		
		$dbName = C ( 'DB_NAME' );
		$sql = "SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.`COLUMNS` WHERE TABLE_SCHEMA='$dbName' AND COLUMN_NAME LIKE '%uid%'";
		$list = M ()->query ( $sql );
		if (empty ( $list )) {
			$str .= '|atme:uid|attach:uid|blog:uid|blog_category:uid|channel:uid|channel_follow:uid|check_info:uid|collection:uid|comment:app_uid|comment:uid|comment:to_uid|credit_user:uid|denounce:uid|denounce:fuid|develop:uid|diy_page:uid|diy_widget:uid|event:uid|event_photo:uid|event_user:uid|feed:uid|feedback:uid|find_password:uid|invite_code:inviter_uid|invite_code:receiver_uid|login:uid|login:type_uid|login_logs:uid|login_record:uid|medal_user:uid|message_content:from_uid|message_list:from_uid|message_member:member_uid|notify_email:uid|notify_message:uid|online:uid|online_logs:uid|online_logs_bak:uid|poster:uid|sitelist_site:uid|survey_answer:uid|task_receive:uid|task_user:uid|template_record:uid|tipoff:uid|tipoff:bonus_uid|tipoff_log:uid|tips:uid|user:uid|user_app:uid|user_blacklist:uid|user_category_link:uid|user_change_style:uid|user_credit_history:uid|user_data:uid|user_department:uid|user_follow:uid|user_follow_group:uid|user_follow_group_link:uid|user_group_link:uid|user_official:uid|user_online:uid|user_privacy:uid|user_profile:uid|user_verified:uid|vote:uid|vote_user:uid|vtask:uid|vtask:bonus_uid|vtask_log:uid|weiba:uid|weiba:admin_uid|weiba_apply:follower_uid|weiba_apply:manager_uid|weiba_favorite:uid|weiba_favorite:post_uid|weiba_follow:follower_uid|weiba_log:uid|weiba_post:post_uid|weiba_post:last_reply_uid|weiba_reply:post_uid|weiba_reply:uid|weiba_reply:to_uid|x_article:uid|x_logs:uid|user_bind:uid|user_verified_info:uid';
		} else {
			$prefix = C('DB_PREFIX');
			foreach ( $list as $vo ) {
				$vo['TABLE_NAME'] = str_replace($prefix,'', $vo['TABLE_NAME']);
				$str .= '|' . $vo ['TABLE_NAME'] . ':' . $vo ['COLUMN_NAME'];
			}
		}
		
		return $str;
	}
	/**
	 * 创建学校账号
	 * @param unknown $sid
	 * @param unknown $name
	 * @param unknown $email
	 * @param unknown $password
	 * @param unknown $option
	 */
	public function createSchoolAccount($sid,$name,$email,$password,$option){
		//创建账号
		$register_model=model('Register');
		if(M('UserVerified')->where('sid='.$sid.' and type=1')->getField('uid')){
			$this->error='该学校已有账号';
			return false;
		}
		$oldUser=$this->where('email=\''.$email.'\'')->field('email')->find();
		if($oldUser){
			$this->error='该邮箱账号已存在';
			return false;
		}
		if(!$register_model->isValidEmail($email,$oldUser?$oldUser['email']:null)) {
			$this->error=$register_model->getLastError();	
			return false;			
		}
		if(!$register_model->isValidPassword($password, $password)){
			$this->error=$register_model->getLastError();
			return false;
		}
		if(!$name){
			$this->error='学校名字不能为空';
			return false;
		}
		$map['uname'] = $name;
		$oldUser=$this->where('uname=\''.$map['uname'].'\'')->field('uname')->find();
		if($oldUser){
				$this->error='该昵称账号已经存在';
				return false;
		}
		$login_salt = rand(11111, 99999);
		$map['login_salt'] = $login_salt;
		$map['password'] = md5(md5($password).$login_salt);
		$map['login'] = $map['email'] = $email;
		$map['utype'] =1;
		$map['reg_ip'] = get_client_ip();
		$map['ctime'] = time();
		
		//通过审核
		$map['is_audit'] =1;
		$map['is_active'] = 1;
		$map['first_letter'] = getFirstLetter($map['uname']);
		//如果包含中文将中文翻译成拼音
		if ( preg_match('/[\x7f-\xff]+/', $map['uname'] ) ){
			//昵称和呢称拼音保存到搜索字段
			$map['search_key'] = $map['uname'].' '.model('PinYin')->Pinyin( $map['uname'] );
		} else {
			$map['search_key'] = $map['uname'];
		}
		$uid = $this->add($map);
		if($uid){
			//移动到梦想中心用户组
			model('UserGroupLink')->moveGroup($uid, C('USER_GROUP_DREAM_CENTER'));
			
			//写入实名认证表
			$verified['uid']=$uid;
			$verified['realname']=$name;
			$verified['sid']=$sid;
			$verified['verified']='1';
			$verified['vtime']=time();		
			$verified['type']='1';
			$verified['first_login']='1';
			//实名扩展数据
			$option['phone']&&$verified['phone']=$option['phone'];
			$option['bankname']&&$verified['bankname']=$option['bankname'];
			$option['accountname']&&$verified['accountname']=$option['accountname'];
			$option['branchbankname']&&$verified['branchbankname']=$option['branchbankname'];
			$option['zhifubao']&&$verified['zhifubao']=$option['zhifubao'];
			M('UserVerified')->add($verified);
			
			//写入积分数据392
			$integral['ref_id']=$uid;
			M('Integral')->add($integral);
			return true;
		}
		$this->error='账号创建失败';
		return false;
		
	}
	public function deleteSchoolAccount($sids){
		!is_array($sids)&&$sids=explode(',', $sids);
		$map['sid']=array('IN',$sids);
		$map['type']='1';
		$UVModel=M('UserVerified');
		$sids&&$uids=$UVModel->where($map)->field('uid')->select();
		$uids&&$uids=getSubByKey($uids,'uid');
		$uids&&$this->trueDeleteUsers($uids);
	}
	/**
	 * 取得用户主要信息
	 * @param unknown $uid
	 * @return unknown
	 */
	public function getUserShortInfo($uid){
		$cache=model('Cache');
		$key='ushort_'.$uid;
		$user=$cache->get($key);
		if(!$user){
			$user=$this->field('uid,uname,email,sex,is_active')->where('uid='.$uid)->find();
			$user = array_merge ( $user, model ( 'Avatar' )->init ( $user ['uid'] )->getUserAvatar () );
			if($user){
				$cache->set($key,$user,86400);
				$keys = $cache->get('getUserDataByCache_keys_'.$user['uid']);
				$keys[$key] = $key;
				$cache->set('getUserDataByCache_keys_'.$user['uid'], $keys,86500);
			}
		}
		return $user;
	}
}