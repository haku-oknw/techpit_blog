<?php
class QueryArticle extends connect {
  private $article;
  const THUMBS_WIDTH = 200;// サムネイル画像の幅

  public function __construct() {
    parent::__construct();
  }

  public function setArticle(Article $article) {
    $this->article = $article;
  }

  // 画像アップロード
  private function saveFile($old_name) {
    $new_name = date('YmdHis').mt_rand();

    if ($type = exif_imagetype($old_name)) {
      list($width, $height) = getimagesize($old_name);// 元画像の縦横サイズを取得
      // サムネイルの比率を求める
      $rate = self::THUMBS_WIDTH / $width;
      $thumbs_height = $rate * $height;
      
      // キャンバス作成
      $canvas = imagecreatetruecolor(self::THUMBS_WIDTH, $thumbs_height);
      
      switch ($type) {// ファイルの種類が画像だったとき、種類によって拡張子を変更
        case IMAGETYPE_JPEG:
          $new_name .= '.jpg';
          // サムネイルを保存
          $image = imagecreatefromjpeg($old_name);
          imagecopyresampled($canvas, $image, 0, 0, 0, 0, self::THUMBS_WIDTH, $thumbs_height, $width, $height);
          imagejpeg($canvas, __DIR__.'/../album/thumbs-'.$new_name);
          break;
        case IMAGETYPE_GIF:
          $new_name .= '.gif';
          // サムネイルを保存
          $image = imagecreatefromgif($old_name);
          imagecopyresampled($canvas, $image, 0, 0, 0, 0, self::THUMBS_WIDTH, $thumbs_height, $width, $height);
          imagegif($canvas, __DIR__.'/../album/thumbs-'.$new_name);
          break;
        case IMAGETYPE_PNG:
          $new_name .= '.png';
          // サムネイルを保存
          $image = imagecreatefrompng($old_name);
          imagecopyresampled($canvas, $image, 0, 0, 0, 0, self::THUMBS_WIDTH, $thumbs_height, $width, $height);
          imagepng($canvas, __DIR__.'/../album/thumbs-'.$new_name);
          break;
        default:
          // JPEG・GIF・PNG以外の画像なら処理しない
          imagedestroy($canvas);
          return null;
      }
      imagedestroy($canvas);
      imagedestroy($image);

      // 元サイズの画像をアップロード
      move_uploaded_file($old_name, __DIR__.'/../album/'.$new_name);
      return $new_name;// 保存したファイル名を返す
    } else {
      return null;// 画像以外なら処理しない
    }
  }
  
  public function save() {
    // bindParam用
    $title = $this->article->getTitle();
    $body  = $this->article->getBody();
    $filename = $this->article->getFilename();
    $category_id = $this->article->getCategoryId();

    if ($this->article->getId()) {
      // ID があるときは上書き
      $id = $this->article->getId();

      // 新しいファイルがアップロードされたとき
      if ($file = $this->article->getFile()) {
        // ファイルが既にある場合は古いファイルを削除する
        $this->deleteFile();
        // 新しいファイルのアップロード
        $this->article->setFilename($this->saveFile($file['tmp_name']));
        $filename = $this->article->getFilename();
      }

      $stmt  = $this->dbh->prepare("UPDATE articles SET title=:title, body=:body, filename=:filename, category_id=:category_id, updated_at=NOW() WHERE id=:id");
      $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    } else {// ID がなければ新規作成
      // 画像があれば、$this->saveFile()メソッドを呼び、ファイル名を$this->articleのfilenameにセット
      if ($file = $this->article->getFile()) {
        $this->article->setFilename($this->saveFile($file['tmp_name']));
        $filename = $this->article->getFilename();
      }

      $stmt  = $this->dbh->prepare("INSERT INTO articles (title, body, filename, category_id, created_at, updated_at) VALUES (:title, :body, :filename, :category_id, NOW(), NOW())");
    }
    $stmt->bindParam(':title', $title, PDO::PARAM_STR);
    $stmt->bindParam(':body', $body, PDO::PARAM_STR);
    $stmt->bindParam(':filename', $filename, PDO::PARAM_STR);
    $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
    $stmt->execute();
  }

  private function deleteFile() {
    if ($this->article->getFilename()) {
      unlink(__DIR__.'/../album/thumbs-'.$this->article->getFilename());
      unlink(__DIR__.'/../album/'.$this->article->getFilename());
    }
  }

  public function delete() {
    if ($this->article->getId()) {
      $this->deleteFile();// 画像の削除
      $id = $this->article->getId();
      $stmt = $this->dbh->prepare("UPDATE articles SET is_delete=1 WHERE id=:id");
      $stmt->bindParam(':id', $id, PDO::PARAM_INT);
      $stmt->execute();
    }
  }

  public function find($id) {
    $stmt = $this->dbh->prepare("SELECT * FROM articles WHERE id=:id AND is_delete=0");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $articles = $this->getArticles($stmt->fetchAll(PDO::FETCH_ASSOC));
    return $articles[0];
  }

  public function findAll() {
    $stmt = $this->dbh->prepare("SELECT * FROM articles WHERE is_delete=0 ORDER BY created_at DESC");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $articles = $this->getArticles($stmt->fetchAll(PDO::FETCH_ASSOC));
    return $articles;
  }

  public function getPager($page = 1, $limit = 10, $month = null, $category_id = null) {
    $start = ($page - 1) * $limit;// LIMIT x,y:1ページ目を表示するとき、xは0になる
    $pager = array ('total' => null, 'articles' => null);

    // 月指定があれば、「2021-01%」のように検索できるよう末尾に追加
    if ($month) {
      $month .= '%';
    }
    // 総記事数
    // ベースSQL
    $sql = "SELECT COUNT(*) FROM articles WHERE is_delete=0";
    if ($month) {
      $stmt = $this->dbh->prepare($sql." AND created_at LIKE :month");
      $stmt->bindParam(':month', $month, PDO::PARAM_STR);
    } else if ($category_id === 0) {
      $stmt = $this->dbh->prepare($sql." AND category_id IS NULL");
    } else if ($category_id) {
      $stmt = $this->dbh->prepare($sql." AND category_id=:category_id");
      $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
    } else {
      $stmt = $this->dbh->prepare($sql);
    }
    $stmt->execute();
    $pager['total'] = $stmt->fetchColumn();

    // 表示するデータ
    // ベースSQL
    $sql = "SELECT * FROM articles WHERE is_delete=0 ";
    $orderBy = "ORDER BY created_at DESC LIMIT :start, :limit";
    if ($month) {
      $stmt = $this->dbh->prepare($sql."AND created_at LIKE :month ".$orderBy);
      $stmt->bindParam(':month', $month, PDO::PARAM_STR);
    } else if ($category_id === 0) {
      $stmt = $this->dbh->prepare($sql."AND category_id IS NULL ".$orderBy);
    } else if ($category_id) {
      $stmt = $this->dbh->prepare($sql."AND category_id=:category_id ".$orderBy);
      $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
    } else {
      $stmt = $this->dbh->prepare($sql.$orderBy);
    }
    $stmt->bindParam(':start', $start, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $pager['articles'] = $this->getArticles($stmt->fetchAll(PDO::FETCH_ASSOC));
    return $pager;
  }

  public function getMonthlyArchiveMenu() {
    $stmt = $this->dbh->prepare("
      SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_menu, COUNT(*) AS count
      FROM articles
      WHERE is_delete = 0
      GROUP BY DATE_FORMAT(created_at, '%Y-%m')
      ORDER BY month_menu DESC");
    $stmt->execute();
    $return = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $return[] = array('month' => $row['month_menu'], 'count' => $row['count']);
    }
    return $return;
  }

  private function getArticles($results) {
    $articles = array();
    foreach ($results as $result) {
      $article = new Article();
      $article->setId($result['id']);
      $article->setTitle($result['title']);
      $article->setBody($result['body']);
      $article->setFilename($result['filename']);
      $article->setCategoryId($result['category_id']);
      $article->setCreatedAt($result['created_at']);
      $article->setUpdatedAt($result['updated_at']);
      $articles[] = $article;
    }
    return $articles;
  }
}
?>