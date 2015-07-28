<?php
	include 'config.php';
	include 'include/Database.php';
	include 'include/Security.php';
	include 'include/General.php';
	include 'include/Posts.php';
	include 'include/Users.php';
	include 'include/Pagination.php';
	$__DB = new Database($_config);
	$__DB->connect();
	$__DB->select_db();
	$__Sec 	= new Security($__DB);
	$__GB 	= new General($__DB,$__Sec);
	$__PO	= new Posts($__GB);
	$__USERS	= new Users($__GB);
	
	if(isset($_GET['tag'])){
		if(!empty($_GET['token'])){
			$userID = $__USERS->getUserID($_GET['token']);
		}else{
			$userID = 0;
		}
		switch ($_GET['tag']) {
			case 'addMessage':
				if(isset($_POST['message']) && $userID !=0){
					$__USERS->addMessage($userID,$_POST);
				}
				break;
			case 'conversation':
				if($userID != 0 && isset($_GET['id'],$_GET['recipient'])){
					$__USERS->getConversation($userID,$_GET['id'],$_GET['recipient']);
				}
				break;
			case 'conversations':
				if($userID != 0){
					$__USERS->getConversations($userID);
				}
				break;
			case 'getProfile':
				if($userID != 0){
					$__USERS->getProfile($userID);
				}
				break;
			case 'comment':
				if($userID !=0){
					if(isset($_POST['comment'])){
						$__PO->addComment($_POST['comment'],$_GET['id'],$userID);
					}
				}
				break;
				case 'comments':
					if($userID !=0){
						if(isset($_GET['id'])){
							$__PO->getPostComments($_GET['id']);
						}
					}
					break;
			case 'post':
				if($userID != 0){
					$__PO->getPost($_GET['id'],$userID);
				}
				break;
			case 'getFollowing':
				if($userID != 0){
					$__USERS->getFollowing($userID);
				}
			break;
			case 'simpleUserInfo':
				if(isset($_GET['id'])){
					if($_GET['id'] != 0){
						$user = $__USERS->getUser($_GET['id']);
					}else{
						$user = $__USERS->getUser($userID);
					}
					$__GB->prepareMessage($user);

				}
				break;
			case 'unfollow':
				if ($userID != 0) {
					$__USERS->unFollow($userID, $_GET['id']);
				}
				break;
			case 'follow':
					if ($userID != 0) {
						$__USERS->Follow($userID, $_GET['id']);
					}
				break;
			case 'unlike':
				 if ($userID != 0) {
					$__PO->unlikePost($userID,$_GET['id']);
				 }
				break;
			case 'liked':
				if($userID != 0){
						$likes = $__USERS->getLikes($userID);
						$where = implode(' OR `id` = ', $likes);
						$where = '`id` = '.rtrim($where,'OR');
					}else{
						$where = '`privacy` = 1';
					}
						$rows = $__GB->CountRows('posts',$where);
						$page = (isset($_GET['page']) && !empty($_GET['page'])) ? $__Sec->MA_INT($_GET['page']) : 1;
						$__PAG = new Pagination($page,
												$rows
												,8,
												'api.php?page=#i#');
						$posts = $__PO->getPosts($__PAG->limit,$where,$userID);
						$posts['pages'] = $__PAG->pages;
						$__GB->prepareMessage($posts);
				break;
			case 'like':
				if($userID != 0){
					if(isset($_GET['id'])){
						$__PO->likePost($userID,$_GET['id']);
					}
				}
				break;
			case 'publish':
				if($userID != 0){
					if(isset($_FILES['image'])){
						$imageID = $__GB->uploadImage($_FILES['image']);
					}else{
						$imageID = 0;
					}
					$__PO->publishStatus($_POST,$userID,$imageID);
				}
			break;
			case 'posts':
				if($userID != 0){
					$follows = $__USERS->getFollows($userID);
					if($follows != 0){
						$where = implode(' OR `ownerID` = ', $follows);
						$where = '`ownerID` = '.$userID.' OR `privacy` = 1 OR `ownerID` = '.rtrim($where,'OR');
					}else{
						$where = '`privacy` = 1';
					}
				}else{
					$where = '`privacy` = 1';
				}
					$rows = $__GB->CountRows('posts',$where);
					$page = (isset($_GET['page']) && !empty($_GET['page'])) ? $__Sec->MA_INT($_GET['page']) : 1;
					$__PAG = new Pagination($page,
											$rows
											,8,
											'api.php?page=#i#');
					$posts = $__PO->getPosts($__PAG->limit,$where,$userID);
					$posts['pages'] = $__PAG->pages;
					$__GB->prepareMessage($posts);
				break;
			case 'users':
				if ($userID != 0) {
					$user = $__USERS->getUserDetails($_GET['id'],$userID);
					if($user['profile']['followed'] == false){
						$where = '`ownerID` = '.$user['profile']['id'].' AND `privacy` = 1';
					}else{
						$where = '`ownerID` = '.$user['profile']['id'];
					}
					$rows = $__GB->CountRows('posts',$where);
					$page = (isset($_GET['page']) && !empty($_GET['page'])) ? $__Sec->MA_INT($_GET['page']) : 1;
					$__PAG = new Pagination($page,
											$rows
											,3,
											'api.php?page=#i#');
					$posts = $__PO->getUserPosts($__PAG->limit,$where,$user['profile']['id']);
					$posts['pages'] = $__PAG->pages;
					array_merge($user,$posts);
					$user['userPosts'] = $posts;
					$__GB->prepareMessage($user);
				}
				break;
			case 'login':
				if(isset($_POST['username'],$_POST['password'])){
					$__USERS->userLogin($_POST['username'],$_POST['password']);
				}else{
					$response = array(
								  'statusCode' => 0,
								  'statusMessage' => 'Some Params are Missing'
								  );
					$__GB->prepareMessage($response);
				}
				break;
			case 'register':
				if(isset($_POST['username'],$_POST['password'],$_POST['email'])){
					$__USERS->userRegister($_POST);
				}else{
					$response = array(
								  'statusCode' => 0,
								  'statusMessage' => 'Some Params are Missing'
								  );
					$__GB->prepareMessage($response);
				}
				break;
			case 'updateProfile':
				if($userID != 0){
					if(isset($_FILES['image'])){
						$imageID = $__GB->uploadImage($_FILES['image']);
					}else{
						$imageID = 0;
					}
					$__USERS->updateProfile($_POST,$imageID,$userID);
				}
				break;
			case 'number':
				if(isset($_GET['checkNumber'])){
					$__GB->check_number($_GET['checkNumber']);
				}else if(isset($_GET['code'],$_GET['id'])){
					$__GB->verify_number($_GET['code'],$_GET['id']);
				}
				break;
			case 'disclaimer':
				$dis = $__GB->getConfig('disclaimer','site');
				$__GB->prepareMessage(array('disclaimer'=>$dis));
				break;
			case 'searchFriend':
				if (isset($_GET['name']) && $userID != 0) {
					$__USERS->searchFriend($_GET['name'],$userID);
				}
				break;
		}

	}else{
		$response = array(
					  'statusCode' => 0,
					  'statusMessage' => 'Some Params are Missing'
					  );
		$__GB->prepareMessage($response);
	}
?>