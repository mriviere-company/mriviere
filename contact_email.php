 <?php
    $to = $_POST['email'];
    $to_email_address = 'rivierematthieupro@gmail.com';
    $from = 'contact@mriviere.eu';
    ini_set("SMTP", "smtp.mriviere.com");
    $subject = 'Devis de ' . $to;
    $message = $_POST['message'];
    $headers  = "MIME-Version: 1.0 \n";
    $headers .= "Content-type: text/html; charset=iso-8859-1 \n";
    $headers .= "From: $from  \n";
    $headers .= "Disposition-Notification-To: $from  \n";
    $headers .= "X-Priority: 1  \n";
    $headers .= "X-MSMail-Priority: High \n";

    $CR_Mail = TRUE;

    $CR_Mail = @mail ($to_email_address, $subject, $message, $headers);



     if ($CR_Mail === FALSE)

     {

         echo "L'email a bien été envoyé.<br> \n";

     }

     else

     {

         echo "Une erreur c'est produite lors de l'envoi de l'email.<br> \n";

     }
    header("Location: http://www.mriviere.eu/contact.php");
    exit;
 ?>