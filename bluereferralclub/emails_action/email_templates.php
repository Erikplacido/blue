<?php
function emailTemplate($type, $data = []) {
    // Cabeçalho HTML padrão com wrapper centralizado
    $header = <<<HTML
<html>
<body style="font-family: Arial, sans-serif; color: #333; margin:0; padding:0;">
  <table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
      <td align="center" style="padding:20px 0; text-align:center;">
        <table
          align="center"
          width="600"
          cellpadding="20"
          cellspacing="0"
          border="0"
          style="border:1px solid #e0e0e0; border-radius:8px; margin:0 auto;">
          <tr>
            <td align="center" style="padding-bottom:20px;">
              <img src="https://bluefacilityservices.com.au/bluereferralclub/assest/images/logo_blue.png"
                   alt="Blue Facility Services Logo"
                   style="max-width:200px; height:auto; display:block; margin:0 auto;">
            </td>
          </tr>
          <tr>
            <td align="center" style="padding:0 0 20px 0; text-align:center;">
HTML;

    // Rodapé HTML padrão
    $footer = <<<HTML
            </td>
          </tr>
          <tr>
            <td align="center" style="padding-top:20px; font-size:12px; color:#666;">
              Regards,<br>
              <strong>Blue Facility Services</strong><br>
              <small>Please do not reply to this automated email.</small>
            </td>
          </tr>
        </table>
      </td></tr>
    </table>
  </body>
</html>
HTML;

    // Seleção de assunto e corpo interno
    switch ($type) {
        case 'new_referral':
            $subject = 'New Booking Received';
            $inner   = "Hi {$data['first_name']},<br><br>"
                     . "You've got a new booking through the Blue Referral Club.<br><br>"
                     . "Log in to the platform to stay up to date with the status of this booking.";
            break;

        case 'referral_success':
            $subject = 'Booking Successfully Completed';
            $inner   = "Hi {$data['first_name']},<br><br>"
                     . "Congratulations! The booking ({$data['id_reserva']}) using your Referral Code has been successfully completed.<br><br>"
                     . "You’ll receive your payment soon. Just a reminder: payments are processed on the fifth business day of each month.";
            break;

        case 'referral_fail':
            $subject = 'Booking Not Completed';
            $inner   = "Hi {$data['first_name']},<br><br>"
                     . "Don't worry! The booking ({$data['id_reserva']}) you referred wasn't completed.<br><br>"
                     . "But Blue Facility Services will follow up with your referral to try and arrange a new opportunity.";
            break;

        case 'level_tanzanite':
            $subject = 'Welcome to Blue Tanzanite!';
            $inner   = "Hi {$data['first_name']},<br><br>"
                     . "Well done! You’ve just been upgraded to <strong>Blue Tanzanite</strong>.<br><br>"
                     . "Exclusive benefits await you!<br><br>"
                     . "<a href='#' style='background:#004aad;color:#fff;padding:10px;border-radius:5px;text-decoration:none;'>"
                     . "Click here to share your achievement on social media</a>";
            break;

        case 'level_sapphire':
            $subject = 'Welcome to Blue Sapphire!';
            $inner   = "Hi {$data['first_name']},<br><br>"
                     . "Well done! You’ve just been upgraded to <strong>Blue Sapphire</strong>.<br><br>"
                     . "You’ll now have access to even more exclusive perks!<br><br>"
                     . "<a href='#' style='background:#004aad;color:#fff;padding:10px;border-radius:5px;text-decoration:none;'>"
                     . "Click here to share your achievement on social media</a>";
            break;

        case 'payment_received':
            $subject = 'Payment Received';
            $inner   = "Hi {$data['first_name']},<br><br>"
                     . "We have received your payment for booking <strong>{$data['id_reserva']}</strong>.<br><br>"
                     . "Thank you for completing the process! Your service is now confirmed, and we’ll be in touch shortly to finalise the details.";
            break;

        default:
            return ['subject' => '', 'body' => ''];
    }

    // Monta e retorna o e-mail completo
    return [
        'subject' => $subject,
        'body'    => $header . $inner . $footer
    ];
}
