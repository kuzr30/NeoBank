<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Account;
use App\Entity\Transaction;
use App\Entity\Card;
use App\Entity\Loan;
use App\Entity\LoanPayment;
use App\Enum\CreditTypeEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class BankingFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Créer un utilisateur de test
        $user = new User();
        $user->setEmail('test@banking.com');
        $user->setFirstName('Jean');
        $user->setLastName('Dupont');
        $user->setPhone('0123456789');
        $user->setAddress('123 Rue de la Banque, 75001 Paris');
        $user->setStatus('active');
        $user->setEmailVerified(true);
        $user->setTwoFactorEnabled(false);
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);
        
        $manager->persist($user);
        
        // Créer un compte bancaire
        $account = new Account();
        $account->setOwner($user);
        $account->setBalance('5000.00');
        $account->setBalance('4500.00');
        $account->setType('checking');
        $account->setStatus('active');
        $account->setCurrency('EUR');
        $account->setOverdraftLimit('1000.00');
        $account->setInterestRate('0.0050');
        
        $manager->persist($account);
        
        // Créer quelques transactions
        $transactions = [
            [
                'amount' => '2500.00',
                'type' => 'credit',
                'category' => 'salary',
                'description' => 'Salaire mensuel'
            ],
            [
                'amount' => '85.50',
                'type' => 'debit',
                'category' => 'groceries',
                'description' => 'Courses alimentaires'
            ],
            [
                'amount' => '45.00',
                'type' => 'debit',
                'category' => 'transport',
                'description' => 'Abonnement transport'
            ],
            [
                'amount' => '120.00',
                'type' => 'debit',
                'category' => 'dining',
                'description' => 'Restaurant'
            ]
        ];
        
        foreach ($transactions as $transactionData) {
            $transaction = new Transaction();
            $transaction->setSourceAccount($account);
            $transaction->setAmount($transactionData['amount']);
            $transaction->setType($transactionData['type']);
            $transaction->setCategory($transactionData['category']);
            $transaction->setDescription($transactionData['description']);
            $transaction->setStatus('completed');
            $transaction->setCurrency('EUR');
            $transaction->setProcessedAt(new \DateTimeImmutable('-' . rand(1, 30) . ' days'));
            
            $manager->persist($transaction);
        }
        
        // Créer une carte bancaire
        $card = new Card();
        $card->setAccount($account);
        $card->setCardholderName($user->getFirstName() . ' ' . $user->getLastName());
        $card->setExpiryDate(new \DateTime('+3 years'));
        $card->setCvv('123');
        $card->setType('debit');
        $card->setCategory('standard');
        $card->setStatus('active');
        $card->setDailyLimit('500.00');
        $card->setMonthlyLimit('3000.00');
        $card->setDailySpent('0.00');
        $card->setMonthlySpent('0.00');
        $card->setContactlessEnabled(true);
        $card->setOnlinePaymentsEnabled(true);
        $card->setInternationalPaymentsEnabled(false);
        
        $manager->persist($card);
        
        // Créer un prêt
        $loan = new Loan();
        $loan->setAccount($account);
        $loan->setAmount('25000.00');
        $loan->setRemainingAmount('18500.00');
        $loan->setMonthlyPayment('520.83');
        $loan->setInterestRate('3.5000');
        $loan->setTermMonths(60);
        $loan->setRemainingMonths(36);
        $loan->setType(CreditTypeEnum::AUTO);
        $loan->setStatus('active');
        $loan->setPurpose('Achat véhicule neuf');
        $loan->setApprovedAt(new \DateTimeImmutable('-2 years'));
        $loan->setDisbursedAt(new \DateTimeImmutable('-2 years'));
        $loan->setFirstPaymentDate(new \DateTimeImmutable('-23 months'));
        $loan->setNextPaymentDate(new \DateTimeImmutable('+7 days'));
        $loan->setLastPaymentDate(new \DateTimeImmutable('-1 month'));
        $loan->setTotalInterest('6500.00');
        $loan->setTotalPaid('6500.00');
        $loan->setMissedPayments(0);
        
        $manager->persist($loan);
        
        // Créer quelques échéances de prêt
        for ($i = 1; $i <= 24; $i++) {
            $payment = new LoanPayment();
            $payment->setLoan($loan);
            $payment->setAmount('520.83');
            $payment->setPrincipalAmount('450.00');
            $payment->setInterestAmount('70.83');
            $payment->setType('regular');
            $payment->setStatus('paid');
            $payment->setDueDate(new \DateTime('-' . (25 - $i) . ' months'));
            $payment->setPaidAt(new \DateTimeImmutable('-' . (25 - $i) . ' months'));
            
            $manager->persist($payment);
        }
        
        // Créer la prochaine échéance
        $nextPayment = new LoanPayment();
        $nextPayment->setLoan($loan);
        $nextPayment->setAmount('520.83');
        $nextPayment->setPrincipalAmount('450.00');
        $nextPayment->setInterestAmount('70.83');
        $nextPayment->setType('regular');
        $nextPayment->setStatus('pending');
        $nextPayment->setDueDate(new \DateTime('+7 days'));
        
        $manager->persist($nextPayment);
        
        $manager->flush();
    }
}
