<?php
function emailTemplate($type, $data = []) {
    switch ($type) {
        case 'new_referral':
            return [
                'subject' => 'New Booking Received',
                'body' => "Hi {$data['first_name']},<br><br>You've got a new booking through the Blue Referral Club.<br><br>Log in to the platform to stay up to date with the status of this booking."
            ];

        case 'referral_success':
            return [
                'subject' => 'Booking Successfully Completed',
                'body' => "Hi {$data['first_name']},<br><br>Congratulations! The booking ({$data['id_reserva']}) using your Referral Code has been successfully completed.<br><br>You’ll receive your payment soon. Just a reminder: payments are processed on the fifth business day of each month."
            ];

        case 'referral_fail':
            return [
                'subject' => 'Booking Not Completed',
                'body' => "Hi {$data['first_name']},<br><br>Don't worry! For some reason, the booking ({$data['id_reserva']}) you referred wasn't completed.<br><br>But Blue Facility Services will follow up with your referral to try and arrange a new opportunity."
            ];

        case 'level_tanzanite':
            return [
                'subject' => 'Welcome to Blue Tanzanite!',
                'body' => "Hi {$data['first_name']},<br><br>Well done! You’ve just been upgraded to <strong>Blue Tanzanite</strong>.<br>Exclusive benefits await you!<br><br>Thanks for your effort — your network is impressive!<br><br><a href='#' style='background:#007bff;color:#fff;padding:10px;border-radius:5px;text-decoration:none;'>Click here to share your achievement on social media</a>"
            ];

        case 'level_sapphire':
            return [
                'subject' => 'Welcome to Blue Sapphire!',
                'body' => "Hi {$data['first_name']},<br><br>Well done! You’ve just been upgraded to <strong>Blue Sapphire</strong>.<br>You’ll now have access to even more exclusive perks!<br><br>Your engagement is outstanding!<br><br><a href='#' style='background:#007bff;color:#fff;padding:10px;border-radius:5px;text-decoration:none;'>Click here to share your achievement on social media</a>"
            ];
    }

    return ['subject' => '', 'body' => ''];
}