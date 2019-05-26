<?php 
use Hcode\PageAdmin;
use Hcode\Model\User;

$app->get("/admin/users", function() {
	User::verifyLogin();
	
	$search = isset($_GET['search']) ? $_GET['search'] : "";
	
	$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; 
	
	if($search != '') {
		
		$pagination = User::getPageSearch($search, $page, 10);
		
	} else {
	
		$pagination = User::getPage($page, 10);
		
	}
	
	$pages = [];
	for($x = 0; $x < $pagination['pages']; $x++) {
		array_push($pages, ['href' => '/admin/users?'. http_build_query([
				'page' => $x+1,
				'search' => $search
			]),
			'text' => $x+1
		]);
	}
	
	$pageAdmin = new PageAdmin();
	$pageAdmin->setTPL("users", array(
			"users" => $pagination['data'],
			"search" => $search,
			"pages" => $pages
	));
});
	
$app->get("/admin/users/:iduser/password", function($iduser) {
	
	User::verifyLogin();
	
	$user = new User();
	
	$user->get((int) $iduser);
	
	$page = new PageAdmin();
	
	$page->setTPL("users-password", [
			"user" => $user->getValues(),
			"msgError" => $user->getErrorRegister(),
			"msgSuccess" => $user->getSuccess()
	]);
	
});
		
$app->post("/admin/users/:iduser/password", function($iduser) {
	
	User::verifyLogin();
	
	if(!isset($_POST['despassword']) || $_POST['despassword'] === '') {
		User::setErrorRegister("Preencha a nova senha.");
		header("Location: /admin/users/$iduser/password");
		exit;
	}
	
	if(!isset($_POST['despassword-confirm']) || $_POST['despassword-confirm'] === '') {
		User::setErrorRegister("Preencha a confirmação da nova senha.");
		header("Location: /admin/users/$iduser/password");
		exit;
	}
	
	if($_POST['despassword'] !== $_POST['despassword-confirm']) {
		User::setErrorRegister("As senhas não correspondem.");
		header("Location: /admin/users/$iduser/password");
		exit;
	}
	
	$user = new User();
	
	$user->get((int) $iduser);
	
	$user->setPassword($_POST['despassword']);
	
	User::setSuccess("Senha atualizada com sucesso.");
	header("Location: /admin/users/$iduser/password");
	exit;
});
	
$app->get("/admin/users/create", function() {
	User::verifyLogin();
	$page = new PageAdmin();
	$page->setTPL("users-create");
});
		
$app->get("/admin/users/:iduser/delete", function($iduser) {
	User::verifyLogin();
	$user = new User();
	$user->get((int) $iduser);
	$user->delete();
	header("Location: /admin/users");
	exit;
});
			
$app->get("/admin/users/:iduser", function($iduser) {
	User::verifyLogin();
	$user= new User();
	$user->get((int) $iduser);
	$page = new PageAdmin();
	$page->setTPL("users-update", array(
			"user"=>$user->getValues()
	));
});
				
$app->post("/admin/users/create", function() {
	User::verifyLogin();
	$user = new User();
	$_POST['inadmin'] = isset($_POST['inadmin']) ? 1 : 0;
	$user->setData($_POST);
	$user->save();
	header("Location: /admin/users");
	exit;
});
					
$app->post("/admin/users/:iduser", function($iduser) {
	User::verifyLogin();
	$user = new User();
	$user->get((int)$iduser);
	$_POST['inadmin'] = isset($_POST['inadmin']) ? 1 : 0;
	$user->setData($_POST);
	$user->update();
	header("Location: /admin/users");
	exit;
});

?>