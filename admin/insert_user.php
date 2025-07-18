<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

require_once('../bluereferralclub/conexao.php');
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Obter e sanitizar inputs
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $mobile = trim($_POST['mobile']);
        $referral_code = trim($_POST['referral_code']);
        $tfn = trim($_POST['tfn']);
        $abn = trim($_POST['abn']);
        $commission_amount = $_POST['commission_amount'] ?: 0;
        $referral_club_level_name = trim($_POST['referral_club_level_name']);
        $bankName = trim($_POST['bankName']);
        $agency = trim($_POST['agency']);
        $bsb = trim($_POST['bsb']);
        $accountNumber = trim($_POST['accountNumber']);
        $abnNumber = trim($_POST['abnNumber']);
        $work_rights_details = trim($_POST['work_rights_details']);
        $user_type = $_POST['user_type'];

        // Preparar e executar query
        $sql = "INSERT INTO users (
            first_name, last_name, email, password, mobile, referral_code, tfn, abn,
            commission_amount, referral_club_level_name, bankName, agency, bsb,
            accountNumber, abnNumber, work_rights_details, user_type, created_at
        ) VALUES (
            :first_name, :last_name, :email, :password, :mobile, :referral_code, :tfn, :abn,
            :commission_amount, :referral_club_level_name, :bankName, :agency, :bsb,
            :accountNumber, :abnNumber, :work_rights_details, :user_type, NOW()
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':email' => $email,
            ':password' => $password,
            ':mobile' => $mobile,
            ':referral_code' => $referral_code,
            ':tfn' => $tfn,
            ':abn' => $abn,
            ':commission_amount' => $commission_amount,
            ':referral_club_level_name' => $referral_club_level_name,
            ':bankName' => $bankName,
            ':agency' => $agency,
            ':bsb' => $bsb,
            ':accountNumber' => $accountNumber,
            ':abnNumber' => $abnNumber,
            ':work_rights_details' => $work_rights_details,
            ':user_type' => $user_type
        ]);

        header('Location: add_user.php?success=1');
        exit();

    } catch (PDOException $e) {
        die("Erro ao inserir usuário: " . $e->getMessage());
    }
} else {
    header('Location: referral_club.php');
    exit();
}
