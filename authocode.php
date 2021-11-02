 <?php 
require_once('config.php'); 
//データベースへ接続、テーブルがない場合は作成 
session_start(); 

try { 
    $pdo = new PDO(DSN, DB_USER, DB_PASS); 
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
    $pdo->exec("create table if not exists userDeta( 
      id int not null auto_increment primary key, 
      email varchar(255), 
      password varchar(255), 
      created timestamp not null default current_timestamp 
    )"); 
} catch (Exception $e) { 
    echo $e->getMessage() . PHP_EOL; 
} 
//メールアドレスのバリデーション 
if (!$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) { 
    echo '入力された値が不正です。'; 
    return false; 
} 
//正規表現でパスワードをバリデーション 
if (preg_match('/\A(?=.*?[a-z])(?=.*?\d)[a-z\d]{8,100}+\z/i', $_POST['password'])) { 
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 
} else { 
    echo 'パスワードは半角英数字をそれぞれ1文字以上含んだ8文字以上で設定してください。'; 
    return false; 
} 
//データベース内のメールアドレスを取得 
$stmt = $pdo->prepare("select email from userDeta where email = ?"); 
$stmt->execute([$email]); 
$row = $stmt->fetch(PDO::FETCH_ASSOC); 
$_SESSION["password"] = $password; 
$_SESSION["email"] = $email; 


//データベース内のメールアドレスと重複していない場合、メール認証を実行 
if(!isset($row['email'])){ 
    /*******************メール認証******************** */ 
    $round = rand(1000,5000); 
     
    // セッション情報の保存 
    $_SESSION['round'] = $round; 

    /*-----------------メール送信-----------------*/ 
    require 'phpmailer/src/Exception.php'; 
    require 'phpmailer/src/PHPMailer.php'; 
    require 'phpmailer/src/SMTP.php'; 
    require 'phpmailer/setting.php'; 

    // PHPMailerのインスタンス生成 
        $mail = new PHPMailer\PHPMailer\PHPMailer(); 

        $mail->isSMTP(); // SMTPを使うようにメーラーを設定する 
        $mail->SMTPAuth = true; 
        $mail->Host = MAIL_HOST; // メインのSMTPサーバー（メールホスト名）を指定 
        $mail->Username = MAIL_USERNAME; // SMTPユーザー名（メールユーザー名） 
        $mail->Password = MAIL_PASSWORD; // SMTPパスワード（メールパスワード） 
        $mail->SMTPSecure = MAIL_ENCRPT; // TLS暗号化を有効にし、「SSL」も受け入れます 
        $mail->Port = SMTP_PORT; // 接続するTCPポート 

        // メール内容設定 
        $mail->CharSet = "UTF-8"; 
        $mail->Encoding = "base64"; 
        $mail->setFrom(MAIL_FROM,MAIL_FROM_NAME); 
        $mail->addAddress($email, '受信者さん'); //受信者（送信先）を追加する 
    //    $mail->addReplyTo('xxxxxxxxxx@xxxxxxxxxx','返信先'); 
    //    $mail->addCC('xxxxxxxxxx@xxxxxxxxxx'); // CCで追加 
    //    $mail->addBcc('xxxxxxxxxx@xxxxxxxxxx'); // BCCで追加 
        $mail->Subject = MAIL_SUBJECT; // メールタイトル 
        $mail->isHTML(true);    // HTMLフォーマットの場合はコチラを設定します 
        $body = 
        "新規会員登録ありがとうございます。
        以下４桁のコードでメール登録を完了してください。"
        .$round;
        //認証番号を入れる 
         
        $mail->Body  = $body; // メール本文 
        // メール送信の実行 
        if(!$mail->send()) { 
            echo 'メッセージは送られませんでした！'; 
            echo 'Mailer Error: ' . $mail->ErrorInfo; 
        } else { 
            echo '送信完了！'; 
        } 
    /*----------------------------------------------------*/  
?> 
    <!---/******認証入力**** */------> 
    <form action="register.php" method="post"> 
        <input type="text" name="authcode" placeholder="認証コードを入力してください" width="300px"> 
        <input type="submit" name="submit_authcode" value="送信"> 
    </form> 

<?php 
}else{//データベース内のメールアドレスと重複している 
?> 
    <link href="https://fonts.googleapis.com/css?family=Ubuntu" rel="stylesheet"> 
    <link rel="stylesheet" href="path/to/font-awesome/css/font-awesome.min.css"> 

    <body id="log_body"> 
        <main class="main_log"> 
            <p>既に登録されたメールアドレスです</p> 
            <h1 style="text-align:center;margin-top: 0em;margin-bottom: 1em;" class="h1_log">初めての方はこちら</h1> 
            <form action="authcode.php" method="post" class="form_log"> 
                <!--<label for="email" class="label">メールアドレス</label><br>--> 
                <input type="email" name="email" class="textbox un" placeholder="メールアドレス"><br> 
                <!--<label for="password" class="label">パスワード</label><br>--> 
                <input type="password" name="password" class="textbox pass" placeholder="パスワード"><br> 
                <button type="submit" class="log_button">新規登録する</button> 
                <p style="text-align:center;margin-top: 1.5em;">※パスワードは半角英数字をそれぞれ１文字以上含んだ、８文字以上で設定してください。</p> 
            </form> 
        </main> 
    </body> 
<?php 
    return false; 
} 
?> 