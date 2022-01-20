<?php
  include 'lib/secure.php';
  include 'lib/connect.php';
  include 'lib/queryArticle.php';
  include 'lib/article.php';

  $queryArticle = new QueryArticle();
  $articles = $queryArticle->findAll();

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
      <h1>記事一覧</h1>
<?php if ($articles): ?>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>ID</th>
            <th>タイトル</th>
            <th>本文</th>
            <th>画像</th>
            <th>作成日</th>
            <th>更新日</th>
            <th>編集</th>
            <th>削除</th>
          </tr>
        </thead>
        <tbody>
<?php foreach ($articles as $article): ?>
          <tr>
            <td><?php echo $article->getId() ?></td>
            <td><?php echo $article->getTitle() ?></td>
            <td><?php echo $article->getBody() ?></td>
            <td><?php echo $article->getFilename()? '<img src="./album/thumbs-'.$article->getFilename().'">': 'なし' ?></td>
            <td><?php echo $article->getCreatedAt() ?></td>
            <td><?php echo $article->getUpdatedAt() ?></td>
            <td><a href="edit.php?id=<?php echo $article->getId() ?>" class="btn btn-success">編集</a></td>
            <td><a href="delete.php?id=<?php echo $article->getId() ?>" class="btn btn-danger">削除</a></td>
          </tr>
<?php endforeach ?>
        </tbody>
      </table>
<?php else: ?>
      <div class="alert alert-info">
        <p>記事はありません。</p>
      </div>
<?php endif ?>
    </div>
  </div>
</main>
</body>
</html>