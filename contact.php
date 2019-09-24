<!DOCTYPE html>
<html>
    <?php include("header.php"); ?>
    <body>
    <div class="row m-5 justify-content-center">
        <div class="col contacte">
        </div>
        <div class="col">
            <form method="post" action="contact_email.php">
                <input type="email" name="email" id="email" placeholder="Votre email" size="20" required>
                <br>
                <br>
                <textarea name="message" id="message" rows="7" cols="50" required>Bonjour, </textarea><br><br>

                <input type="submit" value="Envoyer">
            </form>
        </div>
    </div>
    </body>
    <?php include("footer.php"); ?>
</html>