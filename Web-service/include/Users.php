<?php

	class Users
	{
		private $__GB;
		function __construct($__GB)
		{
			$this->__GB = $__GB;
		}
		public function searchFriend($name,$userID)
		{
			$name = $this->__GB->__DB->escape_string(trim($name));
			$query  = $this->__GB->__DB->select('users','*',"`username` LIKE '%".$name."%' OR `email` LIKE '%".$name."%'");
			if($this->__GB->__DB->num_rows($query) != 0){
				$result['result'] = array();
				while ($fetch = $this->__GB->__DB->fetch_assoc($query)) {
					if($fetch['id'] == $userID){
						continue;
					}
					unset($fetch['password'],$fetch['email'],$fetch['cover'],$fetch['last_seen'],$fetch['date']);
					$result['result'][] = $fetch;
				}
				$this->__GB->prepareMessage($result);
			}else{
				$this->__GB->prepareMessage(array('result'=>null));
			}
		}
		public function addMessage($userID,$array)
		{
			foreach ($array as $key => $value) {
				$array[$key] = $this->__GB->__DB->escape_string(trim($value));
			}
			if($array['conversationID'] == 0){
				$sql = "`from` = ".$array['to']." AND `to` = {$userID} OR `to` = ".$array['to']." AND `from` = {$userID}";
				$query  = $this->__GB->__DB->select('conversations','*',$sql);
				if($this->__GB->__DB->num_rows($query) != 0){
					$fetch = $this->__GB->__DB->fetch_assoc($query);
					$array['conversationID'] = $fetch['id'];
				}else{
					$data = array('from'=>$userID,'to'=>$array['to'],'date'=>time());
					$insert = $this->__GB->__DB->insert('conversations',$data);
					if($insert){
						$array['conversationID'] = $this->__GB->__DB->lastID();
					}
				}
			}
			$array['from'] = $userID;
			$array['date'] = time();
			$insert = $this->__GB->__DB->insert('messages',$array);
			if($insert){
				$this->__GB->prepareMessage('done');
			}else{
				$this->__GB->prepareMessage('error');
			}
		}
		public function getConversationMessages($conversationID)
		{
			$conversationID = $this->__GB->__DB->escape_string($conversationID);
			$query  = $this->__GB->__DB->select('messages','*',"`conversationID` = {$conversationID}",'`date` ASC');
			if($this->__GB->__DB->num_rows($query) != 0){
				$messages['messages'] = array();
				while ($fetch = $this->__GB->__DB->fetch_assoc($query)) {
					$fetch['date'] = $this->__GB->TimeAgo($fetch['date']);
					$messages['messages'][] = $fetch;
				}
				return $messages;
			}else{
				return array('messages'=>null);
			}
		}
		public function getConversation($userID,$conversationID,$recpientID)
		{
			$conversationID = $this->__GB->__DB->escape_string($conversationID);
			if($conversationID != 0){
				$query  = $this->__GB->__DB->select('conversations','*',"`id` = {$conversationID}");
				if($this->__GB->__DB->num_rows($query) != 0){
					$fetch = $this->__GB->__DB->fetch_assoc($query);
					if($fetch['from'] == $userID || $fetch['to'] == $userID){
						$this->__GB->prepareMessage($this->getConversationMessages($fetch['id']));
					}else{
						$this->__GB->prepareMessage(array('messages'=>null));
					}
				}else{
					$this->__GB->prepareMessage(array('messages'=>null));
				}
			}else{
				$sql = "`from` = {$recpientID} AND `to` = {$userID} OR `to` = {$recpientID} AND `from` = {$userID}";
				$query  = $this->__GB->__DB->select('conversations','*',$sql);
				if($this->__GB->__DB->num_rows($query) != 0){
					$fetch = $this->__GB->__DB->fetch_assoc($query);
					if($fetch['from'] == $userID || $fetch['to'] == $userID){
						$this->__GB->prepareMessage($this->getConversationMessages($fetch['id']));
					}else{
						$this->__GB->prepareMessage(array('messages'=>null));
					}
				}else{
					$this->__GB->prepareMessage(array('messages'=>null));
				}
			}

		}
		public function getLastMessage($conversationID)
		{
			$query  = $this->__GB->__DB->select('messages','`message`',"`conversationID` = {$conversationID}",'`date` DESC','1');
			$fetch =  $this->__GB->__DB->fetch_assoc($query);
			return $fetch['message'];
		}
		public function getConversations($userID)
		{
			$query  = $this->__GB->__DB->select('conversations','*',"`from` = {$userID} OR `to` = {$userID}");
			if($this->__GB->__DB->num_rows($query) != 0){
				$conversations['conversations'] = array();
				while ($fetch = $this->__GB->__DB->fetch_assoc($query)) {
					if($userID != $fetch['from']){
						$fetch['userID'] = $fetch['from'];
					}else{
						$fetch['userID'] = $fetch['to'];
					}
					$queryUser = $this->__GB->__DB->select('users','`id`,`username`,`picture`','`id` = '.$fetch['userID']);
					$fetchUser = $this->__GB->__DB->fetch_assoc($queryUser);
					$fetch['last_message'] = $this->getLastMessage($fetch['id']);
					$fetch['date'] = $this->__GB->TimeAgo($fetch['date']);
					unset($fetch['from'],$fetch['to'],$fetchUser['id']);
					$conversation = array_merge($fetch,$fetchUser);
					$conversations['conversations'][] = $conversation;
				}
				$this->__GB->prepareMessage($conversations);
			}else{
				$this->__GB->prepareMessage(array('conversations'=>null));
			}
		}
		public function updateProfile($array,$imageID,$userID)
		{
			foreach ($array as $key => $value) {
				$array[$key] = $this->__GB->__DB->escape_string(trim($value));
			}
			$query = $this->__GB->__DB->select('users','`username`',"`id` = {$userID}");
			$fetch = $this->__GB->__DB->fetch_assoc($query);
			if($fetch['username'] != $array['username'] && $this->UserExist($array['username'])){
				$this->__GB->prepareMessage('Username already exists');
			}else if(filter_var($array['email'],FILTER_VALIDATE_EMAIL) === false){
				$this->__GB->prepareMessage('Invalid E-mail');
			}else{
				$fields = "`username` = '".$array['username']."'";
				$fields .= ",`name` = '".$array['name']."'";
				$fields .= ",`job` = '".$array['job']."'";
				$fields .= ",`address` = '".$array['address']."'";
				$fields .= ",`email` = '".$array['email']."'";
				if(!empty($array['password'])){
					$fields .= ",`password` = '".md5($array['password'])."'";
				}
				if($imageID != 0){
					$fields .= ",`picture` = '{$imageID}'";
				}
				$update = $this->__GB->__DB->update('users',$fields,"`id` = {$userID}");
				if($update){
					$this->__GB->prepareMessage('Profile updated successfully');
				}else{
					$this->__GB->prepareMessage('Please try again update failed');
				}
			}

		}
		public function getProfile($id)
		{
			$query = $this->__GB->__DB->select('users','*',"`id` = {$id}");
			$fetch = $this->__GB->__DB->fetch_assoc($query);
			unset($fetch['password']);
			$this->__GB->prepareMessage(array('profile'=>$fetch));
		}
		public function userRegister($array)
		{
				$username = $this->__GB->__DB->escape_string($array['username']);
				$email = $this->__GB->__DB->escape_string($array['email']);
				$password = md5($array['password']);
				if(strlen(trim($username)) <= 4){
					$response = array(
							  'statusCode' => 0,
							  'statusMessage' => 'Username too short'
							  );
					$this->__GB->prepareMessage($response);
				}else if(filter_var($email,FILTER_VALIDATE_EMAIL) === false){
					$response = array(
							  'statusCode' => 0,
							  'statusMessage' => 'Invalid E-mail'
							  );
					$this->__GB->prepareMessage($response);
				}else if(strlen(trim($array['password'])) <= 5){
					$response = array(
							  'statusCode' => 0,
							  'statusMessage' => 'Password too short'
							  );
					$this->__GB->prepareMessage($response);
				}else if($this->UserExist($username)){
					$response = array(
							  'statusCode' => 0,
							  'statusMessage' => 'Username already exists'
							  );
					$this->__GB->prepareMessage($response);
				}else{
					if(isset($_FILES['image'])){
						$imageID = $this->__GB->uploadImage($_FILES['image']);
					}else{
						$imageID = 0;
					}
					$array = array(
								'name'=>$username,
							'username'=>$username,
							'password'=>$password,
							'job'=> 'Job: -',
							'email'=>$email,
							'date'=>time(),
							'address'=> 'Address: -',
							'picture'=>$imageID
						);
					$insert = $this->__GB->__DB->insert('users', $array);
					if($insert){
						$response = array(
								  'statusCode' => 1,
								  'statusMessage' => 'Your account has been created'
							  );
						$this->__GB->prepareMessage($response);
					}else{
						$response = array(
								  'statusCode' => 0,
								  'statusMessage' => 'Oops Something Went Wrong'
							  );
						$this->__GB->prepareMessage($response);
					}
				}
			
			
		}
		public function userLogin($username,$password)
		{
			$username = $this->__GB->__DB->escape_string($username);
			$password = md5($password);
			$userQuery = $this->__GB->__DB->select('users','`id`', "`username` = '{$username}' AND `password` = '{$password}'");
			if($this->__GB->__DB->num_rows($userQuery) != 0){
				$fetch = $this->__GB->__DB->fetch_assoc($userQuery);
				$token = md5(time().uniqid().$username);
				$array = array(
					'userID' => $fetch['id'],
					'token' => $token,
					'date' => time()
					);
				$check = $this->__GB->__DB->select('sessions','`id`','`userID` = '.$fetch['id']);
				if($this->__GB->__DB->num_rows($check) != 0){
					$this->__GB->__DB->delete('sessions','`userID` = '.$fetch['id']);
				}
				$insert = $this->__GB->__DB->insert('sessions',$array);
				if($insert){
					$array = array(
						'status' => true,
						'userID' => $fetch['id'],
						'token' => $token
						);
					$this->__GB->prepareMessage($array);
				}else{
					$array = array(
						'status' => false,
						'userID' => null,
						'token' => null
						);
					$this->__GB->prepareMessage($array);
				}
			}else{
				$array = array(
						'status' => false,
						'userID' => null,
						'token' => null
						);
					$this->__GB->prepareMessage($array);
			}
		}
		public function getFollowing($id)
		{
			$id = $this->__GB->__DB->escape_string($id);
			$query = $this->__GB->__DB->select('follows','`to`',"`from` = {$id}");
			if($this->__GB->__DB->num_rows($query) != 0){
				$following['following'] = array();
				while ($fetch = $this->__GB->__DB->fetch_assoc($query)) {
					$following['following'][] = $this->getUser($fetch['to']);
				}
				$this->__GB->prepareMessage($following);
			}else{

				$this->__GB->prepareMessage(array('following'=>null));
			}
		}
		public function getUser($id)
		{
			$id = $this->__GB->__DB->escape_string($id);
			$query = $this->__GB->__DB->select('users','`id`,`name`,`job`,`picture`',"`id` = {$id}");
			if($this->__GB->__DB->num_rows($query) != 0){
				$fetch = $this->__GB->__DB->fetch_assoc($query);
				return $fetch;
			}
		}
		public function unFollow($userID,$to)
		{
			$to = $this->__GB->__DB->escape_string($to);
			$delete = $this->__GB->__DB->delete('follows',"`from` = {$userID} AND `to` = {$to}");
			if($delete){
					$this->__GB->prepareMessage('done');
				}else{
					$this->__GB->prepareMessage('error');
				}
		}
		public function Follow($userID,$to)
		{
			$to = $this->__GB->__DB->escape_string($to);
			$query = $this->__GB->__DB->select('users','*',"`id` = {$to}");
			if($this->__GB->__DB->num_rows($query) != 0){
				$fetch = $this->__GB->__DB->fetch_assoc($query);
				if($fetch['id'] != $userID){
					if($this->__GB->isFollowing($userID,$fetch['id']) != true){
						$like = $this->__GB->__DB->insert('follows',array('from'=>$userID,'to'=>$fetch['id'],'date'=>time()));
						if($like){
							$this->__GB->prepareMessage('done');
						}else{
							$this->__GB->prepareMessage('error');
						}
					}else{
						$this->__GB->prepareMessage('already liked');
					}
				}
			}
		}
		public function getLikes($id)
		{
			$query = $this->__GB->__DB->select('likes','`to`',"`from`= {$id}");
			if($this->__GB->__DB->num_rows($query) != 0){
				$ids = array();
				while($fetch = $this->__GB->__DB->fetch_assoc($query)){
					$ids[] = $fetch['to'];
				}
				return $ids;
			}else{
				return 0;
			}
		}
		public function getFollows($id)
		{
			$query = $this->__GB->__DB->select('follows','`to`',"`from`= {$id}");
			if($this->__GB->__DB->num_rows($query) != 0){
				$ids = array();
				while($fetch = $this->__GB->__DB->fetch_assoc($query)){
					$ids[] = $fetch['to'];
				}
				return $ids;
			}else{
				return 0;
			}
		}
		public function getUserID($token)
		{
			$token = $this->__GB->__DB->escape_string($token);
			$query = $this->__GB->__DB->select('sessions','*',"`token`= '{$token}'");
			if($this->__GB->__DB->num_rows($query) != 0){
				$fetch = $this->__GB->__DB->fetch_assoc($query);
				return $fetch['userID'];
			}else{
				return 0;
			}
		}
		public function UsersTotalVisits($id)
		{
			$query = $this->__GB->__DB->select('links','sum(`visits`) as `totalVisits`','`user_id`='.$id);
			$fetch = $this->__GB->__DB->fetch_assoc($query);
			return $fetch['totalVisits'];
		}

		public function adminLogin($array)
		{
			foreach ($array as $key => $val) {
				$array[$key] = trim($this->__GB->__DB->escape_string($val));
			}
			$query = $this->__GB->__DB->select('admins', '*', "`username` = '".$array['username']."' AND `password` = '".md5($array['password'])."'");
			if(empty($array['username']) || empty($array['password'])){
				echo $this->__GB->DisplayError('All fields required');
			}else if($this->__GB->__DB->num_rows($query) <= 0){
				echo $this->__GB->DisplayError('Login failed please try again');
			}else{
				$fetch = $this->__GB->__DB->fetch_assoc($query);
				$this->__GB->SetSession('admin', $fetch['id']);
				$this->__GB->SetSession('adminUsername', $fetch['username']);
				header('Location: index.php');
			}
			$this->__GB->__DB->free_result($query);
		}

		public function AdminExists($username)
		{
			$query = $this->__GB->__DB->select('admins', '`id`', "`username` = '".$username."'");
			if($this->__GB->__DB->num_rows($query) != 0){
				return true;
			}else{
				return false;
			}
		}

		public function getUserDetails($id,$userID)
		{
			$id = (int)$id;
			$userID = (int)$userID;
			if($id != 0 && $id != $userID){
				$query = $this->__GB->__DB->select('follows','`id`',"`from` = {$userID} AND `to` = {$id}");
				if($this->__GB->__DB->num_rows($query) != 0){
					$query =  $this->__GB->__DB->select('users', '`id`,`name`,`job`,`address`,`picture`,`username`', "`id` = {$id}");
					$profile = array();
					$fetch = $this->__GB->__DB->fetch_assoc($query);
					if(empty($fetch['name'])){
						$fetch['name'] = $fetch['username'];
					}
					$fetch['picture'] = $this->__GB->GetConfig('url','site').'safe_image.php?id='.$fetch['picture'];
					$fetch['totalFollowers'] = $this->__GB->CountRows('follows',"`to` = {$id}");
					$fetch['totalPosts'] = $this->__GB->CountRows('posts',"`ownerID` = {$id}");
					$fetch['mine'] = false;
					$fetch['followed'] = $this->__GB->isFollowing($userID,$fetch['id']);
					$profile['profile'] = $fetch;
					return $profile;
				}else{
					$query =  $this->__GB->__DB->select('users', '`id`,`name`,`job`,`address`,`picture`', "`id` = {$id}");
					$profile = array();
					$fetch = $this->__GB->__DB->fetch_assoc($query);
					$fetch['picture'] = $this->__GB->GetConfig('url','site').'safe_image.php?id='.$fetch['picture'];
					$fetch['totalFollowers'] = $this->__GB->CountRows('follows',"`to` = {$id}");
					$fetch['totalPosts'] = $this->__GB->CountRows('posts',"`ownerID` = {$id}");
					$fetch['mine'] = false;
					$fetch['followed'] = $this->__GB->isFollowing($userID,$fetch['id']);
					$profile['profile'] = $fetch;
					return $profile;
				}
			}else{
				$query =  $this->__GB->__DB->select('users', '`id`,`name`,`job`,`address`,`picture`', "`id` = {$userID}");
				$profile = array();
				$fetch = $this->__GB->__DB->fetch_assoc($query);
				$fetch['picture'] = $this->__GB->GetConfig('url','site').'safe_image.php?id='.$fetch['picture'];
				$fetch['totalFollowers'] = $this->__GB->CountRows('follows',"`to` = {$userID}");
				$fetch['totalPosts'] = $this->__GB->CountRows('posts',"`ownerID` = {$userID}");
				$fetch['mine'] = true;
				$fetch['followed'] = false;
				$profile['profile'] = $fetch;
				return $profile;
			}
		}

		public function GetAdmins($limit)
		{
			$query =  $this->__GB->__DB->select('admins', '*','','`id` DESC',$limit);
			$links = '';
			while ($fetch = $this->__GB->__DB->fetch_assoc($query)) {
				$links[] = $fetch;
			}
			return $links;
		}
		public function GetUsers($limit)
		{
			$query =  $this->__GB->__DB->select('users', '*','','`id` DESC',$limit);
			$links = '';
			while ($fetch = $this->__GB->__DB->fetch_assoc($query)) {
				$fetch['date'] = date('Y-m-d H:i', $fetch['date']);
				$links[] = $fetch;
			}
			return $links;
		}


		public function UserExist($username){
			$query = $this->__GB->__DB->select('users', '`id`', "`username` = '".$username."'");
			if($this->__GB->__DB->num_rows($query) != 0){
				return true;
			}else{
				return false;
			}
			$this->__GB->__DB->free_result();
		}
		public function EmailExist($email){
			$query = $this->__GB->__DB->select('users', '`id`', "`email` = '".$email."'");
			if($this->__GB->__DB->num_rows($query) != 0){
				return true;
			}else{
				return false;
			}
			$this->__GB->__DB->free_result();
		}
	}
?>