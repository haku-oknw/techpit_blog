<?php
  include 'lib/secure.php';
  include 'lib/connect.php';
  include 'lib/queryArticle.php';
  include 'lib/article.php';
  include 'lib/queryCategory.php';

  $title = "";  // タイトル
  $body  = "";  // 本文
  $title_alert = "";  // タイトルのエラー文言
  $body_alert  = "";  // 本文のエラー文言

  $queryCategory = new QueryCategory();
  $categories    = $queryCategory->findAll();

  if (!empty($_POST['title']) && !empty($_POST['body']) ) {
    $title = $_POST['title'];
    $body  = $_POST['body'];
    $article = new Article();
    $article->setTitle($title);
    $article->setBody($body);
    if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
      $article->setFile($_FILES['image']);
    }

    if (!empty($_POST['category'])) {
      $category = $queryCategory->find($_POST['category']);
      if ($category) {
        $article->setCategoryId($category->getId());
      }
    }

    $article->save();
    header('Location: backend.php');
  } else if(!empty($_POST)) {
    // POSTメソッドで送信されたが、titleかbodyが足りないとき
    // 存在するほうは変数へ、ない場合空文字にしてフォームのvalueに設定する
    if(!empty($_POST['title'])) {
      $title = $_POST['title'];
    } else {
      $title_alert = "タイトルを入力してください。";
    }
    if(!empty($_POST['body'])) {
      $body = $_POST['body'];
    } else {
      $body_alert = "本文を入力してください。";
    }
  }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blog</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
  <link rel="stylesheet" href="css/sign-in.css">
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
      <h1>記事の投稿</h1>
      <form action="post.php" method="post" enctype="multipart/form-data">
        <div class="mb-3">
          <label for="title" class="form-label">タイトル</label>
          <?php echo !empty($title_alert)? '<div class="alert alert-danger">'.$title_alert.'</div>': '' ?>
          <input type="text" name="title" id="title" value="<?php echo $title; ?>" class="form-control">
        </div>

        <div class="mb-3">
          <label for="body" class="form-label">本文</label>
          <?php echo !empty($body_alert)? '<div class="alert alert-danger">'.$body_alert.'</div>': '' ?>
          <textarea name="body" id="body" class="form-control" rows="10"><?php echo $body; ?></textarea>
        </div>

        <div class="mb-3">
          <label for="category" class="form-label">カテゴリー</label>
          <select name="category" id="category" class="form-control">
            <option value="0">なし</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?php echo $c->getId() ?>"><?php echo $c->getName() ?></option>
            <?php endforeach ?>
          </select>
        </div>

        <div class="mb-3">
          <label for="image" class="form-label">画像</label>
          <input type="file" name="image" id="image" class="form-control">
        </div>

        <div class="mb-3">
          <button type="submit" class="btn btn-primary">投稿する</button>
        </div>
      </form>
    </div>
  </div>
</main>
</body>
</html>