<?php
session_start();

//On enregistre notre token
$token = bin2hex(rand(1,3000) + rand(1,3000) + rand(1,3000) + rand(1,3000) + rand(1,3000) + rand(1,3000));

$_SESSION['token'] = $token;
?>

<!DOCTYPE html>
<html>
    <?php include("header.php"); ?>
    <body>
    <div class="row m-5 justify-content-center">
        <div class="col d-none d-lg-block">
            <img src="img/contacte.jpg">
        </div>
        <div class="col-md-1 align-self-center d-none d-lg-block">
            <div>
                <i class="far fa-paper-plane"></i>
            </div>
        </div>
        <div class="col">
            <form method="post" action="contact_email.php">
                <input type="email" name="email" id="email" placeholder="Votre email" size="20" required>
                <br>
                <br>
                <textarea name="message" id="message" rows="7" cols="50" required>Bonjour, </textarea><br><br>
                <input type="hidden" name="token" id="token" value="<?php echo $token; ?>" />

                <label>Captcha :</label> <input name="captcha" id="captcha" placeholder="1 + 1 = ?" size="6" required></input>
                <input type="submit" value="Envoyer">
            </form>
        </div>
    </div>
    </body>
    <?php include("footer.php"); ?>
</html>