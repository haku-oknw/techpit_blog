<?php
include('lib/secure.php');
include('lib/connect.php');
include('lib/queryCategory.php');

$queryCategory = new QueryCategory();
$formCategory  = null;// 編集対象のカテゴリー情報

if (!empty($_POST['action']) && $_POST['action'] == 'add' && !empty($_POST['name'])) {
  $category = new Category();
  $category->setName($_POST['name']);
  $category->save();
} else if (!empty($_GET['action']) && $_GET['action'] == 'edit' && !empty($_GET['id'])) {
  // 編集モードのとき
  $formCategory = $queryCategory->find($_GET['id']);
} else if (!empty($_POST['action']) && $_POST['action'] == 'edit' && !empty($_POST['id']) && !empty($_POST['name'])) {
  // 編集
  $category = $queryCategory->find($_POST['id']);
  if ($category) {
    $category->setName($_POST['name']);
    $category->save();
  }
} else if (!empty($_GET['action']) && $_GET['action'] == 'delete' && !empty($_GET['id'])) {
  // 消去モードのとき
  $category = $queryCategory->find($_GET['id']);
  if ($category) {
    $category->delete();
  }
}
// 登録されている、カテゴリーをすべて取得
$categories = $queryCategory->findAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blog Backend</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
  <link rel="stylesheet" href="css/blog.css">
  <style>
    .bg-red {
      background-color: #ff6644;
    }
  </style>
</head>
<body>
  <?php include('lib/nav.php'); ?>
  <main class="container">
    <div class="row">
      <div class="col-md-12">
        <h1>カテゴリー</h1>

        <?php if ($formCategory): ?>
          <h2>編集</h2>
          <form action="category.php" method="post" class="row">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?php echo $formCategory->getId() ?>">

            <div class="col-md-6">
              <input type="text" name="name" value="<?php echo $formCategory->getName() ?>" class="form-control">
            </div>
            <div class="col-md-6">
              <button type="submit" class="btn btn-primary">編集する</button>
            </div>
          </form>
          <hr>
        <?php endif ?>

        <h2>新規追加</h2>
        <form action="category.php" method="post" class="row">
          <input type="hidden" name="action" value="add">
          <div class="col-mb-6">
            <input type="text" name="name" class="form-control">
          </div>
          <div class="col-mb-6">
            <button type="submit" class="btn btn-primary">追加する</button>
          </div>
        </form>
        <hr>

        <?php if ($categories): ?>
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>ID</th>
                <th>カテゴリー名</th>
                <th>編集</th>
                <th>削除</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($categories as $c): ?>
                <tr>
                  <td><?php echo $c->getId() ?></td>
                  <td><?php echo $c->getName() ?></td>
                  <td><a href="category.php?action=edit&id=<?php echo $c->getId() ?>" class="btn btn-success">編集</a></td>
                  <td><a href="category.php?action=delete&id=<?php echo $c->getId() ?>" class="btn btn-danger">削除</a></td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="alert alert-info">カテゴリーはまだ登録されてません。</div>
        <?php endif ?>
      </div>
    </div>
  </main>
</body>
</html>